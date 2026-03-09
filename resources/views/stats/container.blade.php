@extends('layouts.app')

@section('head_content')

@endsection

@section('content')
<script src="{{ asset('js/app.extras.js?v=' . config('info.software.version')) }}" defer></script>
<div class="bg-base-1 flex-fill">
    <div class="container pt-3 mt-3 pb-6">
        @include('stats.header')

        @include('stats.' . $view)

        <div class="row mt-3 small text-muted">
            <div class="col">
                {{ __('Report generated on :date at :time (UTC :offset).', ['date' => (clone $now)->tz(Auth::user()->timezone ?? config('app.timezone'))->format(__('Y-m-d')), 'time' => (clone $now)->tz(Auth::user()->timezone ?? config('app.timezone'))->format('H:i:s'), 'offset' => (clone $now)->tz(Auth::user()->timezone ?? config('app.timezone'))->getOffsetString()]) }} <a href="{{ Request::fullUrl() }}" class="text-dark">{{ __('Refresh report') }}</a>
            </div>
        </div>
    </div>
</div>
@include('shared.modals.share-link')
@endsection

@include('shared.sidebars.user')