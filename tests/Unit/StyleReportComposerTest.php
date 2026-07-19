<?php

namespace Tests\Unit;

use App\Models\Analysis;
use App\Services\StyleReportComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StyleReportComposerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_composes_a_report_for_a_completed_analysis(): void
    {
        $analysis = Analysis::factory()->create([
            'face_shape' => 'oval',
            'skin_undertone' => 'warm',
            'hairstyles' => ['textured quiff', 'mid fade'],
            'colors' => ['olive', 'camel', 'rust'],
            'outfits' => ['olive tee + black jeans'],
        ]);

        $report = (new StyleReportComposer)->composeFor($analysis);

        $this->assertSame($analysis->id, $report->analysis_id);
        $this->assertSame($analysis->user_id, $report->user_id);
        $this->assertStringContainsString('oval', $report->face_shape_summary);
        $this->assertStringContainsString('textured quiff', $report->hairstyle_summary);
        $this->assertStringContainsString('olive', $report->color_summary);
        $this->assertStringContainsString('olive tee + black jeans', $report->outfit_summary);
        $this->assertNotEmpty($report->improvement_tips);
    }

    public function test_it_falls_back_to_defaults_for_an_empty_analysis(): void
    {
        $analysis = Analysis::factory()->pending()->create([
            'hairstyles' => [],
            'colors' => [],
            'outfits' => [],
        ]);

        $report = (new StyleReportComposer)->composeFor($analysis);

        $this->assertNotEmpty($report->hairstyle_summary);
        $this->assertNotEmpty($report->color_summary);
        $this->assertNotEmpty($report->outfit_summary);
    }
}
