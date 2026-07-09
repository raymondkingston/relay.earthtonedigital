<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Artist;
use App\Models\Project;
use App\Models\Track;
use App\Support\TrackBatchUploadParser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BatchUploadProject extends Page
{
    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.resources.project-resource.pages.batch-upload-project';

    protected static ?string $title = 'Batch Upload Project';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'visibility' => 'public',
            'type' => 'show',
            'tracks' => [],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Project')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('artist_id')
                            ->label('Artist')
                            ->options(fn (): array => Artist::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),

                        Forms\Components\TextInput::make('title')
                            ->label('Project Title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Forms\Set $set, Forms\Get $get): void {
                                if ($state && ! $get('slug')) {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('recorded_at')
                            ->label('Date Recorded'),

                        Forms\Components\TextInput::make('type')
                            ->placeholder('show, rehearsal, session, etc.')
                            ->maxLength(255),

                        Forms\Components\Select::make('visibility')
                            ->options([
                                'private' => 'Private',
                                'unlisted' => 'Unlisted',
                                'public' => 'Public',
                            ])
                            ->default('public')
                            ->required(),

                        Forms\Components\TextInput::make('venue')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('city')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Audio Files')
                    ->schema([
                        Forms\Components\FileUpload::make('audio_files')
                            ->label('Audio files')
                            ->helperText('Choose an artist first, then drop the files for one project here. Parsed fields stay editable below.')
                            ->disk(config('filesystems.default'))
                            ->directory(function (Forms\Get $get): string {
                                $artist = Artist::find($get('artist_id'));
                                $artistSlug = $artist?->slug ?? 'artist-'.$get('artist_id');
                                $projectSlug = Str::slug($get('title') ?: 'batch-upload');

                                return "media/{$artistSlug}/{$projectSlug}/audio";
                            })
                            ->acceptedFileTypes([
                                'audio/mpeg',
                                'audio/mp3',
                                'audio/wav',
                                'audio/x-wav',
                                'audio/flac',
                                'audio/*',
                                '.mp3',
                                '.wav',
                                '.flac',
                            ])
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->preserveFilenames()
                            ->storeFileNamesIn('audio_file_names')
                            ->maxSize(config('relay.track_upload_max_kb'))
                            ->visibility('public')
                            ->required()
                            ->disabled(fn (Forms\Get $get): bool => blank($get('artist_id')))
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, mixed $state): void {
                                $filenames = collect($state ?? [])
                                    ->map(fn (mixed $file): ?string => $file instanceof TemporaryUploadedFile
                                        ? $file->getClientOriginalName()
                                        : (is_string($file) ? basename($file) : null))
                                    ->filter()
                                    ->values()
                                    ->all();

                                if ($filenames === []) {
                                    $set('tracks', []);

                                    return;
                                }

                                $parsed = TrackBatchUploadParser::parse($filenames);

                                if (! $get('title') && $parsed['title']) {
                                    $set('title', $parsed['title']);
                                    $set('slug', Str::slug($parsed['title']));
                                }

                                if (! $get('recorded_at') && $parsed['recorded_at']) {
                                    $set('recorded_at', $parsed['recorded_at']);
                                }

                                $set('tracks', collect($parsed['tracks'])
                                    ->mapWithKeys(fn (array $track): array => [(string) Str::uuid() => $track])
                                    ->all());
                            }),
                    ]),

                Forms\Components\Section::make('Tracks')
                    ->schema([
                        Forms\Components\Repeater::make('tracks')
                            ->label('Parsed tracks')
                            ->schema([
                                Forms\Components\Hidden::make('source_filename'),

                                Forms\Components\Placeholder::make('source_filename_display')
                                    ->label('File')
                                    ->content(fn (Forms\Get $get): string => $get('source_filename') ?: 'Audio file')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('track_number')
                                    ->label('Track #')
                                    ->numeric()
                                    ->minValue(1),

                                Forms\Components\TextInput::make('title')
                                    ->label('Track Title')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),

                                Forms\Components\DatePicker::make('recorded_at')
                                    ->label('Date'),

                                Forms\Components\Textarea::make('notes')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->reorderable()
                            ->addable(false)
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? $state['source_filename'] ?? null)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $project = DB::transaction(function () use ($data): Project {
            $project = Project::create([
                'artist_id' => $data['artist_id'],
                'title' => $data['title'],
                'slug' => $this->uniqueProjectSlug($data['slug'] ?: $data['title']),
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? null,
                'recorded_at' => $data['recorded_at'] ?? null,
                'venue' => $data['venue'] ?? null,
                'city' => $data['city'] ?? null,
                'visibility' => $data['visibility'] ?? 'public',
            ]);

            $tracks = array_values($data['tracks'] ?? []);
            $storedNames = $data['audio_file_names'] ?? [];
            $storagePathsByFilename = [];

            foreach ($storedNames as $storagePath => $originalFilename) {
                $storagePathsByFilename[$originalFilename] = $storagePath;
            }

            foreach (array_values($data['audio_files'] ?? []) as $storagePath) {
                $storagePathsByFilename[basename($storagePath)] ??= $storagePath;
            }

            foreach ($tracks as $index => $trackData) {
                $storagePath = $storagePathsByFilename[$trackData['source_filename'] ?? null]
                    ?? array_values($data['audio_files'] ?? [])[$index]
                    ?? null;

                if (! $storagePath) {
                    continue;
                }

                Track::create([
                    'project_id' => $project->id,
                    'title' => $trackData['title'] ?: ($trackData['source_filename'] ?? basename($storagePath)),
                    'track_number' => $trackData['track_number'] ?? null,
                    'recorded_at' => $trackData['recorded_at'] ?? $project->recorded_at,
                    'notes' => $trackData['notes'] ?? null,
                    'storage_path' => $storagePath,
                    'original_filename' => $storedNames[$storagePath] ?? $trackData['source_filename'] ?? basename($storagePath),
                ]);
            }

            return $project;
        });

        Notification::make()
            ->title("Project \"{$project->title}\" created")
            ->body($project->tracks()->count().' tracks uploaded.')
            ->success()
            ->send();

        $this->redirect(ProjectResource::getUrl('edit', ['record' => $project]));
    }

    protected function uniqueProjectSlug(string $value): string
    {
        $slug = Str::slug($value) ?: 'project';
        $candidate = $slug;
        $i = 2;

        while (Project::query()->where('slug', $candidate)->exists()) {
            $candidate = "{$slug}-{$i}";
            $i++;
        }

        return $candidate;
    }
}
