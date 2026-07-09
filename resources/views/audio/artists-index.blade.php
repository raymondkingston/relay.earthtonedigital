<x-layouts.app :title="'Artists'" :pageTitle="'Artists'">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl md:text-3xl font-bold">Artists</h1>
        <x-artists.add />
    </div>
    <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
        @forelse($artists as $artist)
            <a href="{{ route('artists.show', $artist) }}"
               class="aspect-square rounded-md px-4 py-3 transition group flex flex-col justify-between bg-slate-500/50 hover:bg-slate-500/70">
                <div class="font-semibold mb-1 py-3 text-slate-200 group-hover:text-slate-50 leading-tight">
                    {{ $artist->name }}
                </div>
                <div class="text-xs text-slate-400">
                    {{ $artist->projects_count }} {{ Str::plural('Project', $artist->projects_count) }}
                </div>
            </a>
        @empty
            <p class="text-sm text-slate-400">No artists yet.</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $artists->links() }}
    </div>
</x-layouts.app>
