<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Services\StyleReportComposer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $reports = $request->user()
            ->styleReports()
            ->with('analysis.uploadedImage')
            ->latest()
            ->paginate(10);

        return view('reports.index', [
            'reports' => $reports,
        ]);
    }

    public function show(Request $request, Analysis $analysis, StyleReportComposer $composer): View
    {
        abort_unless($analysis->user_id === $request->user()->id, 403);

        $report = $analysis->styleReport()->first() ?? $composer->composeFor($analysis);
        $analysis->load(['uploadedImage', 'recommendations']);

        return view('reports.show', [
            'analysis' => $analysis,
            'report' => $report,
            'hairstyles' => $analysis->recommendations->where('type', 'hairstyle')->values(),
            'colors' => $analysis->recommendations->where('type', 'color')->values(),
            'outfits' => $analysis->recommendations->where('type', 'outfit')->values(),
        ]);
    }

    public function save(Request $request, Analysis $analysis, StyleReportComposer $composer): RedirectResponse
    {
        abort_unless($analysis->user_id === $request->user()->id, 403);

        $report = $composer->composeFor($analysis);
        $report->update(['is_saved' => true]);

        return redirect()
            ->route('reports.show', $analysis)
            ->with('status', 'Style report saved to your library.');
    }

    public function download(Request $request, Analysis $analysis, StyleReportComposer $composer): Response
    {
        abort_unless($analysis->user_id === $request->user()->id, 403);

        $report = $analysis->styleReport()->first() ?? $composer->composeFor($analysis);
        $analysis->load(['uploadedImage', 'recommendations']);

        $html = view('reports.pdf', [
            'analysis' => $analysis,
            'report' => $report,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Disposition' => 'inline; filename="aurex-report-'.$analysis->id.'.html"',
        ]);
    }
}
