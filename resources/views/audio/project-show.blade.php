<x-layouts.app :title="$project->title" :pageTitle="$project->title">
    <section class="mb-6">
        <div
            x-data="{ shrink: false }"
            x-init="shrink = window.scrollY > 10"
            x-on:scroll.window="shrink = window.scrollY > 10"
            class="sm:flex lg:items-end pt-4 sm:pt-0"
        >
            <div
                class="
                    relative w-full flex-shrink-0 mx-auto sm:ml-0 sm:mr-4
                    sm:w-56 sm:h-56
                    transition-all duration-700 ease-out
                "
                :class="shrink ? 'max-w-36 max-h-36' : 'max-w-72 max-h-72'"
            >
                @if($project->cover_art_path)
                    <img
                        src="{{ Storage::disk(config('filesystems.default'))->url($project->cover_art_path) }}"
                        alt=""
                        class="aspect-square object-cover z-20 rounded-lg"
                    />
                @else
                    <div class="relative aspect-square bg-gray-500/40 flex items-center justify-center text-gray-400 text-sm z-20 rounded-lg">
                        No Image
                    </div>
                @endif

                <div class="absolute top-2 right-2">
                    <x-share-button :project="$project" />
                </div>
            </div>

            <div class="md:pr-12 lg:pr-0">
                <h1 class="text-center sm:text-left text-2xl md:text-4xl font-semibold text-balance pt-4 sm:pt-0 mb-1">{{ $project->title }}</h1>
                <a href="{{ route('artists.show', $project->artist) }}" class="block text-orange-500 hover:text-orange-600 text-balance text-center sm:text-left text-lg font-medium">
                    {{ $project->artist->name }}
                </a>
                @if($project->description)
                    <p class="mt-4 text-sm text-slate-200">
                        {{ $project->description }}
                    </p>
                @endif
                <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-400 pt-1">
                    @if($project->type)
                        <span>{{ ucfirst($project->type) }}</span>
                    @endif
                    @if($project->type && $project->recorded_at) &bull; @endif
                    @if($project->recorded_at)
                        <span>{{ $project->recorded_at->format('M j, Y') }}</span>
                    @endif
                    @if($project->recorded_at && ($project->venue || $project->city)) &bull; @endif
                    @if($project->venue || $project->city)
                        <span>
                            {{ $project->venue }}
                            @if($project->venue && $project->city), @endif
                            {{ $project->city }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

    </section>

    <section class="mb-12">
        <h2 class="text-lg font-semibold mb-3">Tracks</h2>

        @if($project->tracks->isEmpty())
            <p class="text-sm text-slate-400">No tracks yet.</p>
        @else
            <div class="space-y-3">
                @foreach($project->tracks as $track)
                    @php
                        $audioUrl = Storage::disk(config('filesystems.default'))
                            ->url($track->storage_path);
                    @endphp

                    <div class="mb-4">
                        <x-track-player
                            :src="$audioUrl"
                            :title="$track->title"
                            :track="$track"
                            :waveform="$track->waveform_image_path"
                            :enable-waveform="$track->duration_seconds && $track->duration_seconds < 900"
                        />
                    </div>
                @endforeach
            </div>
        @endif
        <hr class="my-6 border-slate-800" />
        <p class="text-xs text-slate-500">Visibility: {{ ucfirst($project->visibility) }}</p>
    </section>
</x-layouts.app>
