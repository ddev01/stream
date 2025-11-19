<?php

namespace App\Livewire;

use App\Livewire\Concerns\FormatsUserLinks;
use App\Models\TwitchUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

// Dummy model to prevent Eloquent from adding table columns
class LeaderboardDummy extends Model
{
    protected $table = 'leaderboard_data';

    public $timestamps = false;
}

class GiftedSubscriptionsLeaderboardTable extends DataTableComponent
{
    use FormatsUserLinks;

    // Don't set a model - we'll use DB::table() directly and wrap it
    // protected $model = SubscriptionHistory::class;

    public int $rankCounter = 0;

    /**
     * Configure the table component
     */
    public function configure(): void
    {
        $this->setPrimaryKey('gifter_user_id')
            ->setDefaultSort('gift_count', 'desc');
    }

    /**
     * Build the query for the table
     */
    public function builder(): Builder
    {
        $this->rankCounter = 0;

        // Build the subquery for gift counts
        $subquery = DB::table('subscription_history')
            ->selectRaw('gifter_user_id, COUNT(*) as gift_count')
            ->whereNotNull('gifter_user_id')
            ->groupBy('gifter_user_id');

        // Build the main query using DB::table() - no Eloquent model involved
        // Include display_name so we can sort/search on it
        $query = DB::table(DB::raw("({$subquery->toSql()}) as gift_counts"))
            ->mergeBindings($subquery)
            ->join('twitch_users', 'gift_counts.gifter_user_id', '=', 'twitch_users.twitch_id')
            ->select(
                'gift_counts.gifter_user_id',
                'gift_counts.gift_count',
                'twitch_users.id as twitch_user_table_id',
                'twitch_users.display_name'
            );

        // Wrap in an Eloquent builder using our dummy model
        // The dummy model has no real columns, so Eloquent won't add any
        $eloquentQuery = LeaderboardDummy::query()->fromSub($query, 'leaderboard_data')
            ->selectRaw('leaderboard_data.*');

        // Apply default sort if no user sort is active
        // Note: After fromSub, we need to reference leaderboard_data, not gift_counts
        if (! $this->hasSorts()) {
            $eloquentQuery->orderByDesc('leaderboard_data.gift_count');
        }

        return $eloquentQuery;
    }

    /**
     * Define the columns for the table
     */
    public function columns(): array
    {
        return [
            $this->getRankColumn(),
            $this->getUsernameColumn(),
            $this->getValueColumn(),
        ];
    }

    /**
     * Get the rank column definition
     */
    protected function getRankColumn(): Column
    {
        return Column::make('Rank', 'gifter_user_id')
            ->format(function ($value, $row, Column $column) {
                $page = $this->getPage() ?? 1;
                $perPage = $this->getPerPage() ?? 10;

                return ($page - 1) * $perPage + (++$this->rankCounter);
            })
            ->unclickable();
    }

    /**
     * Get the username column definition
     */
    protected function getUsernameColumn(): Column
    {
        return Column::make('Username', 'gifter_user_id')
            ->format(function ($value, $row, Column $column) {
                // Since we're using DB::table(), $row is a stdClass object
                // We need to get the TwitchUser and format it manually
                $twitchUser = TwitchUser::where('twitch_id', $row->gifter_user_id)
                    ->with('user')
                    ->first();

                if (! $twitchUser) {
                    return view('livewire.tables.user-link', [
                        'displayName' => 'Unknown',
                        'hasUser' => false,
                        'twitchId' => null,
                    ]);
                }

                return view('livewire.tables.user-link', [
                    'displayName' => $twitchUser->display_name ?? 'Unknown',
                    'hasUser' => $twitchUser->user !== null,
                    'twitchId' => $twitchUser->twitch_id ?? null,
                ]);
            })
            ->sortable(function (Builder $query, string $direction) {
                return $query->orderByRaw('LOWER(leaderboard_data.display_name) '.$direction);
            })
            ->searchable(function (Builder $query, $searchTerm) {
                $dbDriver = $query->getConnection()->getDriverName();
                $operator = $dbDriver === 'pgsql' ? 'ilike' : 'like';

                return $query->where('leaderboard_data.display_name', $operator, "%{$searchTerm}%");
            });
    }

    /**
     * Get the value column definition
     */
    protected function getValueColumn(): Column
    {
        return Column::make('Gifted Subs', 'gift_count')
            ->sortable(function (Builder $query, string $direction) {
                return $query->orderBy('leaderboard_data.gift_count', $direction);
            })
            ->setSortingPillDirections('0-9', '9-0')
            ->format(fn ($value) => number_format((int) $value));
    }
}
