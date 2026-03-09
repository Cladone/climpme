@section('site_title', formatTitle([__('New'), __('QR Code'), config('settings.title')]))

@include('shared.breadcrumbs', ['breadcrumbs' => [
    ['url' => request()->is('admin/*') ? route('admin.dashboard') : route('dashboard'),
     'title' => request()->is('admin/*') ? __('Admin') : __('Home')],
    ['url' => route('qr.index'), 'title' => __('QR Codes')],
    ['title' => __('New')],
]])

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white d-flex align-items-center">
    <h5 class="mb-0">{{ __('Create QR') }}</h5>
  </div>
  <div class="card-body">
    <form action="{{ route('qr.store') }}" method="POST">
      @csrf

      <div class="form-group">
        <label for="i-name">{{ __('QR Code Name') }}</label>
        <input type="text" id="i-name" name="name" class="form-control" value="{{ old('name') }}" required>
        <small class="text-muted">{{ __('Enter a name to identify this QR code.') }}</small>
        @error('name') <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span> @enderror
      </div>

      <div class="form-group">
        <label for="i-type">{{ __('QR Type') }}</label>
        <select id="i-type" name="type" class="custom-select" required>
            <option value="link" @selected(old('type', 'link') == 'link')">Link</option>
        </select>
        <small class="text-muted">{{ __('Choose the type of QR code.') }}</small>
        @error('type') <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span> @enderror
      </div>

      <input type="hidden" name="mode" value="dynamic">

      <button class="btn btn-primary">{{ __('Continue to Builder') }}</button>
    </form>
  </div>
</div>