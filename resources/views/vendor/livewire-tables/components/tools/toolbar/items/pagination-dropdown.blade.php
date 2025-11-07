@aware(['tableName', 'isTailwind', 'isBootstrap', 'isBootstrap4', 'isBootstrap5', 'localisationPath'])
@if ($isTailwind)
	<div class="relative inline-block w-full md:w-auto">
		<select id="{{ $tableName }}-perPage" wire:model.live="perPage" {{ $attributes->merge($this->getPerPageFieldAttributes())->class([
		        'appearance-none block w-full px-4 py-2 text-sm font-medium rounded-md shadow-sm focus:ring focus:ring-opacity-50 pr-10 transition duration-150 ease-in-out cursor-pointer' => $this->getPerPageFieldAttributes()['default-styling'],
		        'text-neutral-700 bg-white hover:bg-neutral-50 focus:ring-neutral-400 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800' => $this->getPerPageFieldAttributes()['default-colors'],
		    ])->except(['default', 'default-styling', 'default-colors']) }}>
			@foreach ($this->getPerPageAccepted() as $item)
				<option value="{{ $item }}" wire:key="{{ $tableName }}-per-page-{{ $item }}">
					{{ $item === -1 ? __($localisationPath . 'All') : $item }}
				</option>
			@endforeach
		</select>
		<div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4">
			<x-heroicon-m-chevron-down class="-mr-1 h-5 w-5 text-neutral-700 dark:text-zinc-300" />
		</div>
	</div>
@else
	<div @class([
		'ml-0 ml-md-2' => $isBootstrap4,
		'ms-0 ms-md-2' => $isBootstrap5,
	])>
		<select id="{{ $tableName }}-perPage" wire:model.live="perPage" {{ $attributes->merge($this->getPerPageFieldAttributes())->class([
		        'form-control' => $isBootstrap4 && $this->getPerPageFieldAttributes()['default-styling'],
		        'form-select' => $isBootstrap5 && $this->getPerPageFieldAttributes()['default-styling'],
		    ])->except(['default', 'default-styling', 'default-colors']) }}>
			@foreach ($this->getPerPageAccepted() as $item)
				<option value="{{ $item }}" wire:key="{{ $tableName }}-per-page-{{ $item }}">
					{{ $item === -1 ? __($localisationPath . 'All') : $item }}
				</option>
			@endforeach
		</select>
	</div>
@endif
