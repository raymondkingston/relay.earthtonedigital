<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class QuickActions extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions';

    protected int|string|array $columnSpan = [
        'sm' => 2,
        'lg' => 3,
    ];
}
