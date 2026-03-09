@extends('layouts.app')

@section('site_title', formatTitle([__('Confirm payment'), config('settings.title')]))

@section('content')
    <script src="https://cdn.paddle.com/paddle/v2/paddle.js"></script>

    <script>
        'use strict';

        @if (config('settings.paddle_mode') == 'sandbox')
            Paddle.Environment.set('sandbox');
        @endif

        Paddle.Initialize({
            token: '{{ config('settings.paddle_client_token') }}'
        });

        Paddle.Checkout.open({
            settings: {
                variant: 'one-page',
                displayMode: "overlay",
                theme: '{{ (config('settings.dark_mode') == 1 ? 'dark' : 'light') }}',
                locale: '{{ config('app.locale') }}',
                allowLogout: false,
                showAddTaxId: false,
                showAddDiscounts: false,
                successUrl: '{{ route('checkout.complete') }}',
                frameStyle: "width: 100%; min-width: 312px; background-color: transparent; border: none;"
            },
            items: [{
                priceId: '{{ $price['id'] }}',
                quantity: 1
            }]
        });
    </script>

    <div class="bg-base-1 d-flex align-items-center flex-fill">
        <div class="container py-6">
            <div class="row h-100 justify-content-center align-items-center py-3">
                <div class="col-lg-6">
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

@include('shared.sidebars.user')