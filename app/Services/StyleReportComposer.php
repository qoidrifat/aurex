<?php

namespace App\Services;

use App\Models\Analysis;
use App\Models\StyleReport;

class StyleReportComposer
{
    /**
     * Build (or refresh) the long-form style report that accompanies an analysis.
     */
    public function composeFor(Analysis $analysis): StyleReport
    {
        $faceShape = $analysis->face_shape ?? 'oval';
        $undertone = $analysis->skin_undertone ?? 'neutral';
        $hairstyles = $analysis->hairstyles ?? [];
        $colors = $analysis->colors ?? [];
        $outfits = $analysis->outfits ?? [];

        $faceSummary = sprintf(
            'Your %s face shape benefits from styles that add balance to your proportions and highlight your strongest features.',
            $faceShape
        );

        $hairSummary = $hairstyles === []
            ? 'Keep your hair clean and intentional — a sharp cut always beats a trendy one.'
            : 'Cuts that complement your face shape include '.$this->joinList($hairstyles).'. Ask your barber for structure on the top and a clean taper on the sides.';

        $colorSummary = $colors === []
            ? 'Stick to muted earth tones and controlled contrast to flatter your complexion.'
            : 'Your '.$undertone.' undertone pairs best with '.$this->joinList($colors).'. Build your wardrobe around one hero color and two supporting neutrals.';

        $outfitSummary = $outfits === []
            ? 'Focus on fit over fabric — a well-fitted basic outperforms a loose designer piece every time.'
            : 'Try rotations like '.$this->joinList($outfits).'. Layer textures rather than colors to create depth without noise.';

        $tips = implode("\n", [
            '• Get a haircut every 3–4 weeks to keep your shape sharp.',
            '• Match your belt to your shoes — small details do heavy lifting.',
            '• Keep a neutral base (black, charcoal, cream) and add one accent color.',
            '• Groom your brows and skin; the face sells the whole outfit.',
        ]);

        return StyleReport::updateOrCreate(
            ['analysis_id' => $analysis->id],
            [
                'user_id' => $analysis->user_id,
                'title' => 'AUREX Style Report',
                'face_shape_summary' => $faceSummary,
                'hairstyle_summary' => $hairSummary,
                'color_summary' => $colorSummary,
                'outfit_summary' => $outfitSummary,
                'improvement_tips' => $tips,
                'is_saved' => true,
            ],
        );
    }

    /** @param array<int, string> $items */
    private function joinList(array $items): string
    {
        $items = array_values(array_filter(array_map('trim', $items)));
        if ($items === []) {
            return '';
        }
        if (count($items) === 1) {
            return $items[0];
        }
        $last = array_pop($items);

        return implode(', ', $items).' and '.$last;
    }
}
