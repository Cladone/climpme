@extends('layouts.wrapper')

@section('body')
    <body class="d-flex flex-column">
        @guest
            @if(config('settings.announcement_guest'))
                @include('shared.announcement', ['message' => config('settings.announcement_guest'), 'type' => config('settings.announcement_guest_type'), 'id' => config('settings.announcement_guest_id')])
            @endif
        @else
            @if(config('settings.announcement_user'))
                @include('shared.announcement', ['message' => config('settings.announcement_user'), 'type' => config('settings.announcement_user_type'), 'id' => config('settings.announcement_user_id')])
            @endif
        @endguest

        @if(parse_url(config('app.url'), PHP_URL_HOST) == request()->getHost())
            @include('shared.header')
        @endif

        <div class="d-flex flex-column flex-fill @auth content @endauth">
            @yield('content')

            @include('shared.footer', ['footer' => ['menu' => ['removed' => true], 'copyright' => (parse_url(config('app.url'), PHP_URL_HOST) !== request()->getHost() ? ['removed' => true] : [])]])
        </div>
    </body>
@endsection