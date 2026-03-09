@section('site_title', formatTitle([__($title), __('Traffic'), config('settings.title')]))

@include('shared.breadcrumbs', ['breadcrumbs' => [
  ['url' => route('dashboard'), 'title' => __('Home')],
  ['url' => route('traffic.index'), 'title' => __('Traffic')],
  ['title' => __($title)],
]])

<div class="d-flex">
  <div class="flex-grow-1">
    <h1 class="h2 mb-0 d-inline-block">{{ __($title) }}</h1>
  </div>
  <div class="col-auto pl-0">
    <form method="get" class="d-inline-flex align-items-center">
      @if(($hosts ?? collect())->count() > 1)
        <select name="traffic_site_id" class="custom-select custom-select-sm mr-2" onchange="this.form.submit()">
          @foreach($hosts as $h)
            <option value="{{ $h->id }}" {{ (int)($traffic_site_id ?? 0) === (int)$h->id ? 'selected' : '' }}>
              {{ $h->origin_host }}
            </option>
          @endforeach
        </select>
      @else
        <input type="hidden" name="traffic_site_id" value="{{ $traffic_site_id }}">
      @endif

      <select name="range" class="custom-select custom-select-sm" onchange="this.form.submit()">
        <option value="7d"  {{ $range === '7d'  ? 'selected' : '' }}>{{ __('Last 7 days') }}</option>
        <option value="30d" {{ $range === '30d' ? 'selected' : '' }}>{{ __('Last 30 days') }}</option>
      </select>
    </form>
  </div>
</div>

<div class="mt-3">
  @include('traffic.partials.metric-list', [
    'title'      => $title,
    'rows'       => $rows,
    'nameKey'    => $nameKey ?? 'name',
    'valueKey'   => $valueKey ?? 'pv',
    'nameLabel'  => $nameLabel ?? __('Name'),
    'valueLabel' => $valueLabel ?? __('Pageviews'),
  ])
</div>

@include('shared.sidebars.user')
