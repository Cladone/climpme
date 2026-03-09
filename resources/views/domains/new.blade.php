@section('site_title', formatTitle([__('New'), __('Domain'), config('settings.title')]))

@include('shared.breadcrumbs', ['breadcrumbs' => [
    ['url' => request()->is('admin/*') ? route('admin.dashboard') : route('dashboard'), 'title' => request()->is('admin/*') ? __('Admin') : __('Home')],
    ['url' => request()->is('admin/*') ? route('admin.domains') : route('domains'), 'title' => __('Domains')],
    ['title' => __('New')],
]])

<h1 class="h2 mb-3 d-inline-block">{{ __('New') }}</h1>

<div class="card border-0 shadow-sm">
    <div class="card-header">
        <div class="row">
            <div class="col">
                <div class="font-weight-medium py-1">{{ __('Domain') }}</div>
            </div>
            <div class="col-auto d-flex align-items-center">
                <div class="badge badge-danger">{{ __('Expert level') }}</div>
            </div>
        </div>
    </div>
    <div class="card-body">
        @include('shared.message')

        @if(request()->is('admin/*'))
            <div class="alert alert-warning">{{ __('This domain will be available as a plan feature.') }}</div>
        @endif

        <form action="{{ request()->is('admin/*') ? route('admin.domains.new') : route('domains.new') }}" method="post" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label for="i-name">{{ __('Domain') }}</label>
                <input type="text" dir="ltr" name="name" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" id="i-name" value="{{ old('name') }}">
                @if ($errors->has('name'))
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $errors->first('name') }}</strong>
                    </span>
                @endif
                <small class="form-text form-text text-muted w-100">{!! __('The DNS of the domain must include a CNAME record pointing to :domain.', ['domain' => '<strong>' . parse_url(config('app.url'), PHP_URL_HOST) . '</strong>']) !!}</small>
            </div>

            <div class="form-group">
                <label for="i-index-page">{{ __('Custom index URL') }}</label>
                <input type="text" dir="ltr" name="index_page" id="i-index-page" class="form-control{{ $errors->has('index_page') ? ' is-invalid' : '' }}" value="{{ old('index_page') }}">
                @if ($errors->has('index_page'))
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $errors->first('index_page') }}</strong>
                    </span>
                @endif
                <small class="text-muted">{{ __('Add a custom index page.') }}</small>
            </div>

            <div class="form-group">
                <label for="i-not-found-page">{{ __('Custom 404 URL') }}</label>
                <input type="text" dir="ltr" name="not_found_page" id="i-not-found-page" class="form-control{{ $errors->has('not_found_page') ? ' is-invalid' : '' }}" value="{{ old('not_found_page') }}">
                @if ($errors->has('not_found_page'))
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $errors->first('not_found_page') }}</strong>
                    </span>
                @endif
                <small class="form-text text-muted">{{ __('Add a custom 404 page.') }}</small>
            </div>

            <button type="submit" name="submit" class="btn btn-primary">{{ __('Save') }}</button>
        </form>
    </div>
</div>