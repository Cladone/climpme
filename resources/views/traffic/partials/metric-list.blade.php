@php
  // expected: $title, $rows, $nameKey, $valueKey, optional $viewUrl
  $rows  = collect($rows ?? []);
  $max   = max(1, (int) $rows->max($valueKey) ?: 1);
@endphp

<div class="card metric-card border-0 shadow-sm h-100">
  <div class="card-header">
    <div class="font-weight-medium py-1">{{ __($title) }}</div>
  </div>

  <ul class="list-group list-group-flush">
    @forelse($rows as $row)
      @php
        $name  = (string) data_get($row, $nameKey, '');
        $value = (int)    data_get($row, $valueKey, 0);
        $url   =          data_get($row, 'url');
        $pct   = $max ? round(($value / $max) * 100) : 0;
      @endphp

      <li class="list-group-item">
        <div class="metric-row" style="--pct: {{ $pct }}%">
          <div class="metric-bar"></div>

          {{-- make one horizontal row --}}
          <div class="metric-content d-flex align-items-center">
            <div class="metric-name text-truncate">
              @if(!empty($url))
                <a href="{{ e($url) }}" target="_blank" rel="noopener" class="text-reset">
                  {{ $name }}
                </a>
              @else
                {{ $name }}
              @endif
            </div>

            {{-- push to far right + right align --}}
            <div class="metric-value ml-auto text-right">
              {{ number_format($value) }}
            </div>
          </div>
        </div>
      </li>
    @empty
      <li class="list-group-item text-center text-muted py-3">
        {{ __('No data yet.') }}
      </li>
    @endforelse
  </ul>

  @isset($viewUrl)
    <a href="{{ $viewUrl }}" class="view-all">
      {{ __('View all') }} <span class="chev">›</span>
    </a>
  @endisset
</div>
