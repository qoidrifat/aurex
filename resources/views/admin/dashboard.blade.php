@extends('layouts.dashboard')

@section('dashboard')
    <div class="flex items-center justify-between">
        <div>
            <span class="aurex-badge">Admin</span>
            <h1 class="mt-4 text-3xl font-semibold tracking-tight">Overview</h1>
            <p class="mt-1 text-sm" style="color: var(--color-muted);">Snapshot of platform activity.</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total users" :value="$totalUsers" icon="user" />
        <x-stat-card label="Total analyses" :value="$totalAnalyses" icon="scan" :trend="$completedAnalyses.' completed'" />
        <x-stat-card label="Uploaded images" :value="$totalImages" icon="image" />
        <x-stat-card label="Average score" :value="$averageScore ?: '—'" icon="star" />
    </div>

    <div class="mt-10 grid gap-6 lg:grid-cols-[2fr_1fr]">
        <div class="aurex-card">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Daily activity (last 14 days)</h2>
                <a href="{{ route('admin.analyses') }}" class="text-xs aurex-link">View all →</a>
            </div>

            @php
                $max = max(array_column($dailyActivity, 'total') ?: [1]);
            @endphp

            @if (empty($dailyActivity))
                <p class="mt-6 text-sm" style="color: var(--color-muted);">No activity yet.</p>
            @else
                <div class="mt-6 flex items-end gap-2">
                    @foreach ($dailyActivity as $d)
                        @php($h = (int) max(4, round(($d['total'] / $max) * 140)))
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <div class="w-full rounded-md" style="height: {{ $h }}px; background: linear-gradient(180deg, #B7410E, #6B8340);"></div>
                            <span class="text-[10px]" style="color: var(--color-muted);">{{ \Illuminate\Support\Carbon::parse($d['day'])->format('M j') }}</span>
                            <span class="text-[10px] font-medium">{{ $d['total'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="aurex-card">
            <h2 class="text-lg font-semibold">Recent activity</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @forelse ($recentLogs as $log)
                    <li class="flex items-start gap-3">
                        <div class="mt-1 h-1.5 w-1.5 rounded-full" style="background-color: var(--color-rust);"></div>
                        <div>
                            <p class="font-medium">{{ $log->action }}</p>
                            <p class="text-xs" style="color: var(--color-muted);">
                                {{ $log->user?->email ?? 'system' }} · {{ $log->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </li>
                @empty
                    <li class="text-sm" style="color: var(--color-muted);">No activity yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
