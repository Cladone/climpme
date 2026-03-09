@php
    $parameters = [
        [
            'name' => 'name',
            'type' => $type,
            'format' => 'string',
            'description' => __('Name') . '.'
        ],
        [
            'name' => 'color',
            'type' => 0,
            'format' => 'integer',
            'description' => __('Color') . '. ' . __('Possible values are: :values.', ['values' => '<code>' . implode('</code>, <code>', [1, 2, 3, 4, 5, 6]) .'</code>']) . ($type ? ' ' . __('Defaults to: :value.', ['value' => '<code>1</code>']) : '')
        ]
    ];
@endphp

@include('developers.parameters')