<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('artist_id')
                    ->relationship('artist', 'name')
                    ->required()
                    ->searchable(),

                Forms\Components\TextInput::make('title')
                    ->label('Project Title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $state, callable $set, callable $get) {
                        if (! $get('slug')) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->rows(4)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('type')
                    ->placeholder('show, rehearsal, session, etc.')
                    ->maxLength(255),

                Forms\Components\DatePicker::make('recorded_at')
                    ->label('Date Recorded'),

                Forms\Components\TextInput::make('venue')
                    ->maxLength(255),

                Forms\Components\TextInput::make('city')
                    ->maxLength(255),

                Forms\Components\FileUpload::make('cover_art_path')
                    ->label('Project Image')
                    ->disk(config('filesystems.default')) // optional, default anyway
                    ->directory(function (?Project $record) {
                        // Safety: if no record yet (on create), just drop in a generic bucket
                        if (! $record || ! $record->artist) {
                            return 'media/_unassigned/projects';
                        }

                        $artistSlug = $record->artist->slug ?? 'artist-'.$record->artist_id;
                        $projectSlug = $record->slug ?? 'project-'.$record->id;

                        return "media/{$artistSlug}/{$projectSlug}/images";
                    })
                    ->image()
                    ->acceptedFileTypes(
                        [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif'
                        ]
                    )
                    ->visibility('public'),

                Forms\Components\Select::make('visibility')
                    ->options([
                        'private'  => 'Private',
                        'unlisted' => 'Unlisted',
                        'public'   => 'Public',
                    ])
                    ->default('public')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('artist.name')
                    ->label('Artist')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Project')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('recorded_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('visibility')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TracksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'batch-upload' => Pages\BatchUploadProject::route('/batch-upload'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
