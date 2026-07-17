@extends('layouts.dashboard')

@section('dashboard')
    <h1 class="text-3xl font-semibold tracking-tight">Settings</h1>
    <p class="mt-1 text-sm" style="color: var(--color-muted);">Control notifications and manage your subscription.</p>

    @php($prefs = (array) ($user->preferences ?? []))

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('profile.preferences') }}" class="aurex-card space-y-4">
            @csrf
            @method('PUT')
            <h2 class="text-lg font-semibold">Notifications</h2>

            <label class="flex items-start gap-3">
                <input type="checkbox" name="notify_analysis_complete" value="1" class="mt-1 h-4 w-4 rounded border" style="border-color: var(--color-border); background-color: var(--color-surface-elevated);" @checked(!empty($prefs['notify_analysis_complete']))>
                <div>
                    <p class="text-sm font-medium">Analysis complete</p>
                    <p class="text-xs" style="color: var(--color-muted);">Get an email when your AI run finishes.</p>
                </div>
            </label>

            <label class="flex items-start gap-3">
                <input type="checkbox" name="notify_product_updates" value="1" class="mt-1 h-4 w-4 rounded border" style="border-color: var(--color-border); background-color: var(--color-surface-elevated);" @checked(!empty($prefs['notify_product_updates']))>
                <div>
                    <p class="text-sm font-medium">Product updates</p>
                    <p class="text-xs" style="color: var(--color-muted);">Monthly digest of new AUREX features.</p>
                </div>
            </label>

            <div class="flex justify-end">
                <button class="aurex-btn aurex-btn-primary" type="submit">Save preferences</button>
            </div>
        </form>

        <div class="aurex-card overflow-hidden" style="background: linear-gradient(135deg, rgba(183,65,14,0.18), rgba(85,107,47,0.18)), var(--color-surface);">
            <span class="aurex-badge">Subscription</span>
            <h2 class="mt-4 text-2xl font-semibold">Upgrade to AUREX Pro</h2>
            <p class="mt-2 text-sm" style="color: var(--color-cream-dim);">
                Unlimited analyses, high-resolution PDF reports, early access to new models, and priority AI queue.
            </p>
            <ul class="mt-5 space-y-2 text-sm" style="color: var(--color-cream-dim);">
                <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4" style="color: var(--color-olive-soft);" /> Unlimited analyses</li>
                <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4" style="color: var(--color-olive-soft);" /> Priority AI queue</li>
                <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4" style="color: var(--color-olive-soft);" /> Exportable PDF reports</li>
                <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4" style="color: var(--color-olive-soft);" /> Early access models</li>
            </ul>
            <div class="mt-6 flex items-center gap-3">
                <button disabled class="aurex-btn aurex-btn-primary opacity-80" title="Billing integration is coming soon">
                    Upgrade ($9/mo)
                </button>
                <span class="text-xs" style="color: var(--color-muted);">Billing coming soon</span>
            </div>
        </div>
    </div>
@endsection
