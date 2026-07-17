<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $recent = $user->analyses()
            ->with('uploadedImage')
            ->latest()
            ->limit(4)
            ->get();

        $completed = $user->analyses()->where('status', 'completed');

        $averageScore = (int) round((float) ($completed->clone()->avg('style_score') ?? 0));
        $latest = $completed->clone()->latest('completed_at')->first();
        $totalAnalyses = $user->analyses()->count();

        return view('dashboard.index', [
            'user' => $user,
            'recent' => $recent,
            'averageScore' => $averageScore,
            'latest' => $latest,
            'totalAnalyses' => $totalAnalyses,
        ]);
    }
}
