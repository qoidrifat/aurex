@extends('layouts.dashboard')

@section('dashboard')
    <div class="mx-auto max-w-3xl">
        <span class="aurex-badge"><x-icon name="scan" class="h-3.5 w-3.5" /> Step 1 of 3 · Upload</span>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Upload your selfie</h1>
        <p class="mt-2 text-sm" style="color: var(--color-cream-dim);">
            Use a well-lit, front-facing photo. Neutral background works best. Glasses off, face unobstructed.
        </p>

        @error('selfie')
            <div class="mt-4 rounded-xl border px-4 py-3 text-sm" style="border-color: rgba(183,65,14,0.4); color: var(--color-rust-soft);">{{ $message }}</div>
        @enderror

        <form method="POST" enctype="multipart/form-data" action="{{ route('analysis.store') }}"
              class="mt-8"
              x-data="aurexUploader()">
            @csrf

            <div @dragover.prevent="dragging = true"
                 @dragleave.prevent="dragging = false"
                 @drop.prevent="onDrop($event)"
                 class="rounded-2xl border-2 border-dashed p-10 text-center transition-colors"
                 :class="dragging ? 'border-[var(--color-rust)] bg-white/5' : ''"
                 style="border-color: var(--color-border); background-color: var(--color-surface);">

                <template x-if="!previewUrl">
                    <div>
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full" style="background-color: rgba(183,65,14,0.12); color: var(--color-rust-soft);">
                            <x-icon name="upload" class="h-7 w-7" />
                        </div>
                        <p class="mt-6 text-lg font-medium">Drop your selfie here</p>
                        <p class="mt-1 text-sm" style="color: var(--color-muted);">or use one of the options below — max 8 MB · JPG, PNG, WEBP</p>

                        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                            <label class="aurex-btn aurex-btn-primary cursor-pointer">
                                <x-icon name="upload" class="h-4 w-4" />
                                Choose file
                                <input type="file" name="selfie" accept="image/*" class="hidden" @change="onFile($event.target.files[0])">
                            </label>
                            <label class="aurex-btn aurex-btn-secondary cursor-pointer">
                                <x-icon name="camera" class="h-4 w-4" />
                                Use camera
                                <input type="file" name="selfie_camera" accept="image/*" capture="user" class="hidden" @change="onFile($event.target.files[0])">
                            </label>
                        </div>
                    </div>
                </template>

                <template x-if="previewUrl">
                    <div class="flex flex-col items-center gap-6">
                        <div class="relative h-56 w-44 overflow-hidden rounded-2xl border" style="border-color: var(--color-border);">
                            <img :src="previewUrl" class="h-full w-full object-cover" alt="Selfie preview">
                            <div class="aurex-scan-line"></div>
                        </div>
                        <div class="w-full max-w-xs">
                            <div class="h-1.5 w-full overflow-hidden rounded-full" style="background-color: var(--color-surface-elevated);">
                                <div class="h-full rounded-full transition-all duration-300" :style="`width: ${progress}%; background: linear-gradient(90deg, #B7410E, #6B8340);`"></div>
                            </div>
                            <p class="mt-2 text-center text-xs" style="color: var(--color-muted);" x-text="progressLabel"></p>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" class="aurex-btn aurex-btn-secondary" @click="reset()">
                                <x-icon name="x" class="h-4 w-4" /> Remove
                            </button>
                            <button type="submit" class="aurex-btn aurex-btn-primary" :disabled="!ready" :class="!ready && 'opacity-50'">
                                <x-icon name="sparkle" class="h-4 w-4" />
                                Start AI Analysis
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </form>

        <div class="mt-10 grid gap-4 md:grid-cols-3">
            @foreach ([
                ['Good lighting', 'Natural light beats overhead fluorescents every time.'],
                ['Neutral background', 'A plain wall helps the AI focus on your face.'],
                ['Front-facing', 'Look straight at the camera, no tilt.'],
            ] as $tip)
                <div class="aurex-card">
                    <p class="aurex-label">Tip</p>
                    <p class="mt-2 font-medium">{{ $tip[0] }}</p>
                    <p class="mt-1 text-sm" style="color: var(--color-cream-dim);">{{ $tip[1] }}</p>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function aurexUploader() {
        return {
            dragging: false,
            previewUrl: null,
            progress: 0,
            progressLabel: 'Ready to upload',
            ready: false,
            onDrop(e) {
                this.dragging = false;
                const file = e.dataTransfer.files[0];
                if (file) this.onFile(file);
            },
            onFile(file) {
                if (!file) return;
                if (!file.type.startsWith('image/')) {
                    this.progressLabel = 'Please select an image.';
                    return;
                }
                const fileInput = this.$el.querySelector('input[name="selfie"]');
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;

                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previewUrl = e.target.result;
                    this.simulateProgress();
                };
                reader.readAsDataURL(file);
            },
            simulateProgress() {
                this.progress = 0;
                this.progressLabel = 'Preparing upload…';
                this.ready = false;
                const step = () => {
                    this.progress = Math.min(100, this.progress + 12);
                    if (this.progress < 100) {
                        this.progressLabel = `Preparing upload… ${this.progress}%`;
                        setTimeout(step, 80);
                    } else {
                        this.progressLabel = 'Ready — click Start AI Analysis';
                        this.ready = true;
                    }
                };
                step();
            },
            reset() {
                this.previewUrl = null;
                this.progress = 0;
                this.ready = false;
                this.progressLabel = 'Ready to upload';
                this.$el.querySelector('input[name="selfie"]').value = '';
            },
        };
    }
</script>
@endpush
