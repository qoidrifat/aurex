@extends('layouts.dashboard')

@section('dashboard')
    <div class="mx-auto max-w-2xl text-center">
        <span class="aurex-badge"><x-icon name="scan" class="h-3.5 w-3.5" /> Step 2 of 3 · Analyzing</span>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Analyzing your facial features…</h1>
        <p class="mt-2 text-sm" style="color: var(--color-cream-dim);">
            Our AI is mapping your proportions, undertone, and contrast profile. This usually takes a few seconds.
        </p>

        <div class="relative mx-auto mt-12 aspect-[4/5] w-full max-w-sm overflow-hidden rounded-3xl border" style="border-color: var(--color-border); background-color: var(--color-surface);">
            <img src="{{ $analysis->uploadedImage->url() }}" alt="Your selfie" class="h-full w-full object-cover opacity-90">
            <div class="pointer-events-none absolute inset-0" style="background: linear-gradient(180deg, rgba(15,15,15,0.1), rgba(15,15,15,0.55));"></div>
            <div class="aurex-scan-line"></div>

            <svg class="pointer-events-none absolute inset-0 h-full w-full" viewBox="0 0 100 120" preserveAspectRatio="none">
                <g stroke="#B7410E" stroke-width="0.4" opacity="0.5" fill="none">
                    <line x1="0" y1="30" x2="100" y2="30"/>
                    <line x1="0" y1="60" x2="100" y2="60"/>
                    <line x1="0" y1="90" x2="100" y2="90"/>
                    <line x1="25" y1="0" x2="25" y2="120"/>
                    <line x1="50" y1="0" x2="50" y2="120"/>
                    <line x1="75" y1="0" x2="75" y2="120"/>
                </g>
            </svg>
        </div>

        <div class="mx-auto mt-10 max-w-md"
             x-data="aurexProcessing({ runUrl: '{{ route('analysis.run', $analysis) }}', csrf: '{{ csrf_token() }}' })"
             x-init="start()">
            <div class="h-1.5 w-full overflow-hidden rounded-full" style="background-color: var(--color-surface-elevated);">
                <div class="h-full transition-all duration-300" :style="`width: ${progress}%; background: linear-gradient(90deg, #B7410E, #6B8340);`"></div>
            </div>
            <p class="mt-3 text-xs" style="color: var(--color-muted);" x-text="status"></p>

            <template x-if="error">
                <div class="mt-6 rounded-xl border px-4 py-3 text-sm" style="border-color: rgba(183,65,14,0.4); color: var(--color-rust-soft);" x-text="error"></div>
            </template>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function aurexProcessing({ runUrl, csrf }) {
        return {
            progress: 4,
            error: null,
            status: 'Warming up the model…',
            stages: [
                [10, 'Detecting face landmarks…'],
                [25, 'Measuring proportions…'],
                [45, 'Estimating skin undertone…'],
                [65, 'Matching hairstyles…'],
                [82, 'Selecting a color palette…'],
                [94, 'Finalizing your style report…'],
            ],
            start() {
                let i = 0;
                const tick = () => {
                    if (i < this.stages.length && !this.error) {
                        const [p, s] = this.stages[i++];
                        this.progress = p;
                        this.status = s;
                        setTimeout(tick, 450 + Math.random() * 350);
                    }
                };
                tick();

                fetch(runUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }).then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(data.message || 'Analysis failed.');
                    this.progress = 100;
                    this.status = 'Done — redirecting…';
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 500);
                }).catch((e) => {
                    this.error = e.message || 'Something went wrong. Please try again.';
                });
            },
        };
    }
</script>
@endpush
