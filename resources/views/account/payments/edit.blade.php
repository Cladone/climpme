@section('site_title', formatTitle([__('Edit'), __('Payment'), config('settings.title')]))

@include('shared.breadcrumbs', ['breadcrumbs' => [
    ['url' => request()->is('admin/*') ? route('admin.dashboard') : route('dashboard'), 'title' => request()->is('admin/*') ? __('Admin') : __('Home')],
    ['url' => request()->is('admin/*') ? route('admin.payments') : route('account.payments'), 'title' => __('Payments')],
    ['title' => __('Edit')],
]])

<h1 class="h2 mb-3 d-inline-block">{{ __('Edit') }}</h1>

<div class="card border-0 shadow-sm">
    <div class="card-header align-items-center">
        <div class="row">
            <div class="col">
                <div class="font-weight-medium py-1">{{ __('Payment') }}</div>
            </div>

            <div class="col-auto">
                <div class="form-row">
                    <div class="col">
                        @include('account.payments.partials.menu')
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body mb-n3">
        @include('shared.message')

        <form action="{{ route('admin.payments.edit', $payment->id) }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-12 col-lg-6 mb-3">
                    <div class="text-muted">{{ __('Plan') }}</div>
                    @if (request()->is('admin/*'))
                        <a href="{{ route('admin.plans.edit', ['id' => $payment->product->id]) }}">{{ $payment->product->name }}</a>
                    @else
                        <div>{{ $payment->product->name }}</div>
                    @endif
                </div>

                <div class="col-12 col-lg-6 mb-3">
                    <div class="text-muted">{{ __('Payment ID') }}</div>
                    <div>{{ $payment->payment_id }}</div>
                </div>

                <div class="col-12 col-lg-6 mb-3">
                    <div class="text-muted">{{ __('Processor') }}</div>
                    <div>{{ config('payment.processors.' . $payment->processor)['name'] }}</div>
                </div>

                <div class="col-12 col-lg-6 mb-3">
                    <div class="text-muted">{{ __('Amount') }}</div>
                    <div>{{ formatMoney($payment->amount, $payment->plan->currency) }} {{ $payment->plan->currency }} / <span class="text-lowercase">{{ $payment->interval == 'month' ? __('Month') : __('Year') }}</span></div>
                </div>

                <div class="col-12 col-lg-6 mb-3">
                    <div class="text-muted">{{ __('Status') }}</div>
                    <div>
                        @if($payment->status == 'completed')
                            {{ __('Completed') }}
                        @elseif($payment->status == 'pending')
                            {{ __('Pending') }}
                        @else
                            {{ __('Cancelled') }}
                        @endif
                    </div>
                </div>

                @if((request()->is('admin/*') && in_array($payment->status, ['completed', 'cancelled'])) || $payment->status == 'completed')
                    <div class="col-12 col-lg-6 mb-3">
                        <div class="text-muted">{{ __('Invoice') }}</div>
                        <div><a href="{{ (request()->is('admin/*') ? route('admin.invoices.show', $payment->id) : route('account.invoices.show', $payment->id)) }}">{{ $payment->invoice_id }}</a></div>
                    </div>
                @endif

                <div class="col-12 col-lg-6 mb-3">
                    <div class="text-muted">{{ __('Created at') }}</div>
                    <div>{{ $payment->created_at->tz(Auth::user()->timezone ?? config('app.timezone'))->format(__('Y-m-d')) }}</div>
                </div>
            </div>
        </form>
    </div>
</div>

@if(request()->is('admin/*'))
    <div class="row m-n2 pt-3">
        <div class="col-12 col-md-6 col-lg-4 p-2">
            <a href="{{ route('admin.users.edit', ['id' => $payment->user->id]) }}" class="text-decoration-none text-dark">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center text-truncate">
                        <img src="{{ $payment->user->avatar_url }}" alt="{{ $payment->user->name }}" class="width-8 height-8 rounded-circle">

                        <span class="font-weight-medium text-decoration-none text-truncate {{ (__('lang_dir') == 'rtl' ? 'ml-3 mr-2' : 'mr-2 ml-3') }}">{{ $payment->user->name }}</span>

                        @include((__('lang_dir') == 'rtl' ? 'icons.chevron-left' : 'icons.chevron-right'), ['class' => 'flex-shrink-0 width-3 height-3 fill-current ' . (__('lang_dir') == 'rtl' ? 'mr-auto' : 'ml-auto')])
                    </div>
                </div>
            </a>
        </div>
    </div>
@endif
