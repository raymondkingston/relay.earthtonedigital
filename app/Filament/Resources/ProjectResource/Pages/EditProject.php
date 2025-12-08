<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Pages\Actions\ButtonAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Save
            Actions\Action::make('save')
                ->label('Save Changes')
                ->action('save')
                ->color('primary'),

            // Cancel back to projects list
            Actions\Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url(fn () => route('filament.admin.resources.projects.index')),

            // View public project page
            Actions\ViewAction::make()
                ->label('View Project')
                ->url(fn () => route('projects.show', $this->record)),

            // Delete, then redirect back to projects index
            Actions\DeleteAction::make()
                ->action(function () {
                    $project = $this->record;

                    $projectTitle = $project->title;
                    $project->delete();

                    Notification::make()
                        ->title("Project \"{$projectTitle}\" deleted")
                        ->success()
                        ->send();

                    return redirect()
                        ->route('filament.admin.resources.projects.index');
                }),

            // Uncomment these if Project uses SoftDeletes:
            // Actions\ForceDeleteAction::make(),
            // Actions\RestoreAction::make(),
        ];
    }

}
