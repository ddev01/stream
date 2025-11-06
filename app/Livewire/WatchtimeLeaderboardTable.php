<?php

namespace App\Livewire;

use App\Support\TimeFormatter;

class WatchtimeLeaderboardTable extends BaseLeaderboardTable
{
    protected function getStatName(): string
    {
        return 'watchtime';
    }

    protected function getValueColumnTitle(): string
    {
        return 'Watchtime';
    }

    protected function formatValue($value): string
    {
        return TimeFormatter::formatWatchtime((int) $value);
    }
}
