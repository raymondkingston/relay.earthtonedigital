<x-layouts.app :title="'Projects'" :pageTitle="'Projects'">
    <div class="mb-4 flex justify-between items-center">
        <h1 class="text-3xl md:text-3xl font-bold">Projects</h1>
        <x-projects.add />
    </div>
    <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
        @forelse($projects as $project)
            <x-projects.card :project="$project" :showArtist="true" />
        @empty
            <p class="text-sm text-slate-400">No projects yet.</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $projects->links() }}
    </div>
</x-layouts.app>
