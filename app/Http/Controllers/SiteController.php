<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class SiteController extends Controller
{
    public function landing(): View
    {
        $features = [
            [
                'icon' => 'scan',
                'title' => 'AI Face Analysis',
                'body' => 'Our vision model reads your facial structure, proportions, and skin undertone in seconds.',
            ],
            [
                'icon' => 'scissors',
                'title' => 'Hairstyle Recommendations',
                'body' => 'Get cuts matched to your face shape — not just whatever is trending on TikTok.',
            ],
            [
                'icon' => 'palette',
                'title' => 'Color Palette Detection',
                'body' => 'Know which colors elevate your complexion so every outfit looks intentional.',
            ],
            [
                'icon' => 'shirt',
                'title' => 'Outfit Suggestions',
                'body' => 'Curated looks built from your palette and silhouette — no more guesswork in the morning.',
            ],
        ];

        $testimonials = [
            [
                'name' => 'Dimas R.',
                'role' => 'Student, 22',
                'quote' => 'I uploaded one selfie and AUREX told me exactly why my go-to haircut looked off. Switched to a mid fade and the difference is insane.',
            ],
            [
                'name' => 'Arief M.',
                'role' => 'Product Designer, 27',
                'quote' => 'The color palette report alone is worth it. I finally stopped buying clothes that washed me out.',
            ],
            [
                'name' => 'Kenta S.',
                'role' => 'Creator, 24',
                'quote' => 'Feels like having a stylist in your pocket. The outfit suggestions are actually wearable.',
            ],
        ];

        return view('site.landing', [
            'features' => $features,
            'testimonials' => $testimonials,
        ]);
    }
}
