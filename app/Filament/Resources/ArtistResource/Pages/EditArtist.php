<?php

namespace App\Filament\Resources\ArtistResource\Pages;

use App\Filament\Resources\ArtistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Pages\Actions\ButtonAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;

class EditArtist extends EditRecord
{
    protected static string $resource = ArtistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Save
            Actions\Action::make('save')
                ->label('Save Changes')
                ->action('save')
                ->color('primary'),

            // Cancel back to artists list
            Actions\Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url(fn () => route('filament.admin.resources.artists.index')),

            // View public artist page
            Actions\ViewAction::make()
                ->label('View Artist')
                ->url(fn () => route('artists.show', $this->record)),

            // Delete, then redirect back to artists index
            Actions\DeleteAction::make()
                ->action(function () {
                    $artist = $this->record;

                    $artistName = $artist->name ?? 'Artist';
                    $artist->delete();

                    Notification::make()
                        ->title("{$artistName} deleted")
                        ->success()
                        ->send();

                    return redirect()
                        ->route('filament.admin.resources.artists.index');
                }),

            // Uncomment these if Artist uses SoftDeletes:
            // Actions\ForceDeleteAction::make(),
            // Actions\RestoreAction::make(),
        ];
    }

}
