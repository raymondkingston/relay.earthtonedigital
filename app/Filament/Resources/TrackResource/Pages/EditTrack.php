<?php

namespace App\Filament\Resources\TrackResource\Pages;

use App\Filament\Resources\TrackResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Filament\Pages\Actions\ButtonAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;

class EditTrack extends EditRecord
{
    protected static string $resource = TrackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Save
            Actions\Action::make('save')
                ->label('Save Changes')
                ->action('save')
                ->color('primary'),

            // Cancel back to the *project* edit screen (or index as fallback)
            Actions\Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url(function () {
                    $project = $this->record->project;

                    if ($project) {
                        return route('filament.admin.resources.projects.edit', $project);
                    }

                    // Fallback if somehow no project
                    return route('filament.admin.resources.projects.index');
                }),

            // View the track in its project’s public page
            Actions\ViewAction::make()
                ->label('View in Project')
                ->url(function () {
                    $project = $this->record->project;

                    return $project
                        ? route('projects.show', $project)
                        : route('filament.admin.resources.projects.index');
                }),

            // Delete the track, then redirect back to the project edit page
            Actions\DeleteAction::make()
                ->action(function () {
                    $track = $this->record;
                    $project = $track->project; // grab before delete

                    $trackTitle = $track->title ?? 'Track';
                    $track->delete();

                    Notification::make()
                        ->title("Track \"{$trackTitle}\" deleted")
                        ->success()
                        ->send();

                    if ($project) {
                        return redirect()
                            ->route('filament.admin.resources.projects.edit', $project);
                    }

                    return redirect()
                        ->route('filament.admin.resources.projects.index');
                }),

            // Uncomment if Track uses soft deletes:
            // Actions\ForceDeleteAction::make(),
            // Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $disk = config('filesystems.default');
        $path = $data['storage_path'] ?? null;

        if ($path) {
            // $data['original_filename'] = basename($path);
            // $data['file_size_bytes'] = Storage::disk($disk)->size($path);
            $data['format'] = pathinfo($path, PATHINFO_EXTENSION);
        }

        return $data;
    }
}
