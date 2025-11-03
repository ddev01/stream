@aware(['tableName','isTailwind','isBootstrap','isBootstrap4','isBootstrap5', 'localisationPath'])
@props(['filterKey', 'filterPillData'])

@php
    
    $filterButtonAttributes = $filterPillData->getCalculatedCustomResetButtonAttributes($filterKey,$this->getFilterPillsResetFilterButtonAttributes);

@endphp
@if ($isTailwind)
    <button 
        {{
            $attributes->merge($filterButtonAttributes)
            ->class([
                'flex-shrink-0 ml-0.5 h-4 w-4 rounded-full inline-flex items-center justify-center focus:outline-none' => $filterButtonAttributes['default-styling'],
                'text-neutral-400 hover:bg-neutral-200 hover:text-neutral-500 focus:bg-neutral-500 focus:text-white dark:text-zinc-400 dark:hover:bg-zinc-600 dark:hover:text-zinc-200 dark:focus:bg-zinc-500 dark:focus:text-white' => $filterButtonAttributes['default-colors'],
            ])
            ->except(['default', 'default-colors', 'default-styling', 'default-text'])
        }}
    >
        <span class="sr-only">{{ __($localisationPath.'Remove filter option') }}</span>
        <x-heroicon-m-x-mark class="h-full" />
    </button>
@else
    <a
        href="#"
        x-on:click.prevent="resetSpecificFilter('{{ $filterKey }}')"
        {{
            $attributes->merge($filterButtonAttributes)
            ->class([
                'text-white ml-2' => $isBootstrap && $filterButtonAttributes['default-styling']
            ])
            ->except(['default', 'default-colors', 'default-styling', 'default-text'])
        }}
    >
        <span @class([
            'sr-only' => $isBootstrap4,
            'visually-hidden' => $isBootstrap5,
            ])>{{ __($localisationPath.'Remove filter option') }}
            </span>
        <x-heroicon-m-x-mark class="laravel-livewire-tables-btn-tiny"  />
    </a>
@endif
