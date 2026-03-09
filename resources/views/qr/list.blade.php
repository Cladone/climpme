@section('site_title', formatTitle([__('QR Codes'), config('settings.title')]))

<style>
    @media (max-width: 767px) {
        .hide-on-mobile {
            display: none;
        }
    }
</style>

@include('shared.breadcrumbs', ['breadcrumbs' => [
    ['url' => request()->is('admin/*') ? route('admin.dashboard') : route('dashboard'),
     'title' => request()->is('admin/*') ? __('Admin') : __('Home')],
    ['title' => __('QR Codes')],
]])

{{-- Page heading OUTSIDE the card --}}
<h1 class="h3 mb-3">{{ __('QR Codes') }}</h1>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex align-items-center">
        <form method="GET" class="ml-auto d-flex align-items-center" style="gap:.5rem;">
            <input name="q" value="{{ $q ?? '' }}" class="form-control" placeholder="{{ __('Search') }}">
            <select name="sort" class="custom-select" onchange="this.form.submit()">
                <option value="created_at_desc" @selected(($sort ?? '')==='created_at_desc')>{{ __('Newest') }}</option>
                <option value="created_at_asc" @selected(($sort ?? '')==='created_at_asc')>{{ __('Oldest') }}</option>
            </select>
        </form>

        <a href="{{ route('qr.create') }}" class="btn btn-success ml-2">
            {{ __('New') }}
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-borderless mb-0">
            <thead>
                <tr class="text-muted">
                    <th class="pl-3" style="width:64px;">{{ __('QR') }}</th>
                    <th>{{ __('Title') }}</th>
                    <th class="hide-on-mobile">{{ __('Short URL') }}</th>
                    <th class="hide-on-mobile text-right">{{ __('Date created') }}</th>
                    <th style="width:48px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($links as $link)
                    <tr>
                        <td>
                            @if($link->qr_path)
                                <img src="{{ Storage::disk('public')->url($link->qr_path) }}?t={{ $link->updated_at->timestamp }}"
                                     alt="QR" style="width:48px;height:48px;object-fit:contain;">
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        <td class="text-truncate" style="max-width:320px;">
                            <a href="{{ route('qr.builder', $link->id) }}" class="font-weight-medium">
                                {{ $link->title ?: $link->alias }}
                            </a>
                            <div class="text-muted small text-truncate">{{ $link->url }}</div>
                        </td>

                        <td class="hide-on-mobile text-truncate" style="max-width:280px;">
                            <a href="{{ $link->shortUrl }}" target="_blank" rel="nofollow noopener">
                                {{ str_replace(['http://','https://'], '', $link->shortUrl) }}
                            </a>
                        </td>

                        <td class="hide-on-mobile text-right text-muted">{{ $link->created_at->diffForHumans() }}</td>

                        <td class="text-right">
                            <div class="dropdown">
                                <a href="#" class="btn btn-sm text-primary" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    @include('icons.more-horiz', ['class' => 'fill-current width-4 height-4'])
                                </a>
                                <div class="dropdown-menu dropdown-menu-right border-0 shadow">
                                    <a class="dropdown-item d-flex align-items-center" href="{{ route('qr.builder', $link->id) }}" target="_blank">
                                        @include('icons.qr', ['class' => 'text-muted fill-current width-4 height-4 ' . (__('lang_dir') == 'rtl' ? 'ml-3' : 'mr-3')])
                                        {{ __('Open builder') }}
                                    </a>
                                    @if($link->qr_path)
                                        <a class="dropdown-item d-flex align-items-center" href="{{ route('qr.download', $link->id) }}">
                                            @include('icons.open-in-new', ['class' => 'text-muted fill-current width-4 height-4 ' . (__('lang_dir') == 'rtl' ? 'ml-3' : 'mr-3')])
                                            {{ __('Download') }}
                                        </a>
                                    @endif
                                    <a class="dropdown-item d-flex align-items-center" href="{{ $link->shortUrl }}" target="_blank" rel="nofollow noopener">
                                        @include('icons.open-in-new', ['class' => 'text-muted fill-current width-4 height-4 ' . (__('lang_dir') == 'rtl' ? 'ml-3' : 'mr-3')])
                                        {{ __('Open short link') }}
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            {{ __('No QR codes yet.') }}
                            <a href="{{ route('qr.create') }}" class="ml-2">{{ __('Create your first QR') }}</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($links->hasPages())
        <div class="card-footer bg-white">
            {{ $links->links() }}
        </div>
    @endif
</div>