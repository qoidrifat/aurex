<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Analysis;
use App\Models\UploadedImage;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index(): View
    {
        $totalUsers = User::count();
        $totalAnalyses = Analysis::count();
        $totalImages = UploadedImage::count();
        $completedAnalyses = Analysis::where('status', 'completed')->count();
        $averageScore = (int) round((float) (Analysis::where('status', 'completed')->avg('style_score') ?? 0));

        $dailyActivity = Analysis::query()
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', Carbon::now()->subDays(13)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'day' => (string) $row->day,
                'total' => (int) $row->total,
            ])
            ->all();

        $recentLogs = ActivityLog::with('user')->latest()->limit(8)->get();

        return view('admin.dashboard', [
            'totalUsers' => $totalUsers,
            'totalAnalyses' => $totalAnalyses,
            'totalImages' => $totalImages,
            'completedAnalyses' => $completedAnalyses,
            'averageScore' => $averageScore,
            'dailyActivity' => $dailyActivity,
            'recentLogs' => $recentLogs,
        ]);
    }

    public function users(Request $request): View
    {
        $query = User::query()->withCount('analyses');

        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(function ($inner) use ($q): void {
                $inner->where('name', 'like', "%$q%")
                    ->orWhere('email', 'like', "%$q%");
            });
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users', [
            'users' => $users,
            'q' => $q,
        ]);
    }

    public function analyses(): View
    {
        $analyses = Analysis::query()
            ->with(['user', 'uploadedImage'])
            ->latest()
            ->paginate(20);

        return view('admin.analyses', [
            'analyses' => $analyses,
        ]);
    }

    public function images(): View
    {
        $images = UploadedImage::query()
            ->with('user')
            ->latest()
            ->paginate(24);

        return view('admin.images', [
            'images' => $images,
        ]);
    }
}
