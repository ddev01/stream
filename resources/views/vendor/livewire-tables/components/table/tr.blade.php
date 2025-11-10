@aware([ 'tableName','primaryKey','isTailwind','isBootstrap'])
@props(['row', 'rowIndex'])

@php
    $customAttributes = $this->getTrAttributes($row, $rowIndex);
@endphp

<tr
    rowpk='{{ $row->{$primaryKey} }}'
    x-on:dragstart.self="currentlyReorderingStatus && dragStart(event)"
    x-on:drop.prevent="currentlyReorderingStatus && dropEvent(event)"
    x-on:dragover.prevent.throttle.500ms="currentlyReorderingStatus && dragOverEvent(event)"
    x-on:dragleave.prevent.throttle.500ms="currentlyReorderingStatus && dragLeaveEvent(event)"
    @if($this->hasDisplayLoadingPlaceholder()) 
        wire:loading.class.add="hidden d-none"
    @else
        wire:loading.class.delay="opacity-50 dark:bg-gray-900 dark:opacity-60"
    @endif
    id="{{ $tableName }}-row-{{ $row->{$primaryKey} }}"
    :draggable="currentlyReorderingStatus"
    wire:key="{{ $tableName }}-tablerow-tr-{{ $row->{$primaryKey} }}"
    loopType="{{ ($rowIndex % 2 === 0) ? 'even' : 'odd' }}"
    {{
        $attributes->merge($customAttributes)
                ->class([
                    'bg-white dark:bg-transparent dark:text-neutral-100 rappasoft-striped-row hover:bg-neutral-100 dark:hover:bg-neutral-900/50 transition-colors border-b border-neutral-200 dark:border-neutral-800' => ($isTailwind && ($customAttributes['default'] ?? true) && $rowIndex % 2 === 0),
                    'bg-neutral-50 dark:bg-transparent dark:text-neutral-100 rappasoft-striped-row hover:bg-neutral-100 dark:hover:bg-neutral-900/50 transition-colors border-b border-neutral-200 dark:border-neutral-800' => ($isTailwind && ($customAttributes['default'] ?? true) && $rowIndex % 2 !== 0),
                    'cursor-pointer' => ($isTailwind && $this->hasTableRowUrl() && ($customAttributes['default'] ?? true)),
                    'bg-light rappasoft-striped-row' => ($isBootstrap && $rowIndex % 2 === 0 && ($customAttributes['default'] ?? true)),
                    'bg-white rappasoft-striped-row' => ($isBootstrap && $rowIndex % 2 !== 0 && ($customAttributes['default'] ?? true)),
                ])
                ->except(['default','default-styling','default-colors'])
    }}

>
    {{ $slot }}
</tr>
