<x-layouts.app :title="$artist->name" :pageTitle="$artist->name">
    <div>
        <div class="flex justify-between items-start">
            <h1 class="text-3xl lg:text-4xl font-semibold mb-6 text-balance">{{ $artist->name }}</h1>
            <x-share-button :artist="$artist" />
        </div>
        <section class="space-y-3">
            <h2 class="text-lg text-slate-100/70 mb-2 font-semibold">Projects</h2>
            <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                @forelse($artist->projects as $project)
                        <x-projects.card :project="$project" />
                @empty
                    <p class="text-sm text-slate-400 mb-4">There are no projects for this artist.</p>
                @endforelse
            </div>

            <x-projects.add />
        </section>
    </div>
</x-layouts.app>
