<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\QuickActions;
use App\Filament\Widgets\RecentTracks;
use App\Filament\Widgets\RecentProjects;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            QuickActions::class,
            RecentTracks::class,
            RecentProjects::class,
        ];
    }
}
