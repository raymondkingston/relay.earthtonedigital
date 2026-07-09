<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrackResource\Pages;
use App\Filament\Resources\TrackResource\RelationManagers;
use App\Models\Track;
use App\Models\Project;
use App\Support\TrackFilenameParser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TrackResource extends Resource
{
    protected static ?string $model = Track::class;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('project_id')
                    ->relationship('project', 'title')
                    ->label('Project')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\Select::make('artist_id')
                            ->relationship('artist', 'name')
                            ->label('ProjectArtist')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $state, callable $set, callable $get) {
                                        if (! $get('slug')) {
                                            $set('slug', Str::slug($state));
                                        }
                                    }),

                                Forms\Components\TextInput::make('slug')
                                    ->label('Artist Slug')
                                    ->required()
                                    ->maxLength(255),
                            ]),

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
                            ->label('Project Slug')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Project Description')
                            ->rows(3)
                            ->nullable(),

                        Forms\Components\TextInput::make('type')
                            ->placeholder('show, rehearsal, session, etc.')
                            ->maxLength(255)
                            ->nullable(),

                        Forms\Components\DatePicker::make('recorded_at')
                            ->label('Date Recorded')
                            ->nullable(),

                        Forms\Components\TextInput::make('venue')
                            ->maxLength(255)
                            ->nullable(),

                        Forms\Components\TextInput::make('city')
                            ->maxLength(255)
                            ->nullable(),

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
                    ]),

                Forms\Components\FileUpload::make('storage_path')
                    ->label('Audio file')
                    ->disk(config('filesystems.default'))
                    ->directory(function (Forms\Get $get, ?Track $record) {
                        $project = $record?->project;

                        if (! $project && $get('project_id')) {
                            $project = Project::with('artist')->find($get('project_id'));
                        }

                        if (! $project || ! $project->artist) {
                            return 'media/_unassigned/audio';
                        }

                        $artistSlug = $project->artist->slug ?? 'artist-'.$project->artist_id;
                        $projectSlug = $project->slug ?? 'project-'.$project->id;

                        return "media/{$artistSlug}/{$projectSlug}/audio";
                    })
                    ->acceptedFileTypes([
                        'audio/mpeg',   // mp3
                        'audio/mp3',    // some user agents report this
                        'audio/wav',
                        'audio/x-wav',
                        'audio/flac',
                        'audio/*',      // keep as a catch-all
                        '.mp3',         // explicit extensions for iOS
                        '.wav',
                        '.flac',
                    ])
                    ->maxSize(config('relay.track_upload_max_kb'))
                    ->preserveFilenames()
                    ->visibility('public')
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, mixed $state): void {
                        $filename = $state instanceof TemporaryUploadedFile
                            ? $state->getClientOriginalName()
                            : (is_string($state) ? basename($state) : null);

                        if (! $filename) {
                            return;
                        }

                        $parsed = TrackFilenameParser::parse($filename);

                        if (! $get('track_number') && $parsed['track_number']) {
                            $set('track_number', $parsed['track_number']);
                        }

                        if (! $get('title') && $parsed['title']) {
                            $set('title', $parsed['title']);
                        }

                        if (! $get('recorded_at') && $parsed['recorded_at']) {
                            $set('recorded_at', $parsed['recorded_at']);
                        }

                        if (! $get('notes') && $parsed['notes']) {
                            $set('notes', $parsed['notes']);
                        }
                    }),

                Forms\Components\TextInput::make('title')
                    ->label('Track Title')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('track_number')
                    ->numeric()
                    ->minValue(1)
                    ->nullable(),

                Forms\Components\DatePicker::make('recorded_at')
                    ->label('Date Recorded')
                    ->nullable(),

                Forms\Components\Textarea::make('notes')
                    ->rows(3)
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('track_number')
                    ->numeric(),
                Tables\Columns\TextColumn::make('recorded_at')
                    ->label('Date')
                    ->date()
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTracks::route('/'),
            'create' => Pages\CreateTrack::route('/create'),
            'edit' => Pages\EditTrack::route('/{record}/edit'),
        ];
    }
}
