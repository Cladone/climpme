@section('site_title', formatTitle([__('Traffic'), __('Top Content'), config('settings.title')]))

{{-- Breadcrumbs --}}
@include('shared.breadcrumbs', ['breadcrumbs' => [
    ['url' => route('dashboard'), 'title' => __('Home')],
    ['url' => route('traffic.index', ['traffic_site_id' => $traffic_site_id ?? null, 'range' => $range ?? null]), 'title' => __('Traffic')],
    ['title' => __('Top Content')],
]])

<style>
/* --- Top Content "View all" page styles (incl. mobile scroll fix) --- */
@media (max-width: 576px) {
  .top-content-responsive { overflow-x: auto; }
  .top-content-responsive table { min-width: 520px; }
  .top-content-responsive th,
  .top-content-responsive td { white-space: nowrap; }
}
.metric-row { position: relative; }
.metric-row .metric-bar {
  position:absolute; left:0; top:0; bottom:0; width:var(--bar,0%);
  background: rgba(13,110,253,.08);
}
.metric-row .metric-content { position:relative; }
</style>

<div class="d-flex align-items-center mb-3">
  <h1 class="h4 mb-0 mr-3">{{ __('Top Content') }}</h1>
  <form method="get" class="ml-auto d-flex">
    <input type="hidden" name="traffic_site_id" value="{{ $traffic_site_id ?? '' }}">
    <select name="range" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
      @foreach(['7d'=>'7 days','30d'=>'30 days','90d'=>'90 days'] as $key=>$label)
        <option value="{{ $key }}" @if(($range ?? '')===$key) selected @endif>{{ __($label) }}</option>
      @endforeach
    </select>
    <noscript><button class="btn btn-sm btn-primary">{{ __('Apply') }}</button></noscript>
  </form>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header">
    <div class="font-weight-medium py-1">{{ __('All top pages') }}</div>
  </div>
  <div class="card-body">
    @php
      // exact same helpers used on the index Top Content section
      $titleFromUrl = function(string $url): string {
        $clean = strtok($url, '?');
        $p = parse_url($clean) ?: [];
        $host = $p['host'] ?? '';
        $path = $p['path'] ?? '/';
        if ($path === '/' || $path === '') {
          return $host ?: $clean;
        }
        $seg  = trim($path, '/');
        $parts = $seg === '' ? [$host] : explode('/', $seg); // avoid end(explode(...)) byref issue
        $last = urldecode(end($parts));
        $last = preg_replace('/[-_]+/', ' ', $last);
        return mb_convert_case($last, MB_CASE_TITLE, 'UTF-8');
      };

      $pathFromUrl = function(string $url): string {
        $clean = strtok($url, '?');
        $p = parse_url($clean) ?: [];
        $path = $p['path'] ?? '/';
        return $path === '' ? '/' : $path;
      };
    @endphp

    <div class="table-responsive top-content-responsive">
      <table class="table table-striped table-hover mb-0">
        <thead>
          <tr class="text-muted small">
            <th class="pl-3">{{ __('Title') }}</th>
            <th class="border-0 text-right" style="width: 110px;">{{ __('Pageviews') }}</th>
            <th class="border-0 text-right" style="width: 110px;">{{ __('Sessions') }}</th>
            <th class="border-0 text-right" style="width: 110px;">{{ __('Engagement') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($topPages as $row)
            @php
              // works for stdClass or arrays
              $url   = data_get($row, 'url_noq', data_get($row, 'url'));
              $title = data_get($row, 'title') ?: ($url ? $titleFromUrl($url) : __('Unknown page'));
              $path  = $url ? $pathFromUrl($url) : '';
              $pv    = (int) data_get($row, 'pv', 0);
              $ss    = (int) data_get($row, 'ss', 0);
              $eg    = (int) data_get($row, 'eg', 0);
            @endphp

            <tr>
              <td class="align-middle pl-3">
                <div class="text-truncate" style="max-width: 60vw;">
                  @if($url)
                    <a href="{{ e($url) }}" target="_blank" rel="noopener" class="font-weight-medium">
                      {{ $title }}
                    </a>
                  @else
                    <span class="font-weight-medium">{{ $title }}</span>
                  @endif
                </div>
                @if($path)
                  <div class="small text-muted d-none d-md-block text-truncate" style="max-width: 720px;">
                    {{ $path }}
                  </div>
                @endif
              </td>
              <td class="align-middle text-right">{{ number_format($pv) }}</td>
              <td class="align-middle text-right">{{ number_format($ss) }}</td>
              <td class="align-middle text-right">{{ number_format($eg) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center text-muted py-4">{{ __('No data found.') }}</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if(method_exists($topPages, 'links'))
      <div class="mt-3 d-flex justify-content-end">{{ $topPages->links() }}</div>
    @endif
  </div>
</div>
