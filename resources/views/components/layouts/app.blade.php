<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ $title ?? 'Earthtone Audio Relay' }}</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Fonts --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Red+Hat+Text:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-950 text-slate-50 min-h-screen flex flex-col lg:font-sans">

        <header class="border-b border-slate-700">
            <div class="max-w-5xl mx-auto flex items-center justify-between px-4 py-4">
                <a href="{{ route('home') }}" class="text-root sm:text-lg font-semibold tracking-tight text-slate-300 hover:text-slate-200">
                    Earthtone Audio Relay
                </a>

                @auth
                    @if(request()->routeIs('projects.show'))
                        @php $project = request()->route('project'); @endphp

                        @if($project)
                            {{-- @can('update', $project) --}}
                                <a
                                    href="{{ route('filament.admin.resources.projects.edit', $project) }}"
                                    class="inline-flex items-center text-sm text-white bg-slate-100/50 hover:bg-white hover:text-orange-600 py-1 px-3 rounded-md transition-colors duration-200"
                                >
                                    Edit<span class="hidden sm:block">&nbsp;Project</span>
                                </a>
                            {{-- @endcan --}}
                        @endif
                    @endif

                    @if(request()->routeIs('artists.show'))
                        @php $artist = request()->route('artist'); @endphp

                        @if($artist)
                            {{-- @can('update', $artist) --}}
                                <a
                                    href="{{ route('filament.admin.resources.artists.edit', $artist) }}"
                                    class="inline-flex items-center text-sm text-white bg-slate-100/50 hover:bg-white hover:text-orange-600 py-1 px-3 rounded-md transition-colors duration-200"
                                >
                                    Edit<span class="hidden sm:block">&nbsp;Artist</span>
                                </a>
                            {{-- @endcan --}}
                        @endif
                    @endif

                    <nav class="flex gap-4 text-sm">
                        <a href="{{ route('artists.index') }}" class="hover:text-orange-500">Artists</a>
                        <a href="{{ route('projects.index') }}" class="hover:text-orange-500">Projects</a>
                    </nav>
                @endauth
            </div>
        </header>

        <main class="flex-1 pb-12">
            <div class="max-w-5xl mx-auto px-4 py-6">
                @isset($pageTitle)
                    <h1 class="text-2xl font-semibold mb-4">{{ $pageTitle }}</h1>
                @endisset

                {{ $slot }}
            </div>
        </main>

        <footer class="border-t border-slate-800 text-xs text-slate-400 pt-4 pb-8">
            <div class="max-w-5xl mx-auto px-4 py-4 flex justify-between">
                <span>© {{ date('Y') }} <a href="https://earthtonedigital.com" class="hover:text-orange-500">Earthtone Digital Lab</a></span>
                <a href="/admin" class="hover:text-orange-500">
                    <x-heroicon-o-cog-8-tooth class="w-5 h-5 inline -mt-1 stroke-2" />
                </a>
            </div>
        </footer>

    </body>
</html>
