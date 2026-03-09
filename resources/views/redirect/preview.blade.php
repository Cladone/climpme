@extends('layouts.redirect')

@section('site_title', __('Link preview'))

@section('content')
<div class="bg-base-1 d-flex align-items-center flex-fill">
    <div class="container py-6">
        <div class="row h-100 justify-content-center align-items-center">
            <div class="col-lg-12">
                <h1 class="h2 mb-5 text-center">{{ __('Link preview') }}</h1>
                <p class="text-center text-break text-dark font-weight-medium" dir="ltr">{{ $link->displayShortUrl }}</p>

                @if($link->clicks_limit || $link->ends_at)
                    <div class="my-4">
                    @if($link->clicks_limit)
                        <p class="my-2 text-center text-break text-muted">{!! __('Will expire after :count clicks', ['count' => '<span class="text-dark font-weight-medium">' . $link->clicks_limit . '</span>']) !!}</p>
                    @endif

                    @if($link->ends_at)
                        <p class="my-2 text-center text-break text-muted">{!! __('Will expire on :date at :time', ['date' => '<span class="text-dark font-weight-medium">' . $link->ends_at->tz(Auth::user()->timezone ?? config('app.timezone'))->format(__('Y-m-d')) . '</span>', 'time' => '<span class="text-dark font-weight-medium">' . $link->ends_at->tz(Auth::user()->timezone ?? config('app.timezone'))->format('H:i') . '</span>']) !!} UTC{{ \Carbon\CarbonTimeZone::create(Auth::user()->timezone ?? config('app.timezone'))->toOffsetName() }}</p>
                    @endif

                    @if($link->expiration_url)
                        <p class="my-2 text-center text-break text-muted">{!! __('Will redirect to :url once expired', ['url' => '<span class="text-dark font-weight-medium" dir="ltr">' . e($link->expiration_url) . '</span>']) !!}</p>
                    @endif
                    </div>
                @endif

                @if($link->targets_type)
                    <div class="my-4">
                        @if($link->targets_type == 'continents' && $link->targets !== null)
                            @foreach($link->targets as $continent)
                                <p class="my-2 text-center text-break text-muted">{!! __('If the continent is :name will redirect to :url', ['name' => '<span class="text-dark font-weight-medium">' . __(config('continents')[$continent->key]) . '</span>', 'url' => '<span class="text-dark font-weight-medium" dir="ltr">' . e($continent->value) . '</span>']) !!}</p>
                            @endforeach
                        @endif
                            
                        @if($link->targets_type == 'countries' && $link->targets !== null)
                            @foreach($link->targets as $country)
                                <p class="my-2 text-center text-break text-muted">{!! __('If the country is :name will redirect to :url', ['name' => '<span class="text-dark font-weight-medium">' . __(config('countries')[$country->key]) . '</span>', 'url' => '<span class="text-dark font-weight-medium" dir="ltr">' . e($country->value) . '</span>']) !!}</p>
                            @endforeach
                        @endif
    
                        @if($link->targets_type == 'operating_systems' && $link->targets !== null)
                            @foreach($link->targets as $platform)
                                <p class="my-2 text-center text-break text-muted">{!! __('If the operating system is :name will redirect to :url', ['name' => '<span class="text-dark font-weight-medium">' . $platform->key . '</span>', 'url' => '<span class="text-dark font-weight-medium" dir="ltr">' . e($platform->value) . '</span>']) !!}</p>
                            @endforeach
                        @endif
    
                        @if($link->targets_type == 'browsers' && $link->targets !== null)
                            @foreach($link->targets as $browser)
                                <p class="my-2 text-center text-break text-muted">{!! __('If the browser is :name will redirect to :url', ['name' => '<span class="text-dark font-weight-medium">' . $browser->key . '</span>', 'url' => '<span class="text-dark font-weight-medium" dir="ltr">' . e($browser->value) . '</span>']) !!}</p>
                            @endforeach
                        @endif
    
                        @if($link->targets_type == 'languages' && $link->targets !== null)
                            @foreach($link->targets as $language)
                                <p class="my-2 text-center text-break text-muted">{!! __('If the language is :name will redirect to :url', ['name' => '<span class="text-dark font-weight-medium">' . $language->key . '</span>', 'url' => '<span class="text-dark font-weight-medium" dir="ltr">' . e($language->value) . '</span>']) !!}</p>
                            @endforeach
                        @endif

                        @if($link->targets_type == 'devices' && $link->targets !== null)
                            @foreach($link->targets as $device)
                                <p class="my-2 text-center text-break text-muted">{!! __('If the device is :name will redirect to :url', ['name' => '<span class="text-dark font-weight-medium">' . __(config('devices')[$device->key]) . '</span>', 'url' => '<span class="text-dark font-weight-medium" dir="ltr">' . e($device->value) . '</span>']) !!}</p>
                            @endforeach
                        @endif
    
                        @if($link->targets_type == 'rotations' && $link->targets !== null)
                            @foreach($link->targets as $rotation)
                                <p class="my-2 text-center text-break text-muted">{!! __('Will rotate to :url', ['url' => '<span class="text-dark font-weight-medium" dir="ltr">' . e($rotation->value) . '</span>']) !!}</p>
                            @endforeach
                        @endif
                    </div>
                @endif

                <p class="mb-0 text-center text-break text-muted">{!! __('Will redirect to :url', ['url' => '<span class="text-dark font-weight-medium" dir="ltr">' . e($link->url) . '</span>']) !!}</p>

                @if(url()->previous() != url()->current())
                    <div class="text-center mt-5">
                        <a href="{{ url()->previous() }}" class="btn btn-primary">{{ __('Go back') }}</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection