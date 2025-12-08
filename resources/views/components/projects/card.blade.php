<a href="{{ route('projects.show', $project) }}"
    class="block py-3 mb-2 hover:border-orange-400/70 transition">
        <div class="w-full aspect-square mb-3 flex items-center justify-center bg-slate-600/30 rounded-lg overflow-hidden relative">
            @if($project->cover_art_path)
                <img src="{{ Storage::disk(config('filesystems.default'))->url($project->cover_art_path) }}"
                    alt=""
                    class="aspect-square object-cover"
                />
            @else
                <div class="bg-gray-400/40 flex items-center justify-center text-gray-400 text-xs z-20 text-center">
                </div>
            @endif

            <div class="absolute bottom-1 right-1 text-xs text-slate-100 text-right bg-slate-800/80 px-2 py-1 rounded-md">
                {{ count($project->tracks) }} {{ Str::plural('Track', count($project->tracks)) }}
            </div>
        </div>

        <div class="flex justify-between items-center gap-3">
            <div class="flex items-center gap-3">
                <div>
                    <div class="text-sm lg:text-root font-semibold">
                        {{ $project->title }}
                    </div>
                    <div class="text-xs lg:text-sm text-slate-300">
                        @if(isset($showArtist) && $showArtist && $project->artist)
                            {{ $project->artist->name }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
</a>
