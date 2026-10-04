<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AudioBrowseController;
use App\Http\Controllers\ProjectDownloadController;

Route::get('/', [AudioBrowseController::class, 'home'])->name('home');

Route::get('/artists', [AudioBrowseController::class, 'artistsIndex'])->name('artists.index');
Route::get('/artists/{artist}', [AudioBrowseController::class, 'artistShow'])->name('artists.show');

Route::get('/projects', [AudioBrowseController::class, 'projectsIndex'])->name('projects.index');
Route::get('/projects/{project}', [AudioBrowseController::class, 'projectShow'])->name('projects.show');
Route::get('/projects/{project}/download', ProjectDownloadController::class)->name('projects.download');
