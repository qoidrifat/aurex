<?php

use App\Models\Analysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
| Sanctum-protected endpoints for mobile clients or partner integrations.
| All routes are prefixed with /api by the framework.
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::get('/analyses', function (Request $request) {
        return $request->user()
            ->analyses()
            ->with(['uploadedImage', 'recommendations'])
            ->latest()
            ->paginate(20);
    });

    Route::get('/analyses/{analysis}', function (Request $request, Analysis $analysis) {
        abort_unless($analysis->user_id === $request->user()->id, 403);

        return $analysis->load(['uploadedImage', 'recommendations', 'styleReport']);
    });
});
