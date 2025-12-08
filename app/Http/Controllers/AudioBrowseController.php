<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Project;
use Illuminate\Http\Request;

class AudioBrowseController extends Controller
{
    public function home()
    {
        $artists = Artist::with(['projects' => function ($q) {
            $q->latest('recorded_at')->take(3);
        }])->orderBy('name')->get();

        $projects = Project::with('artist')
            ->latest('recorded_at')
            ->take(10)
            ->get();

        return view('audio.home', [
            'artists'  => $artists,
            'projects' => $projects,
        ]);
    }

    public function artistsIndex()
    {
        $artists = Artist::orderBy('name')->paginate(24);

        return view('audio.artists-index', [
            'artists'   => $artists,
        ]);
    }

    public function artistShow(Artist $artist)
    {
        $artist->load(['projects' => function ($q) {
            $q->orderByDesc('recorded_at');
        }]);

        return view('audio.artist-show', [
            'artist'   => $artist,
        ]);
    }

    public function projectsIndex()
    {
        $projects = Project::with('artist')
            ->latest('recorded_at')
            ->paginate(24);

        return view('audio.projects-index', [
            'projects' => $projects,
        ]);
    }

    public function projectShow(Project $project)
    {
        $project->load(['artist', 'tracks' => function ($q) {
            $q->orderBy('track_number')->orderBy('id');
        }]);

        return view('audio.project-show', [
            'project' => $project,
        ]);
    }
}
