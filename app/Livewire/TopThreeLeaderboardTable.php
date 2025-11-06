<?php

namespace App\Livewire;

class TopThreeLeaderboardTable extends BaseLeaderboardTable
{
    protected function getStatName(): string
    {
        return 'topThreeCount';
    }

    protected function getValueColumnTitle(): string
    {
        return 'Top Three Count';
    }

    protected function formatValue($value): string
    {
        return number_format((int) $value);
    }
}
