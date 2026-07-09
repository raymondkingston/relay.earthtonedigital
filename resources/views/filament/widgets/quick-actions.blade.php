<x-filament-widgets::widget>
    <x-filament::section>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <x-filament::button
                tag="a"
                href="{{ route('filament.admin.resources.tracks.create') }}"
                icon="heroicon-o-musical-note"
                class="w-full justify-center"
            >
                Add a Track
            </x-filament::button>

            <x-filament::button
                tag="a"
                href="{{ route('filament.admin.resources.projects.create') }}"
                icon="heroicon-o-rectangle-stack"
                class="w-full justify-center"
            >
                Add a Project
            </x-filament::button>

            <x-filament::button
                tag="a"
                href="{{ route('filament.admin.resources.projects.batch-upload') }}"
                icon="heroicon-o-arrow-up-tray"
                class="w-full justify-center"
            >
                Batch Upload
            </x-filament::button>

            <x-filament::button
                tag="a"
                href="{{ route('filament.admin.resources.artists.create') }}"
                icon="heroicon-o-user-group"
                class="w-full justify-center"
            >
                Add an Artist
            </x-filament::button>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>
