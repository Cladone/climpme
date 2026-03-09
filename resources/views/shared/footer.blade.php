<footer id="footer" class="footer bg-base-0 {{ request()->is('admin/invoices/*') || request()->is('account/invoices/*') ? 'd-print-none' : '' }}">
    <div class="container py-5">
        @if(!isset($footer['menu']['removed']))
            <div class="row align-items-center">
                <!-- Footer links -->
                <div class="col-12 col-lg d-flex mb-3 mb-lg-0">
                    <ul class="nav p-0 mx-n2 d-flex flex-wrap flex-row justify-content-center justify-content-lg-start">
                        @foreach ($footer['menu']['links'] ?? [__('Contact') => route('contact'), __('Terms') => config('settings.legal_terms_url'), __('Privacy') => config('settings.legal_privacy_url'), __('Developers') => route('developers')] as $title => $url)
                            <li class="nav-item">
                                <a href="{{ $url }}" class="nav-link py-1 text-muted">{{ $title }}</a>
                            </li>
                        @endforeach
                        @if (!isset($footer['menu']['links']))
                            @foreach ($footerPages as $page)
                                <li class="nav-item">
                                    <a href="{{ route('pages.show', $page['slug']) }}" class="nav-link py-1">{{ __($page['name']) }}</a>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                </div>

                <!-- Social icons -->
                <div class="col-12 col-lg-auto d-flex justify-content-center justify-content-lg-end">
                    <ul class="nav p-0 mx-n2 d-flex flex-row flex-wrap justify-content-center justify-content-lg-end">
                        @foreach ($footer['menu']['socials'] ?? [
                            'Facebook' => config('settings.social_facebook'),
                            'X' => config('settings.social_x'),
                            'Instagram' => config('settings.social_instagram'),
                            'YouTube' => config('settings.social_youtube'),
                            'GitHub' => config('settings.social_github'),
                            'Discord' => config('settings.social_discord'),
                            'Reddit' => config('settings.social_reddit'),
                            'Threads' => config('settings.social_threads'),
                            'TikTok' => config('settings.social_tiktok'),
                            'LinkedIn' => config('settings.social_linkedin'),
                            'Tumblr' => config('settings.social_tumblr'),
                            'Pinterest' => config('settings.social_pinterest')
                        ] as $title => $url)
                            @if($url)
                                <li class="nav-item d-flex">
                                    <a href="{{ $url }}" class="nav-link px-2 py-1 text-secondary d-flex align-items-center" data-tooltip="true" title="{{ $title }}" rel="nofollow noreferrer noopener">
                                        @include('icons.' . strtolower($title), ['class' => 'fill-current width-5 height-5'])
                                        <span class="sr-only">{{ $title }}</span>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>

            <hr class="{{ (isset($footer['menu']['links']) && empty($footer['menu']['links']) && isset($footer['menu']['socials']) && empty($footer['menu']['socials']) ? 'd-none' : '') }}">
        @endif

        <div class="row align-items-center">
            <!-- Copyright -->
            <div class="col-12 col-lg order-2 order-lg-1 footer-copyright">
                @if(!isset($footer['copyright']['removed']))
                    <div class="text-muted py-1">
                        {{ __('© :year :name.', ['year' => now()->year, 'name' => $footer['copyright']['name'] ?? config('settings.title')]) }}
                        {{ __('All rights reserved.') }}
                    </div>
                @endif
            </div>

            <!-- Utility links -->
            <div class="col-12 col-lg-auto order-1 order-lg-2 d-flex justify-content-center justify-content-lg-end flex-wrap">
                <div class="nav p-0 mx-n2 d-flex flex-row flex-wrap justify-content-center justify-content-lg-end">
                    <!-- Dark mode toggle -->
                    <div class="nav-item d-flex">
                        <a href="#" class="nav-link py-1 d-flex align-items-center text-secondary" id="dark-mode" data-tooltip="true" title="{{ __('Change theme') }}">
                            @include('icons.contrast', ['class' => 'width-4 height-4 fill-current ' . (__('lang_dir') == 'rtl' ? 'ml-2' : 'mr-2')])
                            <span class="text-muted" data-text-light="{{ __('Light') }}" data-text-dark="{{ __('Dark') }}">
                                {{ (config('settings.dark_mode') == 1 ? __('Dark') : __('Light')) }}
                            </span>
                        </a>
                    </div>

                    <!-- Language toggle -->
                    @if(count(config('app.locales')) > 1)
                        <div class="nav-item d-flex">
                            <a href="#" class="nav-link py-1 d-flex align-items-center text-secondary" data-toggle="modal" data-target="#change-language-modal" data-tooltip="true" title="{{ __('Change language') }}">
                                @include('icons.language', ['class' => 'width-4 height-4 fill-current ' . (__('lang_dir') == 'rtl' ? 'ml-2' : 'mr-2')])
                                <span class="text-muted">{{ config('app.locales')[config('app.locale')]['name'] }}</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Language modal -->
    @if(count(config('app.locales')) > 1)
        <div class="modal fade" id="change-language-modal" tabindex="-1" role="dialog" aria-labelledby="change-language-modal-label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h6 class="modal-title" id="change-language-modal-label">{{ __('Change language') }}</h6>
                        <button type="button" class="close d-flex align-items-center justify-content-center width-12 height-14" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true" class="d-flex align-items-center">
                                @include('icons.close', ['class' => 'fill-current width-4 height-4'])
                            </span>
                        </button>
                    </div>
                    <form action="{{ route('locale') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                @foreach(config('app.locales') as $code => $language)
                                    <div class="col-6">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="i-language-{{ $code }}" name="locale" class="custom-control-input" value="{{ $code }}" @if(config('app.locale') == $code) checked @endif>
                                            <label class="custom-control-label" for="i-language-{{ $code }}" lang="{{ $code }}">{{ $language['name'] }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if(!isset($footer['cookie_law']['removed']))
        @include('shared.cookie-law')
    @endif
</footer>
