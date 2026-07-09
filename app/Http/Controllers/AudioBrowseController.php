<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Project;
use Illuminate\Http\Request;

class AudioBrowseController extends Controller
{
    public function home(Request $request)
    {
        $artists = Artist::with(['projects' => function ($q) use ($request) {
            if (! $request->user()) {
                $q->where('visibility', 'public');
            }

            $q->latest('recorded_at')->take(3);
        }])->orderBy('name')->get();

        $projects = Project::with('artist')
            ->when(! $request->user(), fn ($q) => $q->where('visibility', 'public'))
            ->latest('recorded_at')
            ->take(10)
            ->get();

        return view('audio.home', [
            'artists'  => $artists,
            'projects' => $projects,
        ]);
    }

    public function artistsIndex(Request $request)
    {
        $artists = Artist::withCount(['projects' => function ($q) use ($request) {
            if (! $request->user()) {
                $q->where('visibility', 'public');
            }
        }])
            ->orderBy('name')
            ->paginate(24);

        return view('audio.artists-index', [
            'artists'   => $artists,
        ]);
    }

    public function artistShow(Request $request, Artist $artist)
    {
        $canSeePrivateProjects = $request->user() || $artist->hasValidShareKey($request);

        $artist->load(['projects' => function ($q) use ($canSeePrivateProjects) {
            if (! $canSeePrivateProjects) {
                $q->where('visibility', 'public');
            }

            $q->orderByDesc('recorded_at');
        }]);

        return view('audio.artist-show', [
            'artist'   => $artist,
        ]);
    }

    public function projectsIndex(Request $request)
    {
        $projects = Project::with('artist')
            ->when(! $request->user(), fn ($q) => $q->where('visibility', 'public'))
            ->latest('recorded_at')
            ->paginate(24);

        return view('audio.projects-index', [
            'projects' => $projects,
        ]);
    }

    public function projectShow(Request $request, Project $project)
    {
        $project->load('artist');

        abort_unless($project->isVisibleTo($request), 404);

        $project->load(['artist', 'tracks' => function ($q) {
            $q->orderBy('track_number')->orderBy('id');
        }]);

        return view('audio.project-show', [
            'project' => $project,
        ]);
    }
}
