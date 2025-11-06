<?php

namespace App\Livewire;

class PointsLeaderboardTable extends BaseLeaderboardTable
{
    protected function getStatName(): string
    {
        return 'points';
    }

    protected function getValueColumnTitle(): string
    {
        return 'Points';
    }

    protected function formatValue($value): string
    {
        return number_format((int) $value);
    }
}
