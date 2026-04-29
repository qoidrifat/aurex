<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Analysis;
use App\Models\Recommendation;
use App\Models\UploadedImage;
use App\Services\AurexAiClient;
use App\Services\StyleReportComposer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnalysisController extends Controller
{
    public function create(): View
    {
        return view('analysis.upload');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'selfie' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
        ]);

        $user = $request->user();
        $file = $validated['selfie'];
        $path = $file->store('selfies/'.$user->id, 'public');

        $image = UploadedImage::create([
            'user_id' => $user->id,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        $analysis = Analysis::create([
            'user_id' => $user->id,
            'uploaded_image_id' => $image->id,
            'status' => 'pending',
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'analysis.created',
            'subject_type' => Analysis::class,
            'subject_id' => $analysis->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('analysis.processing', $analysis);
    }

    public function processing(Request $request, Analysis $analysis): View
    {
        $this->authorizeAnalysis($request, $analysis);

        return view('analysis.processing', [
            'analysis' => $analysis->load('uploadedImage'),
        ]);
    }

    public function run(Request $request, Analysis $analysis, AurexAiClient $ai, StyleReportComposer $composer): JsonResponse
    {
        $this->authorizeAnalysis($request, $analysis);

        if ($analysis->isCompleted()) {
            return response()->json(['status' => 'completed', 'redirect' => route('analysis.show', $analysis)]);
        }

        $analysis->update(['status' => 'processing']);

        $payload = $ai->analyze($analysis->uploadedImage);

        DB::transaction(function () use ($analysis, $payload, $ai, $composer): void {
            $ai->apply($analysis, $payload);

            Recommendation::where('analysis_id', $analysis->id)->delete();

            $order = 0;
            foreach ((array) $payload['hairstyles'] as $hair) {
                Recommendation::create([
                    'analysis_id' => $analysis->id,
                    'type' => 'hairstyle',
                    'label' => (string) $hair,
                    'sort_order' => $order++,
                ]);
            }
            foreach ((array) $payload['colors'] as $color) {
                Recommendation::create([
                    'analysis_id' => $analysis->id,
                    'type' => 'color',
                    'label' => (string) $color,
                    'hex_color' => $this->colorNameToHex((string) $color),
                    'sort_order' => $order++,
                ]);
            }
            foreach ((array) $payload['outfits'] as $outfit) {
                Recommendation::create([
                    'analysis_id' => $analysis->id,
                    'type' => 'outfit',
                    'label' => (string) $outfit,
                    'sort_order' => $order++,
                ]);
            }

            $composer->composeFor($analysis->fresh());
        });

        ActivityLog::create([
            'user_id' => $analysis->user_id,
            'action' => 'analysis.completed',
            'subject_type' => Analysis::class,
            'subject_id' => $analysis->id,
            'context' => ['source' => $payload['source'] ?? 'mock'],
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'status' => 'completed',
            'redirect' => route('analysis.show', $analysis),
        ]);
    }

    public function show(Request $request, Analysis $analysis): View
    {
        $this->authorizeAnalysis($request, $analysis);

        $analysis->load(['uploadedImage', 'recommendations', 'styleReport']);

        return view('analysis.result', [
            'analysis' => $analysis,
            'hairstyles' => $analysis->recommendations->where('type', 'hairstyle')->values(),
            'colors' => $analysis->recommendations->where('type', 'color')->values(),
            'outfits' => $analysis->recommendations->where('type', 'outfit')->values(),
        ]);
    }

    public function history(Request $request): View
    {
        $analyses = $request->user()
            ->analyses()
            ->with('uploadedImage')
            ->latest()
            ->paginate(12);

        return view('analysis.history', [
            'analyses' => $analyses,
        ]);
    }

    public function destroy(Request $request, Analysis $analysis): RedirectResponse
    {
        $this->authorizeAnalysis($request, $analysis);

        $image = $analysis->uploadedImage;
        $analysis->delete();

        if ($image !== null) {
            Storage::disk($image->disk)->delete($image->path);
            $image->delete();
        }

        return redirect()->route('analysis.history')->with('status', 'Analysis removed.');
    }

    private function authorizeAnalysis(Request $request, Analysis $analysis): void
    {
        abort_unless($analysis->user_id === $request->user()->id, 403);
    }

    private function colorNameToHex(string $name): ?string
    {
        $map = [
            'olive' => '#556B2F',
            'camel' => '#B08A56',
            'rust' => '#B7410E',
            'charcoal' => '#2A2A2A',
            'cream' => '#F5F5F5',
            'navy' => '#1B2A4E',
            'sand' => '#D9C6A7',
            'forest' => '#2F4F2F',
            'slate' => '#4A5560',
            'oat' => '#E8DCC3',
            'terracotta' => '#C56B48',
            'ink' => '#111111',
        ];

        return $map[strtolower(trim($name))] ?? null;
    }
}
