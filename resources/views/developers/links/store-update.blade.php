@php
    $parameters[] = [
        'name' => 'url',
        'type' => $type,
        'format' => 'string',
        'description' => __('Destination URL') . '.'
    ];

    if($type) {
        $parameters[] = [
            'name' => 'domain_id',
            'type' => 1,
            'format' => 'integer',
            'description' => __('Domain ID') . '.'
        ];
    }

    $parameters[] = [
        'name' => 'alias',
        'type' => 0,
        'format' => 'string',
        'description' => __('Alias') . '.'
    ];

    $parameters[] = [
        'name' => 'space_id',
        'type' => 0,
        'format' => 'integer',
        'description' => __('Space ID') . '.'
    ];

    $parameters[] = [
        'name' => 'pixel_ids[]',
        'type' => 0,
        'format' => 'array',
        'description' => __('Pixel IDs') . '.'
    ];

    $parameters[] = [
        'name' => 'redirect_password',
        'type' => 0,
        'format' => 'string',
        'description' => __('Redirect password') . '.'
    ];

    $parameters[] = [
        'name' => 'sensitive_content',
        'type' => 0,
        'format' => 'integer',
        'description' => __('Sensitive content') . '. ' . __('Possible values are: :values.', [
            'values' => implode(', ', [
                __(':value for :name', ['value' => '<code>0</code>', 'name' => '<span class="font-weight-medium">'.__('No').'</span>']),
                __(':value for :name', ['value' => '<code>1</code>', 'name' => '<span class="font-weight-medium">'.__('Yes').'</span>'])
                ])
            ]) . ($type ? ' ' . __('Defaults to: :value.', ['value' => '<code>0</code>']) : '')
    ];

    $parameters[] = [
        'name' => 'privacy',
        'type' => 0,
        'format' => 'integer',
        'description' => __('Stats privacy') . '. ' . __('Possible values are: :values.', [
            'values' => implode(', ', [
                __(':value for :name', ['value' => '<code>0</code>', 'name' => '<span class="font-weight-medium">'.__('Public').'</span>']),
                __(':value for :name', ['value' => '<code>1</code>', 'name' => '<span class="font-weight-medium">'.__('Private').'</span>']),
                __(':value for :name', ['value' => '<code>2</code>', 'name' => '<span class="font-weight-medium">'.__('Password').'</span>'])
                ])
            ]) . ($type ? ' ' . __('Defaults to: :value.', ['value' => '<code>0</code>']) : '')
    ];

    $parameters[] = [
        'name' => 'password',
        'type' => 0,
        'format' => 'string',
        'description' => __('Stats password') . '. ' . __('Only works with :field field set to :value.', ['field' => '<code>privacy</code>', 'value' => '<code>2</code>'])
    ];

    $parameters[] = [
        'name' => 'active_period_start_at',
        'type' => 0,
        'format' => 'string',
        'description' => __('Active period starting date in :format format.', ['format' => '<code>Y-m-d H:i</code>'])
    ];

    $parameters[] = [
        'name' => 'active_period_end_at',
        'type' => 0,
        'format' => 'string',
        'description' => __('Active period ending date in :format format.', ['format' => '<code>Y-m-d H:i</code>'])
    ];

    $parameters[] = [
        'name' => 'clicks_limit',
        'type' => 0,
        'format' => 'integer',
        'description' => __('Clicks limit') . '.'
    ];

    $parameters[] = [
        'name' => 'expiration_url',
        'type' => 0,
        'format' => 'string',
        'description' => __('Expiration URL') . '.'
    ];

    $parameters[] = [
        'name' => 'targets_type',
        'type' => 0,
        'format' => 'string',
        'description' => __('Targeting') . '. ' . __('Possible values are: :values.', [
            'values' => implode(', ', array_map(function($value, $name) { return __(':value for :name', ['value' => '<code>' . $value . '</code>', 'name' => '<span class="font-weight-medium">' . $name . '</span>']); }, array_keys(config('targets')), config('targets')))])
    ];

    $parameters[] = [
        'name' => 'targets[index][key]',
        'type' => 0,
        'format' => 'string',
        'description' =>
        __('For :field, the value must be in :format format.', ['field' => '<code>targets_type=country</code>', 'format' => '<a href="https://wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" target="_blank" rel="nofollow noreferrer noopener">ISO 3166-1 alpha-2</a>']) . '<br>' .
        __('For :field, the possible values are :values.', ['field' => '<code>targets_type=operating_systems</code>', 'values' => '<code>'.implode('</code>, <code>', config('operating_systems'))]) . '</code><br>' .
        __('For :field, the possible values are: :values.', ['field' => '<code>targets_type=browsers</code>', 'values' => '<code>'.implode('</code>, <code>', config('browsers'))]) . '</code><br>' .
        __('For :field, the possible values are: :values.', ['field' => '<code>targets_type=devices</code>', 'values' => '<code>'.implode('</code>, <code>', config('devices'))]) . '</code><br>' .
        __('For :field, the value must be in :format format.', ['field' => '<code>targets_type=languages</code>', 'format' => '<a href="https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes" target="_blank" rel="nofollow noreferrer noopener">ISO 639-1 alpha-2</a>']) . '<br>' .
        __('For :field, the possible values are: :values.', ['field' => '<code>targets_type=continents</code>', 'values' => implode(', ', array_map(function($value, $name) { return __(':value for :name', ['value' => '<code>' . $value . '</code>', 'name' => '<span class="font-weight-medium">' . $name . '</span>']); }, array_keys(config('continents')), config('continents')))]) . '</code><br>'
    ];

    $parameters[] = [
        'name' => 'targets[index][value]',
        'type' => 0,
        'format' => 'string',
        'description' => __('Destination URL') . '.'
    ];
@endphp

@include('developers.parameters')