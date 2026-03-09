@section('site_title', formatTitle([__('Storage'), __('Settings'), config('settings.title')]))

@include('shared.breadcrumbs', ['breadcrumbs' => [
    ['url' => route('admin.dashboard'), 'title' => __('Admin')],
    ['title' => __('Settings')],
]])

<h1 class="h2 mb-3 d-inline-block">{{ __('Storage') }}</h1>

<div class="card border-0 shadow-sm">
    <div class="card-header"><div class="font-weight-medium py-1">{{ __('Storage') }}</div></div>
    <div class="card-body">

        @include('shared.message')

        <form action="{{ route('admin.settings', 'storage') }}" method="post" enctype="multipart/form-data">

            @csrf

            <div class="form-group">
                <label for="i-storage-driver">{{ __('Driver') }}</label>
                <select name="storage_driver" id="i-storage-driver" class="custom-select{{ $errors->has('storage_driver') ? ' is-invalid' : '' }}">
                    @foreach(['public' => __('Local'), 's3' => 'S3'] as $key => $value)
                        <option value="{{ $key }}" @if ((old('storage_driver') !== null && old('storage_driver') == $key) || (config('settings.storage_driver') == $key && old('storage_driver') == null)) selected @endif>{{ $value }}</option>
                    @endforeach
                </select>
                @if ($errors->has('storage_driver'))
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $errors->first('storage_driver') }}</strong>
                    </span>
                @endif
            </div>

            <div class="form-group">
                <label for="i-storage-key">{{ __('Access key') }}</label>
                <input id="i-storage-key" type="text" class="form-control{{ $errors->has('storage_key') ? ' is-invalid' : '' }}" name="storage_key" value="{{ old('storage_key') ?? config('settings.storage_key') }}">
                @if ($errors->has('storage_key'))
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $errors->first('storage_key') }}</strong>
                    </span>
                @endif
            </div>

            <div class="form-group">
                <label for="i-storage-secret-key">{{ __('Secret key') }}</label>
                <input id="i-storage-secret-key" type="password" class="form-control{{ $errors->has('storage_secret') ? ' is-invalid' : '' }}" name="storage_secret" value="{{ old('storage_secret') ?? config('settings.storage_secret') }}">
                @if ($errors->has('storage_secret'))
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $errors->first('storage_secret') }}</strong>
                    </span>
                @endif
            </div>

            <div class="form-group">
                <label for="i-storage-bucket">{{ __('Bucket') }}</label>
                <input id="i-storage-bucket" type="text" class="form-control{{ $errors->has('storage_bucket') ? ' is-invalid' : '' }}" name="storage_bucket" value="{{ old('storage_bucket') ?? config('settings.storage_bucket') }}">
                @if ($errors->has('storage_bucket'))
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $errors->first('storage_bucket') }}</strong>
                    </span>
                @endif
            </div>

            <div class="form-group">
                <label for="i-storage-region">{{ __('Region') }}</label>
                <input id="i-storage-region" type="text" class="form-control{{ $errors->has('storage_region') ? ' is-invalid' : '' }}" name="storage_region" value="{{ old('storage_region') ?? config('settings.storage_region') }}">
                @if ($errors->has('storage_region'))
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $errors->first('storage_region') }}</strong>
                    </span>
                @endif
            </div>

            <div class="form-group">
                <label for="i-storage-endpoint">{{ __('Endpoint') }}</label>
                <input id="i-storage-endpoint" type="text" class="form-control{{ $errors->has('storage_endpoint') ? ' is-invalid' : '' }}" name="storage_endpoint" value="{{ old('storage_endpoint') ?? config('settings.storage_endpoint') }}">
                @if ($errors->has('storage_endpoint'))
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $errors->first('storage_endpoint') }}</strong>
                    </span>
                @endif
            </div>

            <button type="submit" name="submit" class="btn btn-primary">{{ __('Save') }}</button>
        </form>
    </div>
</div>
