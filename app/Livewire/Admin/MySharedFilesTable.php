<?php

namespace App\Livewire\Admin;

use App\Models\SharedFile;
use App\Support\SharedFileExpiry;
use App\Support\ViewerTimezone;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Admin management table for viewing and managing shared file uploads
 */
class MySharedFilesTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public ?array $mountedActions = [];

    public ?array $mountedActionsData = [];

    protected bool $isCachingSchemas = false;

    private const EXPIRY_ADJUSTMENTS = [
        '+1_day' => '+1 day',
        '+7_days' => '+7 days',
        '-1_day' => '-1 day',
        '-7_days' => '-7 days',
    ];

    /**
     * Mount the component and check admin access
     */
    public function mount(): void
    {
        if (! auth()->user()?->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Make Filament translatable content driver (not used in this component)
     */
    public function makeFilamentTranslatableContentDriver(): ?\Filament\Support\Contracts\TranslatableContentDriver
    {
        return null;
    }

    /**
     * Configure the table
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(SharedFile::query()->where('user_id', Auth::id() ?? 0))
            ->columns([
                TextColumn::make('original_filename')
                    ->label('Filename')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn (SharedFile $record): string => $record->original_filename),
                TextColumn::make('human_readable_size')
                    ->label('Size')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('file_size', $direction)),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime(timezone: fn (): string => ViewerTimezone::resolve(request()))
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expires At')
                    ->sortable()
                    ->formatStateUsing(function ($state): string {
                        if (blank($state)) {
                            return 'Permanent';
                        }

                        $viewerTz = ViewerTimezone::resolve(request());
                        $expiresAt = $state instanceof Carbon
                            ? $state
                            : Carbon::parse($state, 'UTC');

                        $utcString = $expiresAt->format('Y-m-d H:i:s T');
                        $converted = $expiresAt->copy()->setTimezone($viewerTz);
                        $convertedString = $converted->format('Y-m-d H:i');

                        return $convertedString;
                    })
                    ->badge()
                    ->color(fn (SharedFile $record): string => $record->expires_at === null ? 'success' : ($record->expires_at->isPast() ? 'danger' : 'warning')),
                ViewColumn::make('time_remaining')
                    ->label('Time Remaining')
                    ->view('livewire.admin.my-shared-files-table.time-remaining'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('set_expiration')
                        ->label('Expiration…')
                        ->icon(Heroicon::Clock)
                        ->color('gray')
                        ->modalHeading('Set Expiration')
                        ->schema([
                            Select::make('preset')
                                ->label('Expiration')
                                ->options(SharedFileExpiry::presetOptions())
                                ->required()
                                ->default('3d'),
                        ])
                        ->action(function (array $data, SharedFile $record): void {
                            $record->update([
                                'expires_at' => SharedFileExpiry::expiresAt($data['preset']),
                            ]);
                        }),
                    Action::make('adjust_expiration')
                        ->label('Adjust…')
                        ->icon(Heroicon::CalendarDays)
                        ->color('gray')
                        ->modalHeading('Adjust Expiration')
                        ->schema([
                            Select::make('adjustment')
                                ->label('Adjustment')
                                ->options(self::EXPIRY_ADJUSTMENTS)
                                ->required()
                                ->default('+1_day'),
                        ])
                        ->action(function (array $data, SharedFile $record): void {
                            $record->update([
                                'expires_at' => $this->adjustedExpiresAt($record->expires_at, $data['adjustment']),
                            ]);
                        }),
                    Action::make('delete')
                        ->label('Delete')
                        ->icon(Heroicon::Trash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (SharedFile $record): void {
                            if (Storage::disk('local')->exists($record->file_path)) {
                                Storage::disk('local')->delete($record->file_path);
                            }

                            $record->delete();
                        }),
                ])
                    ->label('Actions')
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->color('gray')
                    ->size(Size::Small)
                    ->iconButton()
                    ->tooltip('Actions'),
            ])
            ->groupedBulkActions([
                BulkAction::make('set_expiration')
                    ->label('Set expiration…')
                    ->icon(Heroicon::Clock)
                    ->modalHeading('Set Expiration')
                    ->schema([
                        Select::make('preset')
                            ->label('Expiration')
                            ->options(SharedFileExpiry::presetOptions())
                            ->required()
                            ->default('3d'),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $expiresAt = SharedFileExpiry::expiresAt($data['preset']);

                        $records->each(fn (SharedFile $record) => $record->update([
                            'expires_at' => $expiresAt,
                        ]));
                    }),
                BulkAction::make('adjust_expiration')
                    ->label('Adjust expiration…')
                    ->icon(Heroicon::CalendarDays)
                    ->modalHeading('Adjust Expiration')
                    ->schema([
                        Select::make('adjustment')
                            ->label('Adjustment')
                            ->options(self::EXPIRY_ADJUSTMENTS)
                            ->required()
                            ->default('+1_day'),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $records->each(function (SharedFile $record) use ($data): void {
                            $record->update([
                                'expires_at' => $this->adjustedExpiresAt($record->expires_at, $data['adjustment']),
                            ]);
                        });
                    }),
                BulkAction::make('delete')
                    ->label('Delete selected')
                    ->icon(Heroicon::Trash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $records->each(function (SharedFile $record): void {
                            if (Storage::disk('local')->exists($record->file_path)) {
                                Storage::disk('local')->delete($record->file_path);
                            }

                            $record->delete();
                        });
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private function adjustedExpiresAt(?Carbon $currentExpiresAt, string $adjustment): ?Carbon
    {
        $isPlus = Str::startsWith($adjustment, '+');

        $amount = (int) Str::of($adjustment)
            ->ltrim('+-')
            ->before('_')
            ->toString();

        if ($amount < 1) {
            $amount = 1;
        }

        if ($currentExpiresAt === null) {
            return $isPlus ? now('UTC')->addDays($amount) : null;
        }

        $newExpiresAt = $isPlus
            ? $currentExpiresAt->copy()->addDays($amount)
            : $currentExpiresAt->copy()->subDays($amount);

        return $newExpiresAt->isPast() ? now('UTC') : $newExpiresAt;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.my-shared-files-table');
    }
}
