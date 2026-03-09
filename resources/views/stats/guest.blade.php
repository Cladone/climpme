@section('site_title', formatTitle([$link->alias, __('Overview'), __('Stats'), config('settings.title')]))

<div class="card border-0 rounded-top shadow-sm mb-3 overflow-hidden" id="trend-chart-container">
    <div class="px-3 border-bottom">
        <div class="row">
            <!-- Clicks -->
            <div class="col-12 col-lg-4 border-bottom border-bottom-lg-0 {{ (__('lang_dir') == 'rtl' ? 'border-left-lg' : 'border-right-lg')  }}">
                <div class="px-2 py-4">
                    <div class="d-flex">
                        <div class="text-truncate {{ (__('lang_dir') == 'rtl' ? 'ml-2' : 'mr-2') }}">
                            <div class="d-flex align-items-center text-truncate">
                                <div class="d-flex align-items-center justify-content-center bg-primary rounded width-4 height-4 flex-shrink-0 {{ (__('lang_dir') == 'rtl' ? 'ml-2' : 'mr-2') }}" id="clicks-legend"></div>

                                <div class="flex-grow-1 d-flex font-weight-bold text-truncate">
                                    <div class="text-truncate">{{ __('Clicks') }}</div>
                                    <div class="flex-shrink-0 d-flex align-items-center mx-2" data-tooltip="true" title="{{ __('The total number of clicks for the current dataset.') }}">
                                        @include('icons.info', ['class' => 'width-4 height-4 fill-current text-muted'])
                                    </div>
                                </div>
                            </div>
                            —
                        </div>

                        <div class="d-flex align-items-center {{ (__('lang_dir') == 'rtl' ? 'mr-auto' : 'ml-auto') }}">
                            <div class="h2 font-weight-bold mb-0">{{ $link->clicks }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Most -->
            <div class="col-12 col-lg-4 border-bottom border-bottom-lg-0 {{ (__('lang_dir') == 'rtl' ? 'border-left-lg' : 'border-right-lg')  }}">
                <div class="px-2 py-4">
                    <div class="row">
                        <div class="col">
                            <div class="d-flex align-items-center text-truncate">
                                <div class="flex-grow-1 d-flex font-weight-bold text-truncate">
                                    <div class="text-truncate">{{ __('Original link') }}</div>
                                    <div class="flex-shrink-0 d-flex align-items-center mx-2" data-tooltip="true" title="{{ __('The number of characters in the link.') }}">
                                        @include('icons.info', ['class' => 'width-4 height-4 fill-current text-muted'])
                                    </div>
                                </div>
                                <div class="align-self-end">
                                    {{ mb_strlen($link->url) }}
                                </div>
                            </div>

                            <div class="d-flex align-items-center text-truncate @if(mb_strlen($link->shortUrl) < mb_strlen($link->url)) text-danger @elseif(mb_strlen($link->shortUrl) > mb_strlen($link->url)) text-success @endif">
                                @if(mb_strlen($link->shortUrl) < mb_strlen($link->url))
                                    <div class="d-flex align-items-center justify-content-center width-4 height-4 {{ (__('lang_dir') == 'rtl' ? 'ml-2' : 'mr-2') }}">
                                        @include('icons.trending-down', ['class' => 'fill-current width-3 height-3'])
                                    </div>

                                    <div class="flex-grow-1 text-truncate {{ (__('lang_dir') == 'rtl' ? 'ml-2' : 'mr-2') }}">
                                        {{ mb_strtolower(__('Longer')) }}
                                    </div>
                                @elseif(mb_strlen($link->shortUrl) > mb_strlen($link->url))
                                    <div class="d-flex align-items-center justify-content-center width-4 height-4 {{ (__('lang_dir') == 'rtl' ? 'ml-2' : 'mr-2') }}">
                                        @include('icons.trending-up', ['class' => 'fill-current width-3 height-3'])
                                    </div>

                                    <div class="flex-grow-1 text-truncate {{ (__('lang_dir') == 'rtl' ? 'ml-2' : 'mr-2') }}">
                                        {{ mb_strtolower(__('Shorter')) }}
                                    </div>
                                @else
                                    <div class="flex-grow-1 text-truncate text-muted">
                                        {{ __('Identical') }}
                                    </div>
                                @endif

                                <div>{{ calcPercentageChange(mb_strlen($link->shortUrl), mb_strlen($link->url)) != 0 ? abs(calcPercentageChange(mb_strlen($link->shortUrl), mb_strlen($link->url))) . '%' :  '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Least -->
            <div class="col-12 col-lg-4">
                <div class="px-2 py-4">
                    <div class="row">
                        <div class="col">
                            <div class="d-flex align-items-center text-truncate">
                                <div class="flex-grow-1 d-flex font-weight-bold text-truncate">
                                    <div class="text-truncate">{{ __('Shortened link') }}</div>
                                    <div class="flex-shrink-0 d-flex align-items-center mx-2" data-tooltip="true" title="{{ __('The number of characters in the link.') }}">
                                        @include('icons.info', ['class' => 'width-4 height-4 fill-current text-muted'])
                                    </div>
                                </div>

                                <div class="align-self-end">{{ mb_strlen($link->shortUrl) }}</div>
                            </div>

                            <div class="d-flex align-items-center text-truncate @if(mb_strlen($link->shortUrl) < mb_strlen($link->url)) text-success @elseif(mb_strlen($link->shortUrl) > mb_strlen($link->url)) text-danger @endif">
                                @if(mb_strlen($link->shortUrl) < mb_strlen($link->url))
                                    <div class="d-flex align-items-center justify-content-center width-4 height-4 {{ (__('lang_dir') == 'rtl' ? 'ml-2' : 'mr-2') }}">
                                        @include('icons.trending-up', ['class' => 'fill-current width-3 height-3'])
                                    </div>

                                    <div class="flex-grow-1 text-truncate {{ (__('lang_dir') == 'rtl' ? 'ml-2' : 'mr-2') }}">
                                        {{ mb_strtolower(__('Shorter')) }}
                                    </div>
                                @elseif(mb_strlen($link->shortUrl) > mb_strlen($link->url))
                                    <div class="d-flex align-items-center justify-content-center width-4 height-4 {{ (__('lang_dir') == 'rtl' ? 'ml-2' : 'mr-2') }}">
                                        @include('icons.trending-down', ['class' => 'fill-current width-3 height-3'])
                                    </div>

                                    <div class="flex-grow-1 text-truncate {{ (__('lang_dir') == 'rtl' ? 'ml-2' : 'mr-2') }}">
                                        {{ mb_strtolower(__('Longer')) }}
                                    </div>
                                @else
                                    <div class="flex-grow-1 text-truncate text-muted">
                                        {{ __('Identical') }}
                                    </div>
                                @endif

                                <div>{{ calcPercentageChange(mb_strlen($link->url), mb_strlen($link->shortUrl)) != 0 ? abs(calcPercentageChange(mb_strlen($link->url), mb_strlen($link->shortUrl))) . '%' :  '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info">
    {{ __('This link has limited stats as it was created without using an account.') }}
</div>