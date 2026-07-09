<?php

namespace App\Filament\Resources\TrackResource\Pages;

use App\Filament\Resources\TrackResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use App\Models\Project;

class CreateTrack extends CreateRecord
{
    protected static string $resource = TrackResource::class;

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $data = [];
        $projectId = request()->integer('project_id');

        if ($projectId && Project::query()->whereKey($projectId)->exists()) {
            $data['project_id'] = $projectId;
        }

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $disk = config('filesystems.default');
        $path = $data['storage_path'] ?? null;

        if ($path) {
            // storage_path is something like "tracks/abcd1234.mp3"
            $data['original_filename'] = basename($path);

            // file size in bytes
            // $data['file_size_bytes'] = Storage::disk($disk)->size($path);

            // file extension as "format"
            $data['format'] = pathinfo($path, PATHINFO_EXTENSION);

            // Phase 2: duration & bitrate via getID3/FFmpeg, if you decide to add that
            // $data['duration_seconds'] = ...
            // $data['bitrate'] = ...
        }

        return $data;
    }
}
