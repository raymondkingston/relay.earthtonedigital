<?php

namespace App\Filament\Resources\ArtistResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use App\Models\Project;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class ProjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'projects';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')->required(),
                Forms\Components\TextInput::make('slug'),
                // whatever minimal fields you want inline here
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_art_path')
                    ->label('Image')
                    ->disk(config('filesystems.default'))
                    ->checkFileExistence(false)
                    ->height(60)
                    ->square()
                    ->extraImgAttributes([
                        'class' => 'rounded-md',
                    ]),
                Tables\Columns\TextColumn::make('title')->searchable()->label('Project Title'),
                Tables\Columns\TextColumn::make('created_at')->date()->label('Date Created'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Project $record): string =>
                        route('filament.admin.resources.projects.edit', $record)
                    )
                    ->openUrlInNewTab(false),

                Tables\Actions\DeleteAction::make()
                    ->label('Delete')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function (Project $record) {
                        $title = $record->title;

                        $record->delete();

                        Notification::make()
                            ->title("Project \"{$title}\" deleted")
                            ->success()
                            ->send();
                    }),
                ]);
    }
}
