<x-layouts.app :title="'Earthtone Audio Relay'" :pageTitle="'Audio Projects'">
    <div class="grid gap-8 lg:grid-cols-2">
        <section class="border-b border-slate-700 pb-6 lg:pb-0 lg:border-b-0">
            <div class="flex justify-between items-center pb-4">
                <h2 class="text-2xl font-semibold">
                    <a href="{{ route('projects.index') }}" class="flex items-center hover:text-slate-200">
                        Recent Projects
                        <x-heroicon-o-chevron-right class="w-5 h-5 inline-block ml-2 mt-0.5 stroke-2" />
                    </a>
                </h2>
                <x-projects.add />
            </div>
            <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-3">
                @forelse($projects as $project)
                    <x-projects.card :project="$project" showArtist />
                @empty
                    <p class="text-sm text-slate-400">No projects yet.</p>
                @endforelse
            </div>
        </section>

        <section class="lg:border-l lg:border-slate-700 lg:pl-6 lg:pb-0">
            <div class="flex justify-between items-center pb-6">
                <h2 class="text-2xl font-semibold">
                    <a href="{{ route('artists.index') }}" class="flex items-center hover:text-slate-200">
                        Recent Artists
                        <x-heroicon-o-chevron-right class="w-5 h-5 inline-block ml-2 mt-0.5 stroke-2" />
                    </a>
                </h2>
                <x-artists.add />
            </div>
            <div class="flex flex-wrap gap-2">
                @forelse($artists as $artist)
                    <a href="{{ route('artists.show', $artist) }}"
                       class="border border-slate-700 rounded-full px-3 py-1 text-root hover:border-orange-700 transition duration-200">
                        {{ $artist->name }}
                    </a>
                @empty
                    <p class="text-sm text-slate-400">No artists yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
