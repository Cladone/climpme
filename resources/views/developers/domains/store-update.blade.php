@php
    if($type) {
        $parameters[] = [
            'name' => 'name',
            'type' => $type,
            'format' => 'string',
            'description' => __('Name') . '.'
        ];
    }

    $parameters[] = [
        'name' => 'index_page',
        'type' => 0,
        'format' => 'string',
        'description' => __('Custom index URL') . '.'
    ];

    $parameters[] = [
        'name' => 'not_found_page',
        'type' => 0,
        'format' => 'string',
        'description' => __('Custom 404 URL') . '.'
    ];
@endphp

@include('developers.parameters')