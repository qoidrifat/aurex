<?php

namespace App\Services;

use App\Models\Analysis;
use App\Models\UploadedImage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AurexAiClient
{
    public function __construct(
        private readonly ?string $baseUrl = null,
        private readonly ?int $timeout = null,
    ) {}

    /**
     * Send an uploaded image to the AUREX AI microservice and return the parsed
     * analysis payload. Falls back to a deterministic mock response if the
     * service is unreachable or returns an error — this keeps the scaffold
     * runnable end-to-end without a live AI backend.
     *
     * @return array{
     *     face_shape: string,
     *     skin_undertone: string,
     *     style_score: int,
     *     hairstyles: array<int, string>,
     *     colors: array<int, string>,
     *     outfits: array<int, string>,
     *     source: string,
     * }
     */
    public function analyze(UploadedImage $image): array
    {
        $base = $this->baseUrl ?? (string) config('services.aurex_ai.url');
        $timeout = $this->timeout ?? (int) config('services.aurex_ai.timeout', 30);

        if ($base !== '') {
            try {
                $response = Http::timeout($timeout)
                    ->acceptJson()
                    ->asMultipart()
                    ->attach(
                        'image',
                        Storage::disk($image->disk)->get($image->path) ?? '',
                        $image->original_name ?? basename($image->path),
                    )
                    ->post(rtrim($base, '/').'/analyze');

                if ($response->successful()) {
                    return $this->normalize($response->json(), source: 'service');
                }

                Log::warning('AUREX AI returned non-2xx', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } catch (ConnectionException $e) {
                Log::info('AUREX AI unreachable, falling back to mock', [
                    'error' => $e->getMessage(),
                ]);
            } catch (Throwable $e) {
                Log::warning('AUREX AI call failed', ['error' => $e->getMessage()]);
            }
        }

        return $this->normalize($this->mock($image), source: 'mock');
    }

    /**
     * Persist the AI result onto an Analysis model.
     */
    public function apply(Analysis $analysis, array $payload): Analysis
    {
        $analysis->fill([
            'status' => 'completed',
            'style_score' => (int) ($payload['style_score'] ?? 0),
            'face_shape' => (string) ($payload['face_shape'] ?? 'oval'),
            'skin_undertone' => (string) ($payload['skin_undertone'] ?? 'neutral'),
            'hairstyles' => $payload['hairstyles'] ?? [],
            'colors' => $payload['colors'] ?? [],
            'outfits' => $payload['outfits'] ?? [],
            'raw_response' => $payload,
            'completed_at' => now(),
        ]);
        $analysis->save();

        return $analysis;
    }

    /** @return array<string, mixed> */
    private function mock(UploadedImage $image): array
    {
        $seed = crc32($image->path.'|'.$image->id);
        mt_srand($seed);

        $shapes = ['oval', 'round', 'square', 'heart', 'oblong'];
        $undertones = ['warm', 'cool', 'neutral'];
        $hairstyles = [
            ['textured quiff', 'mid fade', 'crew cut'],
            ['modern pompadour', 'low fade', 'side part'],
            ['curtain fringe', 'buzz cut', 'taper fade'],
        ];
        $palettes = [
            ['olive', 'camel', 'rust', 'charcoal'],
            ['navy', 'cream', 'sand', 'forest'],
            ['slate', 'oat', 'terracotta', 'ink'],
        ];
        $outfits = [
            ['olive tee + black jeans', 'cream knit + tailored trousers', 'rust overshirt + dark denim'],
            ['charcoal henley + chinos', 'camel coat + white tee', 'navy blazer + grey trousers'],
            ['olive bomber + cargo pants', 'cream oxford + selvedge denim', 'rust flannel + black chinos'],
        ];

        $result = [
            'face_shape' => $shapes[mt_rand(0, count($shapes) - 1)],
            'skin_undertone' => $undertones[mt_rand(0, count($undertones) - 1)],
            'style_score' => mt_rand(62, 94),
            'hairstyles' => $hairstyles[mt_rand(0, count($hairstyles) - 1)],
            'colors' => $palettes[mt_rand(0, count($palettes) - 1)],
            'outfits' => $outfits[mt_rand(0, count($outfits) - 1)],
        ];

        mt_srand();

        return $result;
    }

    /** @return array<string, mixed> */
    private function normalize(mixed $payload, string $source): array
    {
        $payload = is_array($payload) ? $payload : [];

        return [
            'face_shape' => (string) ($payload['face_shape'] ?? 'oval'),
            'skin_undertone' => (string) ($payload['skin_undertone'] ?? 'neutral'),
            'style_score' => (int) ($payload['style_score'] ?? 70),
            'hairstyles' => array_values((array) ($payload['hairstyles'] ?? [])),
            'colors' => array_values((array) ($payload['colors'] ?? [])),
            'outfits' => array_values((array) ($payload['outfits'] ?? [])),
            'source' => $source,
        ];
    }
}
