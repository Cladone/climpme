@php
    $parameters = [
        [
            'name' => 'name',
            'type' => $type,
            'format' => 'string',
            'description' => __('Name') . '.'
        ],
        [
            'name' => 'type',
            'type' => $type,
            'format' => 'string',
            'description' => __('Type') . '. ' . __('Possible values are: :values.', ['values' => '<code>'.implode('</code>, <code>', array_keys(config('pixels'))).'</code>'])
        ],
        [
            'name' => 'value',
            'type' => $type,
            'format' => 'string',
            'description' => __('The pixel ID value.')
        ]
    ];
@endphp

@include('developers.parameters')