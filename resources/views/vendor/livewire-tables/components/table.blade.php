@aware([ 'tableName','isTailwind','isBootstrap'])

@php
    $customAttributes = [
        'wrapper' => $this->getTableWrapperAttributes(),
        'table' => $this->getTableAttributes(),
        'thead' => $this->getTheadAttributes(),
        'tbody' => $this->getTbodyAttributes(),
    ];
@endphp

@if ($isTailwind)
    <div
        wire:key="{{ $tableName }}-twrap"
        {{ $attributes->merge($customAttributes['wrapper'])
            ->class([
                'overflow-y-auto border border-neutral-200 dark:border-neutral-800 sm:rounded-xl shadow-sm dark:shadow-lg bg-white dark:bg-neutral-900' => $customAttributes['wrapper']['default'] ?? true
            ])
            ->except(['default','default-styling','default-colors']) }}
    >
        <table
            wire:key="{{ $tableName }}-table"
            {{ $attributes->merge($customAttributes['table'])
                ->class(['min-w-full divide-y divide-gray-200 dark:divide-neutral-800' => $customAttributes['table']['default'] ?? true])
                ->except(['default','default-styling','default-colors']) }}

        >
            <thead wire:key="{{ $tableName }}-thead"
                {{ $attributes->merge($customAttributes['thead'])
                    ->class([
                        'bg-gray-50 dark:bg-neutral-900' => $customAttributes['thead']['default'] ?? true
                    ])
                    ->except(['default','default-styling','default-colors']) }}
            >
                <tr>
                    {{ $thead }}
                </tr>
            </thead>

            <tbody
                wire:key="{{ $tableName }}-tbody"
                id="{{ $tableName }}-tbody"
                {{ $attributes->merge($customAttributes['tbody'])
                        ->class([
                            'bg-white divide-y divide-gray-200 dark:bg-transparent dark:divide-neutral-800' => $customAttributes['tbody']['default'] ?? true
                        ])
                        ->except(['default','default-styling','default-colors']) }}
            >
                {{ $slot }}
            </tbody>

            @isset($tfoot)
                <tfoot wire:key="{{ $tableName }}-tfoot">
                    {{ $tfoot }}
                </tfoot>
            @endisset
        </table>
    </div>
@elseif ($isBootstrap)
    <div wire:key="{{ $tableName }}-twrap"
        {{ $attributes->merge($customAttributes['wrapper'])
            ->class(['table-responsive' => $customAttributes['wrapper']['default'] ?? true])
            ->except(['default','default-styling','default-colors']) }}
    >
        <table
            wire:key="{{ $tableName }}-table"
            {{ $attributes->merge($customAttributes['table'])
                ->class(['laravel-livewire-table table' => $customAttributes['table']['default'] ?? true])
                ->except(['default','default-styling','default-colors'])
            }}
        >
            <thead
                wire:key="{{ $tableName }}-thead"
                {{ $attributes->merge($customAttributes['thead'])
                    ->class(['' => $customAttributes['thead']['default'] ?? true])
                    ->except(['default','default-styling','default-colors']) }}
            >
                <tr>
                    {{ $thead }}
                </tr>
            </thead>

            <tbody
                wire:key="{{ $tableName }}-tbody"
                id="{{ $tableName }}-tbody"
                {{ $attributes->merge($customAttributes['tbody'])
                        ->class(['' => $customAttributes['tbody']['default'] ?? true])
                        ->except(['default','default-styling','default-colors']) }}
            >
                {{ $slot }}
            </tbody>

            @isset($tfoot)
                <tfoot wire:key="{{ $tableName }}-tfoot">
                    {{ $tfoot }}
                </tfoot>
            @endisset
        </table>
    </div>
@endif
