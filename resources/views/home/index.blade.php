@section('site_title', formatTitle([config('settings.title'), __(config('settings.tagline'))]))

@extends('layouts.app')

@section('content')
<div class="flex-fill">
    <div class="bg-base-0 position-relative py-5 py-sm-6">
        <div class="container position-relative py-sm-5 z-1">
            <h1 class="display-4 text-center text-break font-weight-bold mb-0">
            Simple. Fast. Smarter Links.
            </h1>

            <p class="text-muted text-center text-break font-size-xl font-weight-normal mt-4 mb-5">
            Stay in control of your links with advanced features for shortening, targeting, and tracking.
            </p>

            <div class="row justify-content-center">

                @if(config('settings.short_guest'))
                    <div class="col-12 col-lg-8">
                        <div class="form-group mb-0" id="short-form-container"@if(request()->session()->get('link')) style="display: none;"@endif>
                            <form action="{{ route('guest') }}" method="post" enctype="multipart/form-data" id="short-form">
                                @csrf
                                <div class="form-row">
                                    <div class="col-12 col-sm">
                                        <input type="text" dir="ltr" autocomplete="off" autocapitalize="none" spellcheck="false" name="url" class="form-control form-control-lg font-size-lg{{ $errors->has('url') || $errors->has('domain_id') || $errors->has(formatCaptchaFieldName()) ? ' is-invalid' : '' }}" placeholder="{{ __('https://example.com') }}" autofocus>
                                        @if ($errors->has('url'))
                                            <span class="invalid-feedback d-block" role="alert">
                                                <strong>{{ $errors->first('url') }}</strong>
                                            </span>
                                        @endif

                                        @if ($errors->has('domain_id'))
                                            <span class="invalid-feedback d-block" role="alert">
                                                <strong>{{ $errors->first('domain_id') }}</strong>
                                            </span>
                                        @endif

                                        @if ($errors->has(formatCaptchaFieldName()))
                                            <span class="invalid-feedback d-block" role="alert">
                                                <strong>{{ __($errors->first(formatCaptchaFieldName())) }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="col-12 col-sm-auto mt-3 mt-sm-0">
                                        @if(config('settings.captcha_driver'))
                                            <x-captcha-js lang="{{ __('lang_code') }}"></x-captcha-js>

                                            @include('shared.captcha', ['id' => 'short-form'])

                                            <x-captcha-button data-callback="{{ (config('settings.captcha_driver') == 'turnstile' ? '' : 'captchaFormSubmit') }}" form-id="short-form" class="btn btn-primary btn-lg btn-block font-size-lg" data-sitekey="{{ config('settings.captcha_site_key') }}" data-theme="{{ (config('settings.dark_mode') == 1 ? 'dark' : 'light') }}">{{ __('Shorten') }}</x-captcha-button>
                                        @else
                                            <button class="btn btn-primary btn-lg btn-block font-size-lg position-relative" type="submit" data-button-loader>
                                                <span class="position-absolute top-0 right-0 bottom-0 left-0 d-flex align-items-center justify-content-center">
                                                    <span class="d-none spinner-border spinner-border-sm width-4 height-4" role="status"></span>
                                                </span>
                                                <span class="spinner-text">{{ __('Shorten') }}</span>&#8203;
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <input type="hidden" name="domain_id" value="{{ $defaultDomain }}">
                            </form>
                        </div>

                        @include('home.link')
                    </div>
                @else
                    <div class="col-12 col-lg-8">
                        <div class="row justify-content-md-center {{ !config('settings.report_guest') ? 'm-n2' : '' }}">
                            <div class="col-12 col-md-auto p-2">
                                <a href="{{ config('settings.registration') ? route('register') : route('login') }}" class="btn btn-primary btn-lg btn-block font-size-lg d-inline-flex align-items-center justify-content-center">{{ __('Get started') }}</a>
                            </div>
                            <div class="col-12 col-md-auto p-2">
                                <a href="#features" class="btn btn-outline-primary btn-lg btn-block font-size-lg d-inline-flex align-items-center justify-content-center">{{ __('Learn more') }}</a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@if(request()->session()->get('link'))
    @include('shared.modals.share-link')
@endif