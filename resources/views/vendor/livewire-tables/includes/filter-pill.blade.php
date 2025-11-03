@aware(['tableName','isTailwind','isBootstrap4','isBootstrap5'])

<div x-data="filterPillsHandler(@js($setupData))" x-bind="trigger" 
    wire:key="{{ $tableName }}-filter-pill-{{ $filterKey }}" {{
    $attributes->merge($filterPillsItemAttributes)
    ->class([
        'inline-flex items-center px-2.5 py-0.5 rounded-full leading-4' => $isTailwind && ($filterPillsItemAttributes['default-styling'] ?? true),
        'text-xs font-medium capitalize' => $isTailwind && ($filterPillsItemAttributes['default-text'] ?? ($filterPillsItemAttributes['default-styling'] ?? true)),
        'bg-neutral-100 text-neutral-800 dark:bg-zinc-700 dark:text-zinc-100' => $isTailwind && ($filterPillsItemAttributes['default-colors'] ?? true),
        'badge badge-pill badge-info d-inline-flex align-items-center' => $isBootstrap4 && ($filterPillsItemAttributes['default-styling'] ?? true),
        'badge rounded-pill bg-info d-inline-flex align-items-center' => $isBootstrap5 && ($filterPillsItemAttributes['default-styling'] ?? true),
    ])
    ->except(['default', 'default-styling', 'default-colors'])
}}
>
<span {{ $attributes->merge($pillTitleDisplayDataArray) }}></span>:&nbsp;
<span {{ $attributes->merge($pillDisplayDataArray) }}></span>

<x-livewire-tables::tools.filter-pills.buttons.reset-filter :$filterKey :$filterPillData/>

</div>
