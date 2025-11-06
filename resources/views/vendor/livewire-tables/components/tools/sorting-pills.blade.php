@aware(['tableName', 'isTailwind', 'isBootstrap', 'isBootstrap4', 'isBootstrap5', 'localisationPath'])

@if ($isTailwind)
	<div class="@if($this->sortingPillsAreEnabled() && $this->hasSorts()) mb-4 @endif">
        @if ($this->sortingPillsAreEnabled() && $this->hasSorts())
			<div class="px-4 md:p-0" x-cloak x-show="!currentlyReorderingStatus">
				<small class="text-neutral-700 dark:text-zinc-300">{{ __($localisationPath . 'Applied Sorting') }}:</small>

				@foreach ($this->getSorts() as $columnSelectName => $direction)
                    @php($column = $this->getColumnBySelectName($columnSelectName) ?? $this->getColumnBySlug($columnSelectName))

                    @continue(is_null($column))
                    @continue($column->isHidden())
					@continue($this->columnSelectIsEnabled && !$this->columnSelectIsEnabledForColumn($column))

					<span wire:key="{{ $tableName }}-sorting-pill-{{ $columnSelectName }}" {{ $attributes->merge($this->getSortingPillsItemAttributes())->class([
                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium leading-4 capitalize' => $this->getSortingPillsItemAttributes()['default-styling'],
					        'bg-neutral-100 text-neutral-800 dark:bg-zinc-700 dark:text-zinc-100' => $this->getSortingPillsItemAttributes()['default-colors'],
					    ])->except(['default-styling', 'default-colors']) }}>
                        {{ $column->getSortingPillTitle() }}: {{ $column->getSortingPillDirectionLabel($direction, $this->getDefaultSortingLabelAsc, $this->getDefaultSortingLabelDesc) }}

						<button type="button" wire:click="clearSort('{{ $columnSelectName }}')" {{ $attributes->merge($this->getSortingPillsClearSortButtonAttributes())->class([
                                    'flex-shrink-0 ml-0.5 h-4 w-4 rounded-full inline-flex items-center justify-center focus:outline-none' => $this->getSortingPillsClearSortButtonAttributes()['default-styling'],
						        'text-neutral-400 hover:bg-neutral-200 hover:text-neutral-500 focus:bg-neutral-500 focus:text-white dark:text-zinc-400 dark:hover:bg-zinc-600 dark:hover:text-zinc-200 dark:focus:bg-zinc-500 dark:focus:text-white' => $this->getSortingPillsClearSortButtonAttributes()['default-colors'],
						    ])->except(['default-styling', 'default-colors']) }}>
							<span class="sr-only">{{ __($localisationPath . 'Remove sort option') }}</span>
                            <x-heroicon-m-x-mark class="h-3 w-3" />
                        </button>
                    </span>
                @endforeach

				<button class="focus:outline-none active:outline-none" wire:click.prevent="clearSorts">
					<span {{ $attributes->merge($this->getSortingPillsClearAllButtonAttributes())->class([
                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium' => $this->getSortingPillsClearAllButtonAttributes()['default-styling'],
					        'bg-neutral-100 text-neutral-800 dark:bg-zinc-700 dark:text-zinc-100' => $this->getSortingPillsClearAllButtonAttributes()['default-colors'],
					    ])->except(['default-styling', 'default-colors']) }}>
						{{ __($localisationPath . 'Clear') }}
                    </span>
                </button>
            </div>
        @endif
    </div>
@elseif ($isBootstrap4)
    <div>
        @if ($this->sortingPillsAreEnabled() && $this->hasSorts())
            <div class="mb-3" x-cloak x-show="!currentlyReorderingStatus">
				<small>{{ __($localisationPath . 'Applied Sorting') }}:</small>

				@foreach ($this->getSorts() as $columnSelectName => $direction)
                    @php($column = $this->getColumnBySelectName($columnSelectName) ?? $this->getColumnBySlug($columnSelectName))

                    @continue(is_null($column))
                    @continue($column->isHidden())
					@continue($this->columnSelectIsEnabled && !$this->columnSelectIsEnabledForColumn($column))

					<span wire:key="{{ $tableName . '-sorting-pill-' . $columnSelectName }}" {{ $attributes->merge($this->getSortingPillsItemAttributes())->class([
                                'badge badge-pill badge-info d-inline-flex align-items-center' => $this->getSortingPillsItemAttributes()['default-styling'],
					    ])->except(['default-styling', 'default-colors']) }}>
                        {{ $column->getSortingPillTitle() }}: {{ $column->getSortingPillDirectionLabel($direction, $this->getDefaultSortingLabelAsc, $this->getDefaultSortingLabelDesc) }}

						<a href="#" wire:click="clearSort('{{ $columnSelectName }}')" {{ $attributes->merge($this->getSortingPillsClearSortButtonAttributes())->class([
                                    'text-white ml-2' => $this->getSortingPillsClearSortButtonAttributes()['default-styling'],
						    ])->except(['default-styling', 'default-colors']) }}>
							<span class="sr-only">{{ __($localisationPath . 'Remove sort option') }}</span>
                            <x-heroicon-m-x-mark class="laravel-livewire-tables-btn-smaller" />
                        </a>
                    </span>
                @endforeach

				<a href="#" wire:click.prevent="clearSorts" {{ $attributes->merge($this->getSortingPillsClearAllButtonAttributes())->class([
                            'badge badge-pill badge-light' => $this->getSortingPillsClearAllButtonAttributes()['default-styling'],
				    ])->except(['default-styling', 'default-colors']) }}>
					{{ __($localisationPath . 'Clear') }}
                </a>
            </div>
        @endif
    </div>
@elseif ($isBootstrap5)
    <div>
        @if ($this->sortingPillsAreEnabled() && $this->hasSorts())
            <div class="mb-3" x-cloak x-show="!currentlyReorderingStatus">
				<small>{{ __($localisationPath . 'Applied Sorting') }}:</small>

				@foreach ($this->getSorts() as $columnSelectName => $direction)
                    @php($column = $this->getColumnBySelectName($columnSelectName) ?? $this->getColumnBySlug($columnSelectName))

                    @continue(is_null($column))
                    @continue($column->isHidden())
					@continue($this->columnSelectIsEnabled && !$this->columnSelectIsEnabledForColumn($column))

					<span wire:key="{{ $tableName }}-sorting-pill-{{ $columnSelectName }}" {{ $attributes->merge($this->getSortingPillsItemAttributes())->class([
                                'badge rounded-pill bg-info d-inline-flex align-items-center' => $this->getSortingPillsItemAttributes()['default-styling'],
					    ])->except(['default-styling', 'default-colors']) }}>
                        {{ $column->getSortingPillTitle() }}: {{ $column->getSortingPillDirectionLabel($direction, $this->getDefaultSortingLabelAsc, $this->getDefaultSortingLabelDesc) }}

						<a href="#" wire:click="clearSort('{{ $columnSelectName }}')" {{ $attributes->merge($this->getSortingPillsClearSortButtonAttributes())->class([
                                    'text-white ms-2' => $this->getSortingPillsClearSortButtonAttributes()['default-styling'],
						    ])->except(['default-styling', 'default-colors']) }}>
							<span class="visually-hidden">{{ __($localisationPath . 'Remove sort option') }}</span>
                            <x-heroicon-m-x-mark class="laravel-livewire-tables-btn-smaller" />
                        </a>
                    </span>
                @endforeach

				<a href="#" wire:click.prevent="clearSorts" {{ $attributes->merge($this->getSortingPillsClearAllButtonAttributes())->class([
                            'badge rounded-pill bg-light text-dark text-decoration-none' => $this->getSortingPillsClearAllButtonAttributes()['default-styling'],
				    ])->except(['default-styling', 'default-colors']) }}>
					{{ __($localisationPath . 'Clear') }}
                </a>
            </div>
        @endif
    </div>
@endif
