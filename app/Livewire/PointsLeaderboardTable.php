<?php

namespace App\Livewire;

use App\Models\TwitchUserStat;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class PointsLeaderboardTable extends DataTableComponent
{
    protected $model = TwitchUserStat::class;

    public int $rankCounter = 0;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setDefaultSort('value', 'desc');
    }

    public function builder(): Builder
    {
        $this->rankCounter = 0;

        return TwitchUserStat::query()
            ->where('name', 'points')
            ->with(['twitchUser.user'])
            ->orderByRaw('CAST(value AS INTEGER) DESC');
    }

    public function columns(): array
    {
        return [
            Column::make('Rank', 'id')
                ->format(function ($value, $row, Column $column) {
                    $page = $this->getPage() ?? 1;
                    $perPage = $this->getPerPage() ?? 10;

                    return ($page - 1) * $perPage + (++$this->rankCounter);
                })
                ->unclickable(),
            Column::make('Username', 'twitch_user_id')
                ->format(function ($value, $row, Column $column) {
                    // Access the eager loaded relationship
                    // Try both direct access and loading if needed
                    $twitchUser = $row->getRelation('twitchUser') ?? $row->twitchUser ?? null;

                    if (! $twitchUser) {
                        return view('livewire.tables.user-link', [
                            'displayName' => 'Unknown',
                            'hasUser' => false,
                            'userId' => null,
                        ]);
                    }

                    return view('livewire.tables.user-link', [
                        'displayName' => $twitchUser->display_name ?? 'Unknown',
                        'hasUser' => $twitchUser->relationLoaded('user') ? $twitchUser->user !== null : ($twitchUser->user_id !== null),
                        'userId' => $twitchUser->user?->id ?? ($twitchUser->user_id ?? null),
                    ]);
                })
                ->sortable(function (Builder $query, string $direction) {
                    return $query->join('twitch_users', 'twitch_user_stats.twitch_user_id', '=', 'twitch_users.id')
                        ->orderBy('twitch_users.display_name', $direction)
                        ->select('twitch_user_stats.*')
                        ->with(['twitchUser.user']);
                })
                ->searchable(function (Builder $query, $searchTerm) {
                    return $query->whereHas('twitchUser', function ($q) use ($searchTerm) {
                        $q->where('display_name', 'ilike', "%{$searchTerm}%");
                    });
                }),
            Column::make('Points', 'value')
                ->sortable(function (Builder $query, string $direction) {
                    $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

                    return $query->orderByRaw("CAST(value AS INTEGER) {$direction}");
                })
                ->format(fn ($value) => number_format((int) $value)),
        ];
    }
}
