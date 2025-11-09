<?php

namespace App\Livewire;

class TriviaWinsLeaderboardTable extends BaseLeaderboardTable
{
    protected function getStatName(): string
    {
        return 'triviaWins';
    }

    protected function getValueColumnTitle(): string
    {
        return 'Trivia Wins';
    }

    protected function formatValue($value): string
    {
        return number_format((int) $value);
    }
}
