<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Tables;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\ImageColumn;

class RecentProjects extends TableWidget
{
    protected int|string|array $columnSpan = [
        'lg' => 1,
    ];

    // 👇 Add the return type + import above
    protected function getTableQuery(): Builder
    {
        return Project::query()
            ->latest()
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            ImageColumn::make('cover_art_path')
                ->label('Image')
                ->disk(config('filesystems.default'))
                ->height(30)
                ->square()
                ->extraImgAttributes([
                    'class' => 'rounded-md',
                ]),
            Tables\Columns\TextColumn::make('title')
                ->label('Project'),

            Tables\Columns\TextColumn::make('created_at')
                ->since()
                ->label('Added'),
        ];
    }
}
