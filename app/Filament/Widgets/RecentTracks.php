<?php

namespace App\Filament\Widgets;

use App\Models\Track;
use Filament\Tables;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentTracks extends TableWidget
{
    protected int|string|array $columnSpan = [
        'lg' => 1,
    ];

    protected function getTableQuery(): Builder
    {
        return Track::query()
            ->latest()
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('title')
                ->label('Track'),

            Tables\Columns\TextColumn::make('created_at')
                ->since()
                ->label('Added'),
        ];
    }
}
