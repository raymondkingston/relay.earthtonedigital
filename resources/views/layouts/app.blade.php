<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Earthtone Audio Relay' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ config('app.fonts') }}" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-50 min-h-screen flex flex-col font-sans">

    <header class="border-b border-slate-800">
        <div class="max-w-5xl mx-auto flex items-center justify-between px-4 py-4">
            @auth
            <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight">
            @endauth
                Earthtone Audio Relay
            @auth
            </a>
            @endauth

            @auth
                @if(request()->routeIs('projects.show'))
                    {{-- @can('update', $project) --}}
                        <a
                            href="{{ route('filament.admin.resources.projects.edit', $project) }}"
                            class="inline-flex items-center text-xs text-slate-300 hover:text-orange-300 underline mr-2 pt-1"
                        >
                            Edit Project
                        </a>
                    {{-- @endcan --}}
                @endif

                @if(request()->routeIs('artists.show'))
                    {{-- @can('update', $artist) --}}
                        <a
                            href="{{ route('filament.admin.resources.artists.edit', $artist) }}"
                            class="inline-flex items-center text-xs text-slate-300 hover:text-orange-300 underline mr-2 pt-1"
                        >
                            Edit Artist
                        </a>
                    {{-- @endcan --}}
                @endif

            <nav class="flex gap-4 text-sm">
                <a href="{{ route('artists.index') }}" class="hover:text-orange-300">Artists</a>
                <a href="{{ route('projects.index') }}" class="hover:text-orange-300">Projects</a>
            </nav>
            @endauth
        </div>
    </header>

    <main class="flex-1">
        <div class="max-w-5xl mx-auto px-4 py-6">
            @isset($pageTitle)
                <h1 class="text-2xl font-semibold mb-4">{{ $pageTitle }}</h1>
            @endisset

            {{ $slot }}
        </div>
    </main>

    <footer class="border-t border-slate-800 text-xs text-slate-400">
        <div class="max-w-5xl mx-auto px-4 py-4 flex justify-between">
            <span>© {{ date('Y') }} Earthtone Digital Lab</span>
        </div>
    </footer>

</body>
</html>
