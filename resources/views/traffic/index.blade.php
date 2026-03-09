@section('site_title', formatTitle([__('Traffic'), config('settings.title')]))

{{-- Breadcrumbs (match Links page) --}}
@include('shared.breadcrumbs', ['breadcrumbs' => [
    ['url' => route('dashboard'), 'title' => __('Home')],
    ['title' => __('Traffic')],
]])

<style>
  /* Match Links card paddings */
  .metric-card .card-header { padding: .75rem 1rem; }
  .metric-card .list-group-item { padding: .75rem 1rem; }

  /* Light weight bar like Links percentage fill */
  .metric-row { position: relative; }
  .metric-row .metric-bar {
    position: absolute; left: 0; top: 0; bottom: 0;
    width: var(--pct, 0%); background: rgba(16,185,129,.08);
    border-radius: .25rem; pointer-events: none;
  }
  .metric-row .metric-content { position: relative; display: flex; align-items: center; }
  .metric-row .metric-name { flex: 1 1 auto; min-width: 0; }
  .metric-row .metric-value { margin-left: .75rem; white-space: nowrap; }

  /* ===== Mobile horizontal scroll (scoped to Top Content table) ===== */
  .x-scroll{
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    direction: ltr;
  }
  .x-scroll > table{
    min-width: 760px;
    white-space: nowrap;
  }
  .x-scroll th, .x-scroll td{ padding-right: .5rem; }
  .x-scroll .text-truncate{ max-width: 60vw; }

  /* Optional: sticky first/last cols */
  .x-scroll.sticky-cols thead th:first-child,
  .x-scroll.sticky-cols tbody td:first-child{
    position: sticky; left: 0; z-index: 2; background: #fff;
  }
  .x-scroll.sticky-cols thead th:last-child,
  .x-scroll.sticky-cols tbody td:last-child{
    position: sticky; right: 0; z-index: 2; background: #fff;
  }

  /* KPI tiles */
.kpi-card .kpi-label { font-weight: 600; letter-spacing: .2px; }
.kpi-card .kpi-value { font-size: 2.75rem; line-height: 1; font-weight: 700; }
.kpi-card .kpi-delta { margin-top: .35rem; font-weight: 600; }
.live-dot {
  width: .6rem; height: .6rem; border-radius: 50%;
  background: #16a34a; box-shadow: 0 0 0 0 rgba(22,163,74,.6);
  animation: pulse 1.4s infinite;
}
@keyframes pulse {
  0% { box-shadow: 0 0 0 0 rgba(22,163,74,.6); }
  70%{ box-shadow: 0 0 0 10px rgba(22,163,74,0); }
  100%{ box-shadow: 0 0 0 0 rgba(22,163,74,0); }
}


  @media (max-width: 767.98px){
    .x-scroll::-webkit-scrollbar{ height: 8px; }
    .x-scroll::-webkit-scrollbar-thumb{ background-color: rgba(0,0,0,.2); border-radius: 4px; }
    .x-scroll::-webkit-scrollbar-track{ background: transparent; }
  }
  @media (min-width: 768px){
    .x-scroll > table{ min-width: 100%; }
    .x-scroll .text-truncate{ max-width: 720px; }
  }

  /* --- Top Content mobile scroll fix --- */
  @media (max-width: 576px) {
    .top-content-responsive { overflow-x: auto; }
    .top-content-responsive table { min-width: 520px; }
    .top-content-responsive th,
    .top-content-responsive td { white-space: nowrap; }
  }

  /* ---- Unified Metric card + Top Content View all footer strip ---- */
  .metric-card {
    border-radius: .5rem;
    overflow: hidden;
  }
  .metric-card .card-header { border-bottom: 1px solid #f1f3f5; }

  .metric-card .view-all {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: .85rem 1rem;
    text-decoration: none !important;
    background: #fafafa;
    color: #6b7280;
    border-top: 1px solid #eceff1;
    font-weight: 500;
  }
  .metric-card .view-all:hover {
    background: #f3f4f6;
    color: #111827;
  }
  .metric-card .view-all .chev {
    margin-left: .35rem;
    transition: transform .15s ease;
  }
  .metric-card .view-all:hover .chev {
    transform: translateX(2px);
  }

  /* Adjust progress bar color inside metric-row (green shade) */
  .metric-row .metric-bar {
    background: #16a34a1a; /* light green fill */
  }
</style>


{{-- Page header (match Links page header row) --}}
<div class="d-flex">
    <div class="flex-grow-1">
        <h1 class="h2 mb-0 d-inline-block">{{ __('Traffic') }}</h1>
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
                <option value="7d" {{ $range === '7d' ? 'selected' : '' }}>{{ __('Last 7 days') }}</option>
                <option value="30d" {{ $range === '30d' ? 'selected' : '' }}>{{ __('Last 30 days') }}</option>
            </select>
        </form>
    </div>
</div>

@php
    $notConnected = ($hosts ?? collect())->isEmpty();
@endphp

@if($notConnected)
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
            <h5 class="mb-2">{{ __('Connect your site to start seeing traffic') }}</h5>
            <p class="text-muted mb-3">
                {{ __('We haven’t received any website events yet. Install the ClimpMe WordPress plugin and paste your account API key. The key stays on your server — the browser only gets a short-lived token.') }}
            </p>

            <ol class="small mb-3">
                <li>{{ __('Install the “ClimpMe Analytics” plugin on your WordPress site.') }}</li>
                <li>
                    {!! __('Open :link and copy your API key.', [
                        'link' => '<a href="'.route('account.api').'" class="font-weight-medium" target="_self">'.__('Account → API').'</a>'
                    ]) !!}
                </li>
                <li>{{ __('In WordPress, go to Settings → ClimpMe, paste the API key, and save.') }}</li>
                <li>{{ __('Visit any page on your site to generate the first event, then return here.') }}</li>
            </ol>

            <div class="d-flex flex-wrap">
                <a href="{{ route('account.api') }}" class="btn btn-primary btn-sm mr-2 mb-2">{{ __('View my API key') }}</a>
                <a href="{{ route('developers.account') }}" class="btn btn-outline-primary btn-sm mr-2 mb-2">{{ __('Developer guide') }}</a>
                <a href="{{ route('traffic.index', ['traffic_site_id' => $traffic_site_id, 'range' => $range]) }}"
                   class="btn btn-light btn-sm mb-2">{{ __('Refresh') }}</a>
            </div>

            <div class="mt-3 small text-muted">
                {{ __('Note: Sites that use a custom short domain for links (e.g., examp.le) should still send analytics with the original website domain (e.g., example.com). The short domain is only used for generating share links.') }}
            </div>
        </div>
    </div>
    @else
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header">
            <div class="font-weight-medium py-1">{{ __('Site Overview') }}</div>
        </div>
        <div class="card-body">
            <canvas id="trafficChart" style="height:260px"></canvas>
        </div>
    </div>

    {{-- ===== KPI strip (AFTER Site Overview; Live now first) ===== --}}
@php
  // window label
  $wdays       = $kpi['windowDays'] ?? 7;
  $periodLabel = __('compared to the previous :n days', ['n' => $wdays]);

  // derive pageviews if controller didn't pass $kpi['pageviews']
  $pvValue = data_get($kpi, 'pageviews.value');
  if ($pvValue === null) {
      $pvValue = collect($series ?? [])->sum(function ($r) {
          return (int) data_get($r, 'pv', 0);
      });
  }
  $pvDelta = data_get($kpi, 'pageviews.delta');

  // small helper to build a tile descriptor
  $mk = function ($label, $val, $delta) use ($periodLabel) {
      $arrow = $delta === null ? '' : ($delta >= 0 ? '↑' : '↓');
      $cls   = $delta === null ? 'text-muted' : ($delta >= 0 ? 'text-success' : 'text-danger');
      $pct   = $delta === null ? '' : (abs($delta).'%');
      return [
          'label'       => $label,
          'val'         => (int) $val,
          'delta'       => $delta,
          'arrow'       => $arrow,
          'cls'         => $cls,
          'pct'         => $pct,
          'periodLabel' => $periodLabel,
      ];
  };

  // tiles (rename Sessions -> Total Pageviews)
  $tiles = [
      $mk(__('All Visitors'),        data_get($kpi, 'visitors.value', 0),    data_get($kpi, 'visitors.delta')),
      $mk(__('Total Pageviews'),     $pvValue,                                $pvDelta),
      $mk(__('All Engagements'),     data_get($kpi, 'engagement.value', 0),  data_get($kpi, 'engagement.delta')),
  ];
@endphp

<div class="row mt-3">
  {{-- Live now FIRST --}}
  <div class="col-sm-6 col-lg-3 mb-3">
    <div class="card kpi-card border-0 shadow-sm h-100">
      <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-center mb-2">
          <span class="live-dot mr-2"></span>
          <div class="kpi-label text-muted mb-0">{{ __('Live now') }}</div>
        </div>
        <div class="kpi-value" id="kpi-online">{{ number_format(data_get($kpi, 'online.value', 0)) }}</div>
        <div class="small text-muted mt-auto">{{ __('active in the last 5 minutes') }}</div>
      </div>
    </div>
  </div>

  {{-- Other KPIs --}}
  @foreach($tiles as $t)
    <div class="col-sm-6 col-lg-3 mb-3">
      <div class="card kpi-card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="kpi-label text-muted mb-2">{{ $t['label'] }}</div>
          <div class="kpi-value">{{ number_format($t['val']) }}</div>
          <div class="kpi-delta {{ $t['cls'] }}">
            @if($t['delta'] !== null)
              {{ $t['arrow'] }} {{ $t['pct'] }}
              <span class="text-muted"> {{ $t['periodLabel'] }}</span>
            @else
              <span class="text-muted">{{ __('No previous data') }}</span>
            @endif
          </div>
        </div>
      </div>
    </div>
  @endforeach
</div>


    <div class="card border-0 shadow-sm metric-card mt-3">
        <div class="card-header">
            <div class="font-weight-medium py-1">{{ __('Top Content') }}</div>
        </div>

        <div class="card-body p-0">
            @php
                $titleFromUrl = function(string $url): string {
                    $clean = strtok($url, '?');
                    $p = parse_url($clean) ?: [];
                    $host = $p['host'] ?? '';
                    $path = $p['path'] ?? '/';
                    if ($path === '/' || $path === '') {
                        return $host ?: $clean;
                    }
                    $seg = trim($path, '/');
                    $last = $seg === '' ? $host : explode('/', $seg);
                    $last = is_array($last) ? end($last) : $last;
                    $last = urldecode($last);
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
                    @php $__rows = is_iterable($topPages) ? collect($topPages)->take(10) : collect([]); @endphp
                    @forelse($__rows as $row)
                        @php
                            $title = $titleFromUrl($row->url_noq);
                            $path  = $pathFromUrl($row->url_noq);
                        @endphp
                        <tr>
                            <td class="align-middle pl-3">
                                <div class="text-truncate" style="max-width: 60vw;">
                                    <a href="{{ e($row->url_noq) }}" target="_blank" rel="noopener" class="font-weight-medium">
                                        {{ $title }}
                                    </a>
                                </div>
                                <div class="small text-muted d-none d-md-block text-truncate" style="max-width: 720px;">
                                    {{ $path }}
                                </div>
                            </td>
                            <td class="align-middle text-right">{{ number_format($row->pv ?? 0) }}</td>
                            <td class="align-middle text-right">{{ number_format($row->ss ?? 0) }}</td>
                            <td class="align-middle text-right">{{ number_format($row->eg ?? 0) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">{{ __('No data yet.') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- View all footer strip --}}
        <a href="{{ route('traffic.show', [
        'view' => 'top', 
        'traffic_site_id' => $traffic_site_id, 
        'range' => $range
    ]) }}" 
   class="view-all">
    {{ __('View all') }} <span class="chev">›</span>
</a>
    </div>

    <div class="row mt-3">
        <div class="col-md-6 mb-3">
            @include('traffic.partials.metric-list', [
                'title' => 'Referrers',
                'rows' => $referrers,
                'nameKey' => 'name',
                'valueKey' => 'pv',
                'viewUrl' => route('traffic.referrers', ['traffic_site_id' => $traffic_site_id, 'range' => $range]),
            ])
        </div>

        <div class="col-md-6 mb-3">
            @include('traffic.partials.metric-list', [
                'title' => 'Countries',
                'rows' => $countries,
                'nameKey' => 'name',
                'valueKey' => 'pv',
                'viewUrl' => route('traffic.countries', ['traffic_site_id' => $traffic_site_id, 'range' => $range]),
            ])
        </div>

        <div class="col-md-6 mb-3">
            @include('traffic.partials.metric-list', [
                'title' => 'Browsers',
                'rows' => $browsers,
                'nameKey' => 'name',
                'valueKey' => 'pv',
                'viewUrl' => route('traffic.browsers', ['traffic_site_id' => $traffic_site_id, 'range' => $range]),
            ])
        </div>

        <div class="col-md-6 mb-3">
            @include('traffic.partials.metric-list', [
                'title' => 'Operating systems',
                'rows' => $oss,
                'nameKey' => 'name',
                'valueKey' => 'pv',
                'viewUrl' => route('traffic.os', ['traffic_site_id' => $traffic_site_id, 'range' => $range]),
            ])
        </div>
    </div>
@endif

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  window.addEventListener('DOMContentLoaded', function () {
    // PHP passes $series as [{hour: "YYYY-MM-DD HH:00:00", pv, ss, eg}, ...]
    const series = @json($series ?? []);
    const compare = @json($prevSeries ?? []); // optional: same shape as series

    if (!Array.isArray(series) || series.length === 0) {
      const holder = document.getElementById('trafficChart')?.parentElement;
      if (holder) {
        const note = document.createElement('div');
        note.className = 'text-muted small';
        note.style.padding = '0.25rem 0';
        note.innerText = '{{ __("No data yet.") }}';
        holder.appendChild(note);
      }
      return;
    }

    // --- helpers ------------------------------------------------------------
    const toUTCDate = (s) => {               // "2025-10-03 18:00:00" -> Date(…Z)
      // Treat server string as UTC to avoid TZ shifts on client
      return new Date(s.replace(' ', 'T') + 'Z');
    };
    const ymd = (d) => d.toISOString().slice(0,10);  // YYYY-MM-DD

    // Decide granularity:
    // if more than 1 unique day or small screens -> aggregate by day
    const uniqueDays = new Set(series.map(r => r.hour.slice(0,10))).size;
    const isPhone    = window.matchMedia('(max-width: 576px)').matches;
    const useDays    = uniqueDays > 1 || isPhone;

    function aggregateByDay(rows) {
      const map = new Map(); // key: YYYY-MM-DD -> {pv, ss, eg}
      rows.forEach(r => {
        const key = r.hour.slice(0,10);
        const acc = map.get(key) || { pv:0, ss:0, eg:0 };
        acc.pv += Number(r.pv)||0;
        acc.ss += Number(r.ss)||0;
        acc.eg += Number(r.eg)||0;
        map.set(key, acc);
      });
      // sorted by day
      const keys = Array.from(map.keys()).sort();
      return keys.map(k => ({ day:k, ...map.get(k) }));
    }

    function buildChartData(rows) {
      if (useDays) {
        const daily = aggregateByDay(rows);
        return {
          labels: daily.map(r => {
            const d = toUTCDate(r.day + ' 00:00:00');
            // Short, mobile-friendly: "Oct 3" (or "Fri" if you prefer)
            return d.toLocaleDateString(undefined, { month:'short', day:'numeric' });
          }),
          pv: daily.map(r => r.pv),
          ss: daily.map(r => r.ss),
          eg: daily.map(r => r.eg),
        };
      } else {
        return {
          labels: rows.map(r => {
            const d = toUTCDate(r.hour);
            // "18:00" only — compact for mobile
            return d.toLocaleTimeString([], { hour: '2-digit', minute:'2-digit' });
          }),
          pv: rows.map(r => Number(r.pv)||0),
          ss: rows.map(r => Number(r.ss)||0),
          eg: rows.map(r => Number(r.eg)||0),
        };
      }
    }

    const cur = buildChartData(series);
    const prev = (Array.isArray(compare) && compare.length) ? buildChartData(compare) : null;

    // Headroom: take the max across visible datasets and add ~15%
    const maxY = Math.max(
      (cur.pv.length ? Math.max(...cur.pv) : 0),
      (cur.ss.length ? Math.max(...cur.ss) : 0),
      (cur.eg.length ? Math.max(...cur.eg) : 0),
      prev ? (prev.pv.length ? Math.max(...prev.pv) : 0) : 0
    );
    const suggestedMax = maxY > 0 ? Math.ceil(maxY * 1.15) : undefined;

    const ctx = document.getElementById('trafficChart');
    if (!ctx) return;

    // --- Chart.js line style similar to your example ------------------------
    const datasets = [
      {
        label: '{{ __("Pageviews") }}',
        data: cur.pv,
        borderWidth: 2,
        tension: 0.35,
        pointRadius: 0,
        fill: false,
      },
      {
        label: '{{ __("Sessions") }}',
        data: cur.ss,
        borderWidth: 2,
        tension: 0.35,
        pointRadius: 0,
        fill: false,
      },
      {
        label: '{{ __("Engagement") }}',
        data: cur.eg,
        borderWidth: 2,
        tension: 0.35,
        pointRadius: 0,
        fill: false,
      }
    ];

    // Optional dashed comparison (previous period)
    if (prev) {
      datasets.push({
        label: '{{ __("Pageviews (prev)") }}',
        data: prev.pv,
        borderWidth: 2,
        tension: 0.35,
        pointRadius: 0,
        fill: false,
        borderDash: [6,6],
      });
    }

    new Chart(ctx.getContext('2d'), {
      type: 'line',
      data: {
        labels: cur.labels,
        datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        layout: { padding: { top: 8, right: 8, bottom: 4, left: 4 } },
        plugins: {
          legend: {
            display: true,
            labels: { boxWidth: 12, usePointStyle: false }
          },
          tooltip: {
            mode: 'index',
            intersect: false
          }
        },
        scales: {
          x: {
            grid: { display: true, drawBorder: false },
            ticks: {
              maxRotation: useDays ? 0 : 0,
              autoSkip: true,
              autoSkipPadding: isPhone ? 12 : 4
            }
          },
          y: {
            beginAtZero: true,
            suggestedMax,                 // <-- gives the headroom
            grid: { drawBorder: false },
            ticks: { precision: 0 }
          }
        },
        elements: {
          line: { borderJoinStyle: 'round' }
        }
      }
    });
  });
</script>

<script>
(function () {
  const el = document.getElementById('kpi-online');
  if (!el) return;

  async function refreshOnline() {
    try {
      const url = @json(route('traffic.health', ['traffic_site_id' => $traffic_site_id]));
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) return;
      const json = await res.json();
      if (typeof json?.online === 'number') {
        el.textContent = json.online.toLocaleString();
      }
    } catch (_) {}
  }

  // run now, then every 30s
  refreshOnline();
  setInterval(refreshOnline, 30000);
})();
</script>