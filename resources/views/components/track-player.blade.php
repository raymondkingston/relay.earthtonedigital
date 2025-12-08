@props([
    'src',
    'title' => null,
    'track' => null,
    'waveform' => null,
])

<div
    x-data="trackPlayer({
        src: @js($src),
        title: @js($title),
    })"
    x-init="init()"
    class="w-full rounded-lg border border-slate-800 bg-slate-800 px-3 py-2 text-slate-100 shadow-sm"
>
    {{-- Hidden native audio element --}}
    <audio x-ref="audio" :src="src" preload="metadata"></audio>

    <div>
        <div class="flex items-center gap-3">
            {{-- Play / Pause button --}}
            <button
                type="button"
                @click="toggle()"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-orange-500 text-slate-900 hover:bg-orange-400 transition"
                :aria-label="playing ? 'Pause' : 'Play'"
            >
                <template x-if="!playing">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5 fill-current">
                        <path d="M7 4.74v14.52c0 .66.7 1.08 1.28.75l10.01-7.26c.5-.29.5-1.21 0-1.5L8.28 3.99C7.7 3.66 7 4.08 7 4.74Z" />
                    </svg>
                </template>
                <template x-if="playing">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5 fill-current">
                        <path d="M7 5.25C6.59 5.25 6.25 5.59 6.25 6v12c0 .41.34.75.75.75h2c.41 0 .75-.34.75-.75V6c0-.41-.34-.75-.75-.75h-2Zm8 0c-.41 0-.75.34-.75.75v12c0 .41.34.75.75.75h2c.41 0 .75-.34.75-.75V6c0-.41-.34-.75-.75-.75h-2Z" />
                    </svg>
                </template>
            </button>

            <div class="flex min-w-0 flex-1 flex-col gap-1">
                {{-- Title + time --}}
                <div class="flex items-baseline justify-between gap-2">
                    <div class="truncate text-sm font-medium" x-text="title || 'Untitled track'"></div>

                    <div class="flex items-center gap-1 text-[11px] font-mono text-slate-400">
                        <span x-text="formatTime(current)"></span>
                        <span class="text-slate-600">/</span>
                        <span x-text="duration ? formatTime(duration) : '--:--'"></span>
                    </div>
                </div>

                {{-- Waveform + scrubber + download --}}
                <div class="flex items-center gap-2">
                    <div class="relative w-full h-8 flex items-center">
                        {{-- Waveform canvas --}}
                        @if ($waveform)
                            <img
                                src="{{ Storage::disk(config('filesystems.default'))->url($waveform) }}"
                                alt=""
                                class="absolute inset-0 w-full h-full object-cover opacity-70 pointer-events-none"
                            >
                        @endif

                        {{-- Scrubber --}}
                        <input
                            x-ref="slider"
                            type="range"
                            min="0"
                            :max="duration || 0"
                            step="0.1"
                            x-model.number="current"
                            @input="onScrub()"
                            class="relative w-full accent-orange-500 bg-transparent"
                        />
                    </div>

                    {{-- Download button --}}
                    <a
                        :href="src"
                        download
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-700 text-slate-300 hover:bg-slate-800 hover:text-orange-300 transition"
                        :aria-label="`Download ${title || 'track'}`"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4">
                            <path
                                fill="currentColor"
                                d="M11 3a1 1 0 0 1 2 0v9.586l2.293-2.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4A1 1 0 0 1 8.707 10.293L11 12.586V3ZM5 17a1 1 0 0 1 1 1v1h12v-1a1 1 0 1 1 2 0v2a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-2a1 1 0 0 1 1-1Z"
                            />
                        </svg>
                    </a>

                    @if($track->notes)
                        <button
                            type="button"
                            @click="showNotes = !showNotes"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-700 text-slate-300 hover:bg-slate-800 hover:text-orange-300 transition"
                        >
                            <x-heroicon-o-document-text class="w-4 h-4 inline stroke-2" />
                            <span class="sr-only">See Notes</span>
                        </button>
                    @endif

                    @auth
                        {{-- @can('update', $track) --}}
                            <a
                                href="{{ route('filament.admin.resources.tracks.edit', $track) }}"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-700 text-slate-300 hover:bg-slate-800 hover:text-orange-300 transition"
                            >
                                <x-heroicon-o-pencil-square class="w-4 h-4 stroke-2 inline" />
                                <span class="sr-only">Edit</span>
                            </a>
                        {{-- @endcan --}}
                    @endauth
                </div>

            </div>
        </div>
    @if($track->notes)
        <div
            x-show="showNotes"
            x-collapse
            class="block mt-2 pb-2 text-sm text-slate-200 whitespace-pre-line md:ml-12 md:pr-10"
        >
            {{ $track->notes }}
        </div>
    @endif
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('trackPlayer', ({ src, title, enableWaveform = true }) => ({
            src,
            title,
            enableWaveform,
            playing: false,
            current: 0,
            duration: 0,
            isScrubbing: false,

            showNotes: false,

            init() {
                const audio = this.$refs.audio;

                audio.addEventListener('loadedmetadata', () => {
                    this.duration = audio.duration || 0;
                });

                audio.addEventListener('timeupdate', () => {
                    if (!this.isScrubbing) {
                        this.current = audio.currentTime || 0;
                    }
                });

                audio.addEventListener('ended', () => {
                    this.playing = false;
                    this.current = 0;
                });
            },

            toggle() {
                const audio = this.$refs.audio;

                if (audio.paused) {
                    audio.play()
                        .then(() => {
                            this.playing = true;
                        })
                        .catch(() => {
                            this.playing = false;
                        });
                } else {
                    audio.pause();
                    this.playing = false;
                }
            },

            onScrub() {
                const audio = this.$refs.audio;

                this.isScrubbing = true;
                audio.currentTime = this.current;

                this.$nextTick(() => {
                    this.isScrubbing = false;
                });
            },

            formatTime(sec) {
                if (!sec || isNaN(sec)) return '0:00';

                const totalSeconds = Math.floor(sec);
                const minutes = Math.floor(totalSeconds / 60);
                const seconds = totalSeconds % 60;

                return `${minutes}:${seconds.toString().padStart(2, '0')}`;
            },
        }));
    });
</script>
