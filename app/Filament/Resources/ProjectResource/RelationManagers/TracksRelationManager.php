<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use App\Models\Track;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Filament\Resources\TrackResource;

class TracksRelationManager extends RelationManager
{
    protected static string $relationship = 'tracks';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')->required(),
                Forms\Components\TextInput::make('track_number')->numeric(),
                // maybe the FileUpload for audio if you want inline creation
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('track_number')->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable(),
            ])
            ->defaultSort('track_number')
            ->headerActions([
                Tables\Actions\Action::make('createTrack')
                    ->label('New Track')
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => TrackResource::getUrl('create', [
                        'project_id' => $this->getOwnerRecord()->getKey(),
                    ])),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Track $record): string =>
                        route('filament.admin.resources.tracks.edit', $record)
                    )
                    ->openUrlInNewTab(false),

                Tables\Actions\DeleteAction::make()
                    ->label('Delete')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function (Track $record) {
                        $title = $record->title;

                        $record->delete();

                        Notification::make()
                            ->title("Track \"{$title}\" deleted")
                            ->success()
                            ->send();
                    }),
                ]);
    }
}
