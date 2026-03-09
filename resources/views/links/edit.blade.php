@section('site_title', formatTitle([__('Edit'), __('Link'), config('settings.title')]))

@include('shared.breadcrumbs', ['breadcrumbs' => [
    ['url' => request()->is('admin/*') ? route('admin.dashboard') : route('dashboard'), 'title' => request()->is('admin/*') ? __('Admin') : __('Home')],
    ['url' => request()->is('admin/*') ? route('admin.links') : route('links'), 'title' => __('Links')],
    ['title' => __('Edit')],
]])

<div class="d-flex">
    <h1 class="h2 mb-0 flex-grow-1 text-break">{{ __('Edit') }}</h1>
</div>

<div class="card border-0 shadow-sm mt-3">
    <div class="card-header align-items-center">
        <div class="row">
            <div class="col">
                <div class="font-weight-medium py-1">{{ __('Link') }}</div>
            </div>

            <div class="col-auto">
                <div class="form-row flex-nowrap">
                    <div class="col">
                        @include('shared.buttons.copy-link', ['class' => 'btn-sm text-primary'])
                    </div>

                    <div class="col">
                        @include('links.partials.menu')
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        @include('shared.message')

        <form action="{{ request()->is('admin/*') ? route('admin.links.edit', $link->id) : route('links.edit', $link->id) }}" method="post" enctype="multipart/form-data" autocomplete="off" id="form-link">
            @csrf

            @if(request()->is('admin/*'))
                <input type="hidden" name="user_id" value="{{ isset($link->user) ? $link->user->id : '0' }}">
            @endif

            <div class="form-group">
                <label for="i-url">{{ __('URL') }}</label>
                <div class="input-group">
                    <input type="text" dir="ltr" name="url" class="form-control{{ $errors->has('url') ? ' is-invalid' : '' }}" autocapitalize="none" spellcheck="false" id="i-url" value="{{ old('url') ?? $link->url }}">
                    <div class="input-group-append" data-tooltip="true" title="{{ __('UTM builder') }}">
                        <a href="#" class="btn text-secondary bg-transparent input-group-text d-flex align-items-center" data-toggle="modal" data-target="#utm-modal" id="utm-builder">
                            @include('icons.label', ['class' => 'fill-current width-4 height-4'])
                        </a>
                    </div>
                </div>
                @if ($errors->has('url'))
                    <span class="invalid-feedback d-block" role="alert">
                        <strong>{{ $errors->first('url') }}</strong>
                    </span>
                @endif
            </div>

            <div class="form-row">
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <div class="d-flex align-items-center mb-2">
                            <label for="i-domain" class="d-flex align-items-center mb-0">
                                {{ __('Domain') }}
                            </label>
                            @cannot('domains', ['App\Models\Link'])
                                @if(paymentProcessors())
                                    <a href="{{ route('pricing') }}" data-tooltip="true" title="{{ __('Unlock feature') }}" class="d-flex align-items-center {{ (__('lang_dir') == 'rtl' ? 'mr-2' : 'ml-2') }}">@include('icons.lock-open', ['class' => 'fill-current text-primary width-4 height-4'])</a>
                                @endif
                            @endcannot
                        </div>
                        <select name="domain_id" id="i-domain" class="custom-select{{ $errors->has('domain_id') ? ' is-invalid' : '' }}" disabled>
                            <option value="">{{ __('None') }}</option>
                            @foreach($domains as $domain)
                                <option value="{{ $domain->id }}" @if((old('domain_id') !== null && old('domain_id') == $domain->id) || ($link->domain_id == $domain->id && old('domain_id') == null)) selected @endif>{{ $domain->name }}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('domain_id'))
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $errors->first('domain_id') }}</strong>
                            </span>
                        @endif
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <div class="d-flex align-items-center mb-2">
                            <label for="i-alias" class="d-flex align-items-center mb-0">
                                {{ __('Alias') }}
                            </label>
                        </div>
                        <input type="text" name="alias" class="form-control{{ $errors->has('alias') ? ' is-invalid' : '' }}" autocapitalize="none" spellcheck="false" id="i-alias" value="{{ old('alias') ?? $link->alias }}">
                        @if ($errors->has('alias'))
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $errors->first('alias') }}</strong>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="d-flex align-items-center mb-2">
                    <label for="i-space" class="d-flex align-items-center mb-0">
                        {{ __('Space') }}
                    </label>
                    @cannot('spaces', ['App\Models\Link'])
                        @if(paymentProcessors())
                            <a href="{{ route('pricing') }}" data-tooltip="true" title="{{ __('Unlock feature') }}" class="d-flex align-items-center {{ (__('lang_dir') == 'rtl' ? 'mr-2' : 'ml-2') }}">@include('icons.lock-open', ['class' => 'fill-current text-primary width-4 height-4'])</a>
                        @endif
                    @endcannot
                </div>
                <select name="space_id" id="i-space" class="custom-select{{ $errors->has('space_id') ? ' is-invalid' : '' }}" @cannot('spaces', ['App\Models\Link']) disabled @endcannot>
                    <option value="">{{ __('None') }}</option>
                    @foreach($spaces as $space)
                        <option value="{{ $space->id }}" @if((old('space_id') !== null && old('space_id') == $space->id) || ($link->space_id == $space->id && old('space_id') == null)) selected @endif>{{ $space->name }}</option>
                    @endforeach
                </select>
                @if ($errors->has('space_id'))
                    <span class="invalid-feedback d-block" role="alert">
                        <strong>{{ $errors->first('space_id') }}</strong>
                    </span>
                @endif
            </div>

            <div class="form-group">
                <div class="d-flex align-items-center mb-2">
                    <label for="i-pixel-ids" class="d-flex align-items-center mb-0">
                        {{ __('Pixels') }}
                    </label>
                    @cannot('pixels', ['App\Models\Link'])
                        @if(paymentProcessors())
                            <a href="{{ route('pricing') }}" data-tooltip="true" title="{{ __('Unlock feature') }}" class="d-flex align-items-center {{ (__('lang_dir') == 'rtl' ? 'mr-2' : 'ml-2') }}">@include('icons.lock-open', ['class' => 'fill-current text-primary width-4 height-4'])</a>
                        @endif
                    @endcannot
                </div>

                <input type="hidden" name="pixel_ids[]" value="">
                <select name="pixel_ids[]" id="i-pixel-ids" class="custom-select{{ $errors->has('pixel_ids') ? ' is-invalid' : '' }}" size="{{ (count($pixels) == 0 ? 1 : 3) }}" @cannot('pixels', ['App\Models\Link']) disabled @endcannot multiple>
                    @foreach($pixels as $pixel)
                        <option value="{{ $pixel->id }}" @if((old('pixel_ids') !== null && is_array(old('pixel_ids')) && in_array($pixel->id, old('pixel_ids'))) || ($link->pixels->contains('id', $pixel->id) && old('pixel_ids') == null)) selected @endif>{{ $pixel->name }} ({{ config('pixels')[$pixel->type]['name'] }})</option>
                    @endforeach
                </select>
                @if ($errors->has('pixel_ids'))
                    <span class="invalid-feedback d-block" role="alert">
                        <strong>{{ $errors->first('pixel_ids') }}</strong>
                    </span>
                @endif
            </div>

            <div class="pb-3">
                <button class="btn btn-light d-block w-100 d-flex align-items-center justify-content-center" type="button" data-toggle="collapse" data-target="#collapseSchedules" aria-expanded="{{ ($errors->has('active_period_start_at') || $errors->has('active_period_end_at') || $errors->has('clicks_limit') || $errors->has('expiration_url') ? 'true' : 'false') }}" aria-controls="collapseSchedules">
                    @include('icons.date-range', ['class' => 'width-4 height-4 fill-current ' . (__('lang_dir') == 'rtl' ? 'ml-2' : 'mr-2')]) {{ __('Schedules') }}
                </button>

                <div class="collapse {{ ($errors->has('active_period_start_at') || $errors->has('active_period_end_at') || $errors->has('clicks_limit') || $errors->has('expiration_url') ? 'show' : '') }}" id="collapseSchedules">
                    <div class="form-row mt-3">
                        <div class="col-12"><label for="i-schedule-start-at">{{ __('Active period') }}</label></div>
                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <label for="i-schedule-start-at" class="input-group-text">{{ __('Start') }}</label>
                                    </div>
                                    <input type="datetime-local" dir="ltr" name="active_period_start_at" class="form-control{{ $errors->has('active_period_start_at') ? ' is-invalid' : '' }}" id="i-schedule-start-at" value="{{ old('active_period_start_at') ?? ($link->active_period_start_at ? $link->active_period_start_at->tz(Auth::user()->timezone ?? config('app.timezone'))->format('Y-m-d H:i') : '') }}" placeholder="{{ \Carbon\Carbon::now()->tz(Auth::user()->timezone ?? config('app.timezone'))->format('Y-m-d H:i') }}">
                                </div>
                                @if ($errors->has('active_period_start_at'))
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $errors->first('active_period_start_at') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <label for="i-schedule-end-at" class="input-group-text">{{ __('End') }}</label>
                                    </div>
                                    <input type="datetime-local" dir="ltr" name="active_period_end_at" class="form-control{{ $errors->has('active_period_end_at') ? ' is-invalid' : '' }}" id="i-schedule-end-at" value="{{ old('active_period_end_at') ?? ($link->active_period_end_at ? $link->active_period_end_at->tz(Auth::user()->timezone ?? config('app.timezone'))->format('Y-m-d H:i') : '') }}" placeholder="{{ \Carbon\Carbon::now()->tz(Auth::user()->timezone ?? config('app.timezone'))->format('Y-m-d H:i') }}">
                                </div>
                                @if ($errors->has('active_period_end_at'))
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $errors->first('active_period_end_at') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-12 mt-n3 mb-3">
                            <small class="form-text text-muted">{{ __('The period during which the link will be active.') }}</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="d-flex align-items-center mb-2">
                            <label for="i-clicks-limit" class="d-flex align-items-center mb-0">
                                {{ __('Clicks limit') }}
                            </label>
                            @cannot('expiration', ['App\Models\Link'])
                                @if(paymentProcessors())
                                    <a href="{{ route('pricing') }}" data-tooltip="true" title="{{ __('Unlock feature') }}" class="d-flex align-items-center {{ (__('lang_dir') == 'rtl' ? 'mr-2' : 'ml-2') }}">@include('icons.lock-open', ['class' => 'fill-current text-primary width-4 height-4'])</a>
                                @endif
                            @endcannot
                        </div>
                        <input type="number" name="clicks_limit" id="i-clicks-limit" class="form-control {{ $errors->has('clicks_limit') ? ' is-invalid' : '' }}" autocapitalize="none" spellcheck="false" value="{{ old('clicks_limit') ?? $link->clicks_limit }}" @cannot('expiration', ['App\Models\Link']) disabled @endcannot>
                        @if ($errors->has('clicks_limit'))
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $errors->first('clicks_limit') }}</strong>
                            </span>
                        @endif
                        <small class="form-text text-muted">{{ __('The number of clicks the link will be active for.') }}</small>
                    </div>

                    <div class="form-group mb-0">
                        <div class="d-flex align-items-center mb-2">
                            <label for="i-expiration-url" class="d-flex align-items-center mb-0">
                                {{ __('Expiration URL') }}
                            </label>
                            @cannot('expiration', ['App\Models\Link'])
                                @if(paymentProcessors())
                                    <a href="{{ route('pricing') }}" data-tooltip="true" title="{{ __('Unlock feature') }}" class="d-flex align-items-center {{ (__('lang_dir') == 'rtl' ? 'mr-2' : 'ml-2') }}">@include('icons.lock-open', ['class' => 'fill-current text-primary width-4 height-4'])</a>
                                @endif
                            @endcannot
                        </div>
                        <div class="input-group">
                            <input type="text" dir="ltr" name="expiration_url" id="i-expiration-url" class="form-control{{ $errors->has('expiration_url') ? ' is-invalid' : '' }}" autocapitalize="none" spellcheck="false" value="{{ old('expiration_url') ?? $link->expiration_url }}" @cannot('expiration', ['App\Models\Link']) disabled @endcannot>
                        </div>
                        @if ($errors->has('expiration_url'))
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $errors->first('expiration_url') }}</strong>
                            </span>
                        @endif
                        <small class="form-text text-muted">{{ __('The URL to redirect to once the link has expired.') }}</small>
                    </div>
                </div>
            </div>

            <div class="pb-3">
                <button class="btn btn-light d-block w-100 d-flex align-items-center justify-content-center" type="button" data-toggle="collapse" data-target="#collapseProtections" aria-expanded="{{ ($errors->has('redirect_password') || $errors->has('sensitive_content') ? 'true' : 'false') }}" aria-controls="collapseProtections">
                    @include('icons.security', ['class' => 'width-4 height-4 fill-current ' . (__('lang_dir') == 'rtl' ? 'ml-2' : 'mr-2')]) {{ __('Protections') }}
                </button>

                <div class="collapse {{ ($errors->has('redirect_password') || $errors->has('sensitive_content') ? 'show' : '') }}" id="collapseProtections">
                    <div class="form-group mt-3">
                        <div class="d-flex align-items-center mb-2">
                            <label for="i-redirect-password" class="d-flex align-items-center mb-0">
                                {{ __('Redirect password') }}
                            </label>
                            @cannot('redirectPassword', ['App\Models\Link'])
                                @if(paymentProcessors())
                                    <a href="{{ route('pricing') }}" data-tooltip="true" title="{{ __('Unlock feature') }}" class="d-flex align-items-center {{ (__('lang_dir') == 'rtl' ? 'mr-2' : 'ml-2') }}">@include('icons.lock-open', ['class' => 'fill-current text-primary width-4 height-4'])</a>
                                @endif
                            @endcannot
                        </div>
                        <div class="input-group">
                            <input type="password" name="redirect_password" class="form-control{{ $errors->has('redirect_password') ? ' is-invalid' : '' }}" id="i-redirect-password" value="{{ old('redirect_password') ?? $link->redirect_password }}" autocomplete="new-password" @cannot('redirectPassword', ['App\Models\Link']) disabled @endcannot>
                            <div class="input-group-append">
                                <div class="input-group-text cursor-pointer" data-tooltip="true" data-title="{{ __('Show password') }}" data-password="i-redirect-password" data-password-show="{{ __('Show password') }}" data-password-hide="{{ __('Hide password') }}">@include('icons.visibility_off', ['class' => 'width-4 height-4 fill-current text-muted'])@include('icons.visibility', ['class' => 'width-4 height-4 fill-current text-muted d-none'])</div>
                            </div>
                        </div>
                        @if ($errors->has('redirect_password'))
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $errors->first('redirect_password') }}</strong>
                            </span>
                        @endif
                        <small class="form-text text-muted">{{ __('Require the user to enter a password to access the link.') }}</small>
                    </div>

                    <div class="form-group mb-0">
                        <div class="d-flex align-items-center mb-2">
                            <label for="i-sensitive-content" class="d-flex align-items-center mb-0">
                                {{ __('Sensitive content') }}
                            </label>
                        </div>
                        <select name="sensitive_content" id="i-sensitive-content" class="custom-select{{ $errors->has('sensitive_content') ? ' is-invalid' : '' }}">
                            @foreach([0 => __('No'), 1 => __('Yes')] as $key => $value)
                                <option value="{{ $key }}" @if ((old('sensitive_content') !== null && old('sensitive_content') == $key) || ($link->sensitive_content == $key && old('sensitive_content') == null)) selected @endif>{{ $value }}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('sensitive_content'))
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $errors->first('sensitive_content') }}</strong>
                            </span>
                        @endif
                        <small class="form-text text-muted">{{ __('Inform the user that the link contains sensitive content and require consent to access the link.') }}</small>
                    </div>
                </div>
            </div>

            <div class="pb-3">
                <button class="btn btn-light d-block w-100 d-flex align-items-center justify-content-center" type="button" data-toggle="collapse" data-target="#collapseStats" aria-expanded="{{ ($errors->has('privacy') ? 'true' : 'false') }}" aria-controls="collapseStats">
                    @include('icons.bar-chart', ['class' => 'width-4 height-4 fill-current ' . (__('lang_dir') == 'rtl' ? 'ml-2' : 'mr-2')]) {{ __('Stats') }}
                </button>

                <div class="collapse {{ ($errors->has('privacy') || $errors->has('password') ? 'show' : '') }}" id="collapseStats">
                    <div class="form-group mt-3 mb-0">
                        <div class="d-flex align-items-center mb-2">
                            <label for="i-privacy" class="d-flex align-items-center mb-0">
                                {{ __('Privacy') }}
                            </label>
                            @cannot('stats', ['App\Models\Link'])
                                @if(paymentProcessors())
                                    <a href="{{ route('pricing') }}" data-tooltip="true" title="{{ __('Unlock feature') }}" class="d-flex align-items-center {{ (__('lang_dir') == 'rtl' ? 'mr-2' : 'ml-2') }}">@include('icons.lock-open', ['class' => 'fill-current text-primary width-4 height-4'])</a>
                                @endif
                            @endcannot
                        </div>
                        <div class="form-group mb-0">
                            <div class="row mx-n2">
                                <div class="col-12 col-lg-4 px-2">
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="i-privacy1" name="privacy" class="custom-control-input{{ $errors->has('privacy') ? ' is-invalid' : '' }}" value="1" @if($link->privacy == 1 && old('privacy') == null || old('privacy') == 1) checked @endif @cannot('stats', ['App\Models\Link']) disabled @endcannot>
                                        <label class="custom-control-label w-100 d-flex flex-column" for="i-privacy1">
                                            <span>{{ __('Private') }}</span>
                                            <span class="small text-muted">{{ __('Stats accessible only by you.') }}</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-12 col-lg-4 px-2">
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="i-privacy0" name="privacy" class="custom-control-input{{ $errors->has('privacy') ? ' is-invalid' : '' }}" value="0" @if($link->privacy == 0 && old('privacy') == null || old('privacy') == 0 && old('privacy') != null) checked @endif @cannot('stats', ['App\Models\Link']) disabled @endcannot>
                                        <label class="custom-control-label w-100 d-flex flex-column" for="i-privacy0">
                                            <span>{{ __('Public') }}</span>
                                            <span class="small text-muted">{{ __('Stats accessible by anyone.') }}</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-12 col-lg-4 px-2">
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="i-privacy2" name="privacy" class="custom-control-input{{ $errors->has('privacy') ? ' is-invalid' : '' }}" value="2" @if($link->privacy == 2 && old('privacy') == null || old('privacy') == 2) checked @endif @cannot('stats', ['App\Models\Link']) disabled @endcannot>
                                        <label class="custom-control-label w-100 d-flex flex-column" for="i-privacy2">
                                            <span>{{ __('Password') }}</span>
                                            <span class="small text-muted">{{ __('Stats accessible by password.') }}</span>
                                        </label>

                                        <div id="input-password" class="{{ (((old('privacy') == 2) || (old('privacy') == null && $link->privacy == 2)) ? '' : 'd-none')}}">
                                            <div class="input-group mt-2">
                                                <input id="i-password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" value="{{ old('password') ?? $link->password }}" autocomplete="new-password">
                                                <div class="input-group-append">
                                                    <div class="input-group-text cursor-pointer" data-tooltip="true" data-title="{{ __('Show password') }}" data-password="i-password" data-password-show="{{ __('Show password') }}" data-password-hide="{{ __('Hide password') }}">@include('icons.visibility_off', ['class' => 'width-4 height-4 fill-current text-muted'])@include('icons.visibility', ['class' => 'width-4 height-4 fill-current text-muted d-none'])</div>
                                                </div>
                                            </div>
                                            @if ($errors->has('password'))
                                            <span class="invalid-feedback d-block" role="alert">
                                                <strong>{{ $errors->first('password') }}</strong>
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if ($errors->has('privacy'))
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $errors->first('privacy') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="pb-3">
                <button class="btn btn-light d-block w-100 d-flex align-items-center justify-content-center" type="button" data-toggle="collapse" data-target="#collapseTargets" aria-expanded="{{ ($errors->has('targets_type') || $errors->has('targets') || $errors->has('targets.*.key') || $errors->has('targets.*.value') ? 'true' : 'false') }}" aria-controls="collapseTargets">
                    @include('icons.gps-fixed', ['class' => 'width-4 height-4 fill-current ' . (__('lang_dir') == 'rtl' ? 'ml-2' : 'mr-2')]) {{ __('Targets') }}
                </button>

                <div class="collapse {{ ($errors->has('targets_type') || $errors->has('targets') || $errors->has('targets.*.key') || $errors->has('targets.*.value') ? 'show' : '') }}" id="collapseTargets">
                    <div class="form-group mt-3 mb-0">
                        <label for="i-targets-type">{{ __('Target') }}</label>

                        @if ($errors->has('targets'))
                            <span class="invalid-feedback d-block mt-0 mb-2" role="alert">
                                <strong>{{ $errors->first('targets') }}</strong>
                            </span>
                        @endif

                        <div class="mb-3">
                            <div class="form-group">
                                <select name="targets_type" id="i-targets-type" class="custom-select{{ $errors->has('targets_type') ? ' is-invalid' : '' }}">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach(config('targets') as $key => $value)
                                        <option value="{{ $key }}" @if((old('targets_type') !== null && old('targets_type') == $key) || ($link->targets_type == $key && old('targets_type') == null)) selected @endif>{{ __($value) }}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('targets_type'))
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $errors->first('targets_type') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="{{ old('targets_type') !== null ? (old('targets_type') && empty(old('targets_type')) ? '' : 'd-none') : (empty($link->targets_type) ? '' : 'd-none') }}" data-targets-container=""></div>

                            <div class="{{ old('targets_type') !== null ? (old('targets_type') == 'continents' ? '' : 'd-none') : ($link->targets_type == 'continents' ? '' : 'd-none') }}" data-targets-container="continents">
                                <input name="targets[empty][key]" type="hidden" disabled>
                                <input name="targets[empty][value]" type="hidden" disabled>

                                <div class="form-row form-group d-none" data-targets-input-template>
                                    <div class="col">
                                        <select name="continents_key[]" data-input="key" class="custom-select" disabled>
                                            <option value="" selected>{{ __('Continent') }}</option>
                                            @foreach(config('continents') as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col">
                                        <input type="text" dir="ltr" data-input="value" class="form-control" autocapitalize="none" spellcheck="false" placeholder="https://example.com" value="" disabled>
                                    </div>

                                    <div class="col-auto d-flex align-items-start">
                                        <button type="button" class="btn btn-outline-danger d-flex align-items-center input-delete">@include('icons.delete', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                    </div>
                                </div>

                                <div data-targets-inputs="true">
                                    @php
                                        if (old('targets') && old('targets_type') == 'continents') {
                                            $continentList = old('targets');
                                        } elseif($link->targets && $link->targets_type == 'continents') {
                                            $continentList = json_decode(json_encode($link->targets), true);
                                        } else {
                                            $continentList = [];
                                        }
                                    @endphp

                                    @foreach($continentList as $id => $continent)
                                        <div class="form-row form-group">
                                            <div class="col">
                                                <select name="targets[{{ $id }}][key]" data-input="key" class="custom-select{{ $errors->has('targets.'.$id.'.key') ? ' is-invalid' : '' }}">
                                                    <option value="">{{ __('Continent') }}</option>
                                                    @foreach(config('continents') as $key => $value)
                                                        <option value="{{ $key }}" @if($continent['key'] == $key) selected @endif>{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                                @if ($errors->has('targets.'.$id.'.key'))
                                                    <span class="invalid-feedback d-block" role="alert">
                                                        <strong>{{ $errors->first('targets.'.$id.'.key') }}</strong>
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="col">
                                                <input type="text" dir="ltr" name="targets[{{ $id }}][value]" data-input="value" class="form-control{{ $errors->has('targets.'.$id.'.value') ? ' is-invalid' : '' }}" autocapitalize="none" spellcheck="false" placeholder="https://example.com" value="{{ $continent['value'] }}">
                                                @if ($errors->has('targets.'.$id.'.value'))
                                                    <span class="invalid-feedback d-block" role="alert">
                                                        <strong>{{ $errors->first('targets.'.$id.'.value') }}</strong>
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="col-auto d-flex align-items-start">
                                                <button type="button" class="btn btn-outline-danger d-flex align-items-center input-delete">@include('icons.delete', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @can('targets', ['App\Models\Link'])
                                    <button type="button" class="btn btn-outline-secondary input-add d-inline-flex align-items-center">@include('icons.add', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                @else
                                    @if(paymentProcessors())
                                        <a href="{{ route('pricing') }}" class="btn btn-outline-primary d-inline-flex align-items-center" data-tooltip="true" title="{{ __('Unlock feature') }}">@include('icons.lock-open', ['class' => 'width-4 height-4 fill-current'])&#8203;</a>
                                    @endif
                                @endcan
                            </div>

                            <div class="{{ old('targets_type') !== null ? (old('targets_type') == 'countries' ? '' : 'd-none') : ($link->targets_type == 'countries' ? '' : 'd-none') }}" data-targets-container="countries">
                                <input name="targets[empty][key]" type="hidden" disabled>
                                <input name="targets[empty][value]" type="hidden" disabled>

                                <div class="form-row form-group d-none" data-targets-input-template>
                                    <div class="col">
                                        <select name="countries_key[]" data-input="key" class="custom-select" disabled>
                                            <option value="" selected>{{ __('Country') }}</option>
                                            @foreach(config('countries') as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col">
                                        <input type="text" dir="ltr" data-input="value" class="form-control" autocapitalize="none" spellcheck="false" placeholder="https://example.com" value="" disabled>
                                    </div>

                                    <div class="col-auto d-flex align-items-start">
                                        <button type="button" class="btn btn-outline-danger d-flex align-items-center input-delete">@include('icons.delete', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                    </div>
                                </div>

                                <div data-targets-inputs="true">
                                    @php
                                        if (old('targets') && old('targets_type') == 'countries') {
                                            $countryList = old('targets');
                                        } elseif($link->targets && $link->targets_type == 'countries') {
                                            $countryList = json_decode(json_encode($link->targets), true);
                                        } else {
                                            $countryList = [];
                                        }
                                    @endphp

                                    @foreach($countryList as $id => $country)
                                        <div class="form-row form-group">
                                            <div class="col">
                                                <select name="targets[{{ $id }}][key]" data-input="key" class="custom-select{{ $errors->has('targets.'.$id.'.key') ? ' is-invalid' : '' }}">
                                                        <option value="">{{ __('Country') }}</option>
                                                        @foreach(config('countries') as $key => $value)
                                                            <option value="{{ $key }}" @if($country['key'] == $key) selected @endif>{{ $value }}</option>
                                                        @endforeach
                                                    </select>
                                                @if ($errors->has('targets.'.$id.'.key'))
                                                    <span class="invalid-feedback d-block" role="alert">
                                                        <strong>{{ $errors->first('targets.'.$id.'.key') }}</strong>
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="col">
                                                <input type="text" dir="ltr" name="targets[{{ $id }}][value]" data-input="value" class="form-control{{ $errors->has('targets.'.$id.'.value') ? ' is-invalid' : '' }}" autocapitalize="none" spellcheck="false" placeholder="https://example.com" value="{{ $country['value'] }}">
                                                @if ($errors->has('targets.'.$id.'.value'))
                                                    <span class="invalid-feedback d-block" role="alert">
                                                        <strong>{{ $errors->first('targets.'.$id.'.value') }}</strong>
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="col-auto d-flex align-items-start">
                                                <button type="button" class="btn btn-outline-danger d-flex align-items-center input-delete">@include('icons.delete', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @can('targets', ['App\Models\Link'])
                                    <button type="button" class="btn btn-outline-secondary input-add d-inline-flex align-items-center">@include('icons.add', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                @else
                                    @if(paymentProcessors())
                                        <a href="{{ route('pricing') }}" class="btn btn-outline-primary d-inline-flex align-items-center" data-tooltip="true" title="{{ __('Unlock feature') }}">@include('icons.lock-open', ['class' => 'width-4 height-4 fill-current'])&#8203;</a>
                                    @endif
                                @endcan
                            </div>

                            <div class="{{ old('targets_type') !== null ? (old('targets_type') == 'operating_systems' ? '' : 'd-none') : ($link->targets_type == 'operating_systems' ? '' : 'd-none') }}" data-targets-container="operating_systems">
                                <input name="targets[empty][key]" type="hidden" disabled>
                                <input name="targets[empty][value]" type="hidden" disabled>

                                <div class="form-row form-group d-none" data-targets-input-template>
                                    <div class="col">
                                        <select name="operating_systems_key[]" data-input="key" class="custom-select" disabled>
                                            <option value="" selected>{{ __('Operating system') }}</option>
                                            @foreach(config('operating_systems') as $operatingSystem)
                                                <option value="{{ $operatingSystem }}">{{ $operatingSystem }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col">
                                        <input type="text" dir="ltr" data-input="value" class="form-control" autocapitalize="none" spellcheck="false" placeholder="https://example.com" value="" disabled>
                                    </div>

                                    <div class="col-auto d-flex align-items-start">
                                        <button type="button" class="btn btn-outline-danger d-flex align-items-center input-delete">@include('icons.delete', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                    </div>
                                </div>

                                <div data-targets-inputs="true">
                                    @php
                                        if (old('targets') && old('targets_type') == 'operating_systems') {
                                            $operatingSystemList = old('targets');
                                        } elseif($link->targets && $link->targets_type == 'operating_systems') {
                                            $operatingSystemList = json_decode(json_encode($link->targets), true);
                                        } else {
                                            $operatingSystemList = [];
                                        }
                                    @endphp

                                    @foreach($operatingSystemList as $id => $operatingSystem)
                                        <div class="form-row form-group">
                                            <div class="col">
                                                <select name="targets[{{ $id }}][key]" data-input="key" class="custom-select{{ $errors->has('targets.'.$id.'.key') ? ' is-invalid' : '' }}">
                                                    <option value="">{{ __('Operating system') }}</option>
                                                    @foreach(config('operating_systems') as $value)
                                                        <option value="{{ $value }}" @if($operatingSystem['key'] == $value) selected @endif>{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                                @if ($errors->has('targets.'.$id.'.key'))
                                                    <span class="invalid-feedback d-block" role="alert">
                                                        <strong>{{ $errors->first('targets.'.$id.'.key') }}</strong>
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="col">
                                                <input type="text" dir="ltr" name="targets[{{ $id }}][value]" data-input="value" class="form-control{{ $errors->has('targets.'.$id.'.value') ? ' is-invalid' : '' }}" autocapitalize="none" spellcheck="false" placeholder="https://example.com" value="{{ $operatingSystem['value'] }}">
                                                @if ($errors->has('targets.'.$id.'.value'))
                                                    <span class="invalid-feedback d-block" role="alert">
                                                        <strong>{{ $errors->first('targets.'.$id.'.value') }}</strong>
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="col-auto d-flex align-items-start">
                                                <button type="button" class="btn btn-outline-danger d-flex align-items-center input-delete">@include('icons.delete', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @can('targets', ['App\Models\Link'])
                                    <button type="button" class="btn btn-outline-secondary input-add d-inline-flex align-items-center">@include('icons.add', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                @else
                                    @if(paymentProcessors())
                                        <a href="{{ route('pricing') }}" class="btn btn-outline-primary d-inline-flex align-items-center" data-tooltip="true" title="{{ __('Unlock feature') }}">@include('icons.lock-open', ['class' => 'width-4 height-4 fill-current'])&#8203;</a>
                                    @endif
                                @endcan
                            </div>

                            <div class="{{ old('targets_type') !== null ? (old('targets_type') == 'browsers' ? '' : 'd-none') : ($link->targets_type == 'browsers' ? '' : 'd-none') }}" data-targets-container="browsers">
                                <input name="targets[empty][key]" type="hidden" disabled>
                                <input name="targets[empty][value]" type="hidden" disabled>

                                <div class="form-row form-group d-none" data-targets-input-template>
                                    <div class="col">
                                        <select name="browsers_key[]" data-input="key" class="custom-select" disabled>
                                            <option value="" selected>{{ __('Browser') }}</option>
                                            @foreach(config('browsers') as $value)
                                                <option value="{{ $value }}">{{ __($value) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col">
                                        <input type="text" dir="ltr" data-input="value" class="form-control" autocapitalize="none" spellcheck="false" placeholder="https://example.com" value="" disabled>
                                    </div>

                                    <div class="col-auto d-flex align-items-start">
                                        <button type="button" class="btn btn-outline-danger d-flex align-items-center input-delete">@include('icons.delete', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                    </div>
                                </div>

                                <div data-targets-inputs="true">
                                    @php
                                        if (old('targets') && old('targets_type') == 'browsers') {
                                            $browserList = old('targets');
                                        } elseif($link->targets && $link->targets_type == 'browsers') {
                                            $browserList = json_decode(json_encode($link->targets), true);
                                        } else {
                                            $browserList = [];
                                        }
                                    @endphp

                                    @foreach($browserList as $id => $browser)
                                        <div class="form-row form-group">
                                            <div class="col">
                                                <select name="targets[{{ $id }}][key]" data-input="key" class="custom-select{{ $errors->has('targets.'.$id.'.key') ? ' is-invalid' : '' }}">
                                                    <option value="">{{ __('Browser') }}</option>
                                                    @foreach(config('browsers') as $value)
                                                        <option value="{{ $value }}" @if($browser['key'] == $value) selected @endif>{{ __($value) }}</option>
                                                    @endforeach
                                                </select>
                                                @if ($errors->has('targets.'.$id.'.key'))
                                                    <span class="invalid-feedback d-block" role="alert">
                                                        <strong>{{ $errors->first('targets.'.$id.'.key') }}</strong>
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="col">
                                                <input type="text" dir="ltr" name="targets[{{ $id }}][value]" data-input="value" class="form-control{{ $errors->has('targets.'.$id.'.value') ? ' is-invalid' : '' }}" autocapitalize="none" spellcheck="false" placeholder="https://example.com" value="{{ $browser['value'] }}">
                                                @if ($errors->has('targets.'.$id.'.value'))
                                                    <span class="invalid-feedback d-block" role="alert">
                                                        <strong>{{ $errors->first('targets.'.$id.'.value') }}</strong>
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="col-auto d-flex align-items-start">
                                                <button type="button" class="btn btn-outline-danger d-flex align-items-center input-delete">@include('icons.delete', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @can('targets', ['App\Models\Link'])
                                    <button type="button" class="btn btn-outline-secondary input-add d-inline-flex align-items-center">@include('icons.add', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                @else
                                    @if(paymentProcessors())
                                        <a href="{{ route('pricing') }}" class="btn btn-outline-primary d-inline-flex align-items-center" data-tooltip="true" title="{{ __('Unlock feature') }}">@include('icons.lock-open', ['class' => 'width-4 height-4 fill-current'])&#8203;</a>
                                    @endif
                                @endcan
                            </div>

                            <div class="{{ old('targets_type') !== null ? (old('targets_type') == 'languages' ? '' : 'd-none') : ($link->targets_type == 'languages' ? '' : 'd-none') }}" data-targets-container="languages">
                                <input name="targets[empty][key]" type="hidden" disabled>
                                <input name="targets[empty][value]" type="hidden" disabled>

                                <div class="form-row form-group d-none" data-targets-input-template>
                                    <div class="col">
                                        <select name="languages_key[]" data-input="key" class="custom-select" disabled>
                                            <option value="" selected>{{ __('Language') }}</option>
                                            @foreach(config('languages') as $key => $value)
                                                <option value="{{ $key }}">{{ __($value['name']) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col">
                                        <input type="text" dir="ltr" data-input="value" class="form-control" autocapitalize="none" spellcheck="false" placeholder="https://example.com" value="" disabled>
                                    </div>

                                    <div class="col-auto d-flex align-items-start">
                                        <button type="button" class="btn btn-outline-danger d-flex align-items-center input-delete">@include('icons.delete', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                    </div>
                                </div>

                                <div data-targets-inputs="true">
                                    @php
                                        if (old('targets') && old('targets_type') == 'languages') {
                                            $languageList = old('targets');
                                        } elseif($link->targets && $link->targets_type == 'language') {
                                            $languageList = json_decode(json_encode($link->targets), true);
                                        } else {
                                            $languageList = [];
                                        }
                                    @endphp

                                    @foreach($languageList as $id => $language)
                                        <div class="form-row form-group">
                                            <div class="col">
                                                <select name="targets[{{ $id }}][key]" data-input="key" class="custom-select{{ $errors->has('targets.'.$id.'.key') ? ' is-invalid' : '' }}">
                                                    <option value="">{{ __('Language') }}</option>
                                                    @foreach(config('languages') as $key => $value)
                                                        <option value="{{ $key }}" @if($language['key'] == $key) selected @endif>{{ __($value['name']) }}</option>
                                                    @endforeach
                                                </select>
                                                @if ($errors->has('targets.'.$id.'.key'))
                                                    <span class="invalid-feedback d-block" role="alert">
                                                        <strong>{{ $errors->first('targets.'.$id.'.key') }}</strong>
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="col">
                                                <input type="text" dir="ltr" name="targets[{{ $id }}][value]" data-input="value" class="form-control{{ $errors->has('targets.'.$id.'.value') ? ' is-invalid' : '' }}" autocapitalize="none" spellcheck="false" placeholder="https://example.com" value="{{ $language['value'] }}">
                                                @if ($errors->has('targets.'.$id.'.value'))
                                                    <span class="invalid-feedback d-block" role="alert">
                                                        <strong>{{ $errors->first('targets.'.$id.'.value') }}</strong>
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="col-auto d-flex align-items-start">
                                                <button type="button" class="btn btn-outline-danger d-flex align-items-center input-delete">@include('icons.delete', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @can('targets', ['App\Models\Link'])
                                    <button type="button" class="btn btn-outline-secondary input-add d-inline-flex align-items-center">@include('icons.add', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                @else
                                    @if(paymentProcessors())
                                        <a href="{{ route('pricing') }}" class="btn btn-outline-primary d-inline-flex align-items-center" data-tooltip="true" title="{{ __('Unlock feature') }}">@include('icons.lock-open', ['class' => 'width-4 height-4 fill-current'])&#8203;</a>
                                    @endif
                                @endcan
                            </div>

                            <div class="{{ old('targets_type') !== null ? (old('targets_type') == 'devices' ? '' : 'd-none') : ($link->targets_type == 'devices' ? '' : 'd-none') }}" data-targets-container="devices">
                                <input name="targets[empty][key]" type="hidden" disabled>
                                <input name="targets[empty][value]" type="hidden" disabled>

                                <div class="form-row form-group d-none" data-targets-input-template>
                                    <div class="col">
                                        <select name="devices_key[]" data-input="key" class="custom-select" disabled>
                                            <option value="" selected>{{ __('Device') }}</option>
                                            @foreach(config('devices') as $key => $value)
                                                <option value="{{ $key }}">{{ __($value) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col">
                                        <input type="text" dir="ltr" data-input="value" class="form-control" autocapitalize="none" spellcheck="false" placeholder="https://example.com" value="" disabled>
                                    </div>

                                    <div class="col-auto d-flex align-items-start">
                                        <button type="button" class="btn btn-outline-danger d-flex align-items-center input-delete">@include('icons.delete', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                    </div>
                                </div>

                                <div data-targets-inputs="true">
                                    @php
                                        if (old('targets') && old('targets_type') == 'devices') {
                                            $deviceList = old('targets');
                                        } elseif($link->targets && $link->targets_type == 'device') {
                                            $deviceList = json_decode(json_encode($link->targets), true);
                                        } else {
                                            $deviceList = [];
                                        }
                                    @endphp

                                    @foreach($deviceList as $id => $device)
                                        <div class="form-row form-group">
                                            <div class="col">
                                                <select name="targets[{{ $id }}][key]" data-input="key" class="custom-select{{ $errors->has('targets.'.$id.'.key') ? ' is-invalid' : '' }}">
                                                    <option value="">{{ __('Device') }}</option>
                                                    @foreach(config('devices') as $key => $value)
                                                        <option value="{{ $key }}" @if($device['key'] == $key) selected @endif>{{ __($value) }}</option>
                                                    @endforeach
                                                </select>
                                                @if ($errors->has('targets.'.$id.'.key'))
                                                    <span class="invalid-feedback d-block" role="alert">
                                                        <strong>{{ $errors->first('targets.'.$id.'.key') }}</strong>
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="col">
                                                <input type="text" dir="ltr" name="targets[{{ $id }}][value]" data-input="value" class="form-control{{ $errors->has('targets.'.$id.'.value') ? ' is-invalid' : '' }}" autocapitalize="none" spellcheck="false" placeholder="https://example.com" value="{{ $device['value'] }}">
                                                @if ($errors->has('targets.'.$id.'.value'))
                                                    <span class="invalid-feedback d-block" role="alert">
                                                        <strong>{{ $errors->first('targets.'.$id.'.value') }}</strong>
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="col-auto d-flex align-items-start">
                                                <button type="button" class="btn btn-outline-danger d-flex align-items-center input-delete">@include('icons.delete', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @can('targets', ['App\Models\Link'])
                                    <button type="button" class="btn btn-outline-secondary input-add d-inline-flex align-items-center">@include('icons.add', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                @else
                                    @if(paymentProcessors())
                                        <a href="{{ route('pricing') }}" class="btn btn-outline-primary d-inline-flex align-items-center" data-tooltip="true" title="{{ __('Unlock feature') }}">@include('icons.lock-open', ['class' => 'width-4 height-4 fill-current'])&#8203;</a>
                                    @endif
                                @endcan
                            </div>

                            <div class="{{ old('targets_type') !== null ? (old('targets_type') == 'rotations' ? '' : 'd-none') : ($link->targets_type == 'rotations' ? '' : 'd-none') }}" data-targets-container="rotations">
                                <input name="targets[empty][value]" type="hidden" disabled>

                                <div class="form-row form-group d-none" data-targets-input-template>
                                    <div class="col">
                                        <input type="text" dir="ltr" data-input="value" class="form-control" autocapitalize="none" spellcheck="false" placeholder="https://example.com" value="" disabled>
                                    </div>

                                    <div class="col-auto d-flex align-items-start">
                                        <button type="button" class="btn btn-outline-danger d-flex align-items-center input-delete">@include('icons.delete', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                    </div>
                                </div>

                                <div data-targets-inputs="true">
                                    @php
                                        if (old('targets') && old('targets_type') == 'rotations') {
                                            $rotationList = old('targets');
                                        } elseif($link->targets && $link->targets_type == 'rotations') {
                                            $rotationList = json_decode(json_encode($link->targets), true);
                                        } else {
                                            $rotationList = [];
                                        }
                                    @endphp

                                    @foreach($rotationList as $id => $rotation)
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-row form-group">
                                                    <div class="col">
                                                        <input type="text" dir="ltr" name="targets[{{ $id }}][value]" data-input="value" class="form-control{{ $errors->has('targets.'.$id.'.value') ? ' is-invalid' : '' }}" autocapitalize="none" spellcheck="false" placeholder="https://example.com" value="{{ $rotation['value'] }}">
                                                        @if ($errors->has('targets.'.$id.'.value'))
                                                            <span class="invalid-feedback d-block" role="alert">
                                                                <strong>{{ $errors->first('targets.'.$id.'.value') }}</strong>
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <div class="col-auto d-flex align-items-start">
                                                        <button type="button" class="btn btn-outline-danger d-flex align-items-center input-delete">@include('icons.delete', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @can('targets', ['App\Models\Link'])
                                    <button type="button" class="btn btn-outline-secondary input-add d-inline-flex align-items-center">@include('icons.add', ['class' => 'width-4 height-4 fill-current'])&#8203;</button>
                                @else
                                    @if(paymentProcessors())
                                        <a href="{{ route('pricing') }}" class="btn btn-outline-primary d-inline-flex align-items-center" data-tooltip="true" title="{{ __('Unlock feature') }}">@include('icons.lock-open', ['class' => 'width-4 height-4 fill-current'])&#8203;</a>
                                    @endif
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" name="submit" class="btn btn-primary">{{ __('Save') }}</button>
        </form>
    </div>
</div>

@if(request()->is('admin/*'))
    <div class="row m-n2 pt-3">
        <div class="col-12 col-md-6 col-lg-4 p-2">
            @if(isset($link->user))
                <a href="{{ route('admin.users.edit', ['id' => $link->user->id]) }}" class="text-decoration-none text-dark">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center text-truncate">
                            <img src="{{ $link->user->avatar_url }}" alt="{{ $link->user->name }}" class="width-8 height-8 rounded-circle">

                            <span class="font-weight-medium text-decoration-none text-truncate {{ (__('lang_dir') == 'rtl' ? 'ml-3 mr-2' : 'mr-2 ml-3') }}">{{ $link->user->name ?? __('Guest') }}</span>

                            @include((__('lang_dir') == 'rtl' ? 'icons.chevron-left' : 'icons.chevron-right'), ['class' => 'flex-shrink-0 width-3 height-3 fill-current ' . (__('lang_dir') == 'rtl' ? 'mr-auto' : 'ml-auto')])
                        </div>
                    </div>
                </a>
            @else
                <div class="card border-0 shadow-sm h-100 cursor-default">
                    <div class="card-body d-flex align-items-center text-truncate">
                        <img src="{{ asset('img/user.png') }}" class="width-8 height-8 rounded-circle">

                        <span class="font-weight-medium text-decoration-none text-truncate {{ (__('lang_dir') == 'rtl' ? 'ml-3 mr-2' : 'mr-2 ml-3') }}">{{ __('Guest') }}</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-12 col-md-6 col-lg-4 p-2">
            <a href="{{ route('admin.pixels', ['link_id' => $link->id]) }}" class="text-decoration-none text-dark">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center text-truncate">
                        <div class="d-flex position-relative text-primary width-8 height-8 align-items-center justify-content-center flex-shrink-0">
                            <div class="position-absolute bg-primary opacity-10 top-0 right-0 bottom-0 left-0 border-radius-lg"></div>
                            @include('icons.filter-center-focus', ['class' => 'fill-current width-4 height-4'])
                        </div>

                        <span class="font-weight-medium text-decoration-none text-truncate {{ (__('lang_dir') == 'rtl' ? 'ml-3 mr-2' : 'mr-2 ml-3') }}">{{ __('Pixels') }}</span>

                        <span class="badge badge-primary">{{ number_format($link->pixels->count(), 0, __('.'), __(',')) }}</span>

                        @include((__('lang_dir') == 'rtl' ? 'icons.chevron-left' : 'icons.chevron-right'), ['class' => 'flex-shrink-0 width-3 height-3 fill-current ' . (__('lang_dir') == 'rtl' ? 'mr-auto' : 'ml-auto')])
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-4 p-2">
            <a href="{{ route('admin.spaces', ['link_id' => $link->id]) }}" class="text-decoration-none text-dark">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center text-truncate">
                        <div class="d-flex position-relative text-primary width-8 height-8 align-items-center justify-content-center flex-shrink-0">
                            <div class="position-absolute bg-primary opacity-10 top-0 right-0 bottom-0 left-0 border-radius-lg"></div>
                            @include('icons.workspaces', ['class' => 'fill-current width-4 height-4'])
                        </div>

                        <span class="font-weight-medium text-decoration-none text-truncate {{ (__('lang_dir') == 'rtl' ? 'ml-3 mr-2' : 'mr-2 ml-3') }}">{{ __('Spaces') }}</span>

                        <span class="badge badge-primary">{{ number_format(($link->space ? 1 : 0), 0, __('.'), __(',')) }}</span>

                        @include((__('lang_dir') == 'rtl' ? 'icons.chevron-left' : 'icons.chevron-right'), ['class' => 'flex-shrink-0 width-3 height-3 fill-current ' . (__('lang_dir') == 'rtl' ? 'mr-auto' : 'ml-auto')])
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-4 p-2">
            <a href="{{ route('admin.domains', ['link_id' => $link->id]) }}" class="text-decoration-none text-dark">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center text-truncate">
                        <div class="d-flex position-relative text-primary width-8 height-8 align-items-center justify-content-center flex-shrink-0">
                            <div class="position-absolute bg-primary opacity-10 top-0 right-0 bottom-0 left-0 border-radius-lg"></div>
                            @include('icons.website', ['class' => 'fill-current width-4 height-4'])
                        </div>

                        <span class="font-weight-medium text-decoration-none text-truncate {{ (__('lang_dir') == 'rtl' ? 'ml-3 mr-2' : 'mr-2 ml-3') }}">{{ __('Domains') }}</span>

                        <span class="badge badge-primary">{{ number_format(($link->domain ? 1 : 0), 0, __('.'), __(',')) }}</span>

                        @include((__('lang_dir') == 'rtl' ? 'icons.chevron-left' : 'icons.chevron-right'), ['class' => 'flex-shrink-0 width-3 height-3 fill-current ' . (__('lang_dir') == 'rtl' ? 'mr-auto' : 'ml-auto')])
                    </div>
                </div>
            </a>
        </div>
    </div>
@endif

@include('shared.modals.utm')
@include('shared.modals.share-link')