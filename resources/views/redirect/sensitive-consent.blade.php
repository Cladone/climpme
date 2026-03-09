@extends('layouts.redirect')

@section('site_title', __($link->title))

@section('head_content')
    <meta name="robots" content="noindex">

    @if($link->title)
        <meta property="og:title" content="{{ $link->title }}">
    @endif

    @if($link->description)
        <meta name="description" content="{{ $link->description }}">
        <meta property="og:description" content="{{ $link->description }}">
    @endif

    @if($link->image)
        <meta property="og:image" content="{{ $link->image }}">
    @endif

    <meta property="og:url" content="{{ $link->url }}">

@endsection

@section('content')
<div class="bg-base-1 d-flex align-items-center flex-fill">
    <div class="container">
        <div class="row h-100 justify-content-center align-items-center py-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-danger font-weight-medium">
                            {{ __('You are about to access a link that may contain sensitive, explicit, or potentially disturbing content.') }}
                        </div>

                        <div class="my-3 text-muted">
                            {!! __('By clicking :button, you agree to be redirected to the destination link.', ['button' => '<strong>'.__('Continue').'</strong>']) !!}
                        </div>

                        <form action="{{ route(Route::currentRouteName(), $link->id) }}/sensitive-consent" method="post">
                            @csrf
                            <div class="form-row m-n2">
                                @if(url()->previous() != url()->current())
                                    <div class="col-12 col-md p-2">
                                        <a href="{{ url()->previous() }}" class="btn btn-block btn-secondary">{{ __('Go back') }}</a>
                                    </div>
                                @endif

                                <div class="col-12 col-md p-2">
                                    <button name="sensitive" type="submit" value="1" class="btn btn-block btn-danger">{{ __('Continue') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection