<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Symfony\Component\Intl\Countries;

class TrafficReportController extends Controller
{
    /* ---------------- feature gates (plan) ---------------- */

    private function featureEnabledForUser($user, string $feature): bool
    {
        // Admins always allowed.
        if ($this->isAdmin($user)) return true;

        // If you already have plan feature flags, adapt here.
        // Safe fallbacks if nothing is configured yet.
        $planId = (int) ($user->plan_id ?? 0);

        // Example: config/settings.php → ['plan_features' => [1 => ['links','...'], 2 => [...], 3 => ['traffic','qr']]]
        $map = (array) (config('settings.plan_features') ?? []);
        $features = (array) ($map[$planId] ?? []);

        // Fallback toggles (optional): config('settings.features.traffic') / qr
        if (! $features && is_array(config('settings.features'))) {
            $features = array_keys(array_filter(config('settings.features')));
        }

        // If still nothing known, allow to avoid hard lockout.
        if (!$features) return true;

        return in_array($feature, $features, true);
    }

    private function isAdmin($user): bool
    {
        // Adjust if your admin role differs (e.g. role >= 1).
        return (int) ($user->role ?? 0) >= 1;
    }

    /* ---------------- space & site scoping ---------------- */

    /**
     * Space IDs available to the current user.
     * - default_space (single space)
     * - plus space_user memberships if the pivot exists (Enterprise/Teams)
     */
    private function allowedSpaceIdsFor($user): array
    {
        $ids = [];

        if (!empty($user->default_space)) {
            $ids[] = (int) $user->default_space;
        }

        if (Schema::hasTable('space_user')) {
            $more = DB::table('space_user')
                ->where('user_id', $user->id)
                ->pluck('space_id')
                ->map(fn($v) => (int) $v)
                ->all();
            $ids = array_merge($ids, $more);
        }

        // Unique, positive integers
        $ids = array_values(array_unique(array_filter($ids, fn($v) => $v > 0)));

        return $ids;
    }

    /**
     * Traffic sites the user can see.
     * Admins see all; others see only sites whose workspace_id is in their allowed space IDs.
     */
    private function trafficSitesForUser($user)
    {
        $q = DB::table('traffic_sites')->select('id', 'origin_host', 'workspace_id');

        if (!$this->isAdmin($user)) {
            $spaceIds = $this->allowedSpaceIdsFor($user);
            if (empty($spaceIds)) return collect(); // nothing visible
            $q->whereIn('workspace_id', $spaceIds);
        }

        return $q->orderBy('origin_host')->get();
    }

    /** Validate & normalize the selected site id against the visible list. */
    private function resolveSelectedSiteId(Request $request, $hosts): ?int
    {
        $requested = (int) $request->input('traffic_site_id', 0);
        if ($requested && $hosts->firstWhere('id', $requested)) {
            return $requested;
        }
        // default to the first visible host
        return $hosts->first()->id ?? null;
    }

    private function sanitizeRange(?string $range): string
    {
        return in_array($range, ['7d', '30d'], true) ? $range : '7d';
    }

    private function rangeToDates(string $range): array
    {
        $end = Carbon::now('UTC')->minute(0)->second(0);
        $start = $range === '30d' ? $end->copy()->subDays(30) : $end->copy()->subDays(7);
        return [$start, $end];
    }

    /* ---------------- small query helpers ---------------- */

    private function seriesFromHourly(int $siteId, Carbon $start, Carbon $end)
    {
        return DB::table('traffic_hourly')
            ->select(
                'hour',
                DB::raw('SUM(pageviews) as pv'),
                DB::raw('SUM(sessions) as ss'),
                DB::raw('SUM(engaged) as eg')
            )
            ->where('site_id', $siteId)
            ->whereBetween('hour', [$start, $end])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }

    private function topPagesFromHourly(int $siteId, Carbon $start, Carbon $end, int $limit = 20)
    {
        // If your hourly table includes a URL column, use it (fast path)
        if (Schema::hasColumn('traffic_hourly', 'url')) {
            return DB::table('traffic_hourly')
                ->selectRaw("
                    SUBSTRING_INDEX(url, '?', 1) AS url_noq,
                    MAX(post_id) AS post_id,
                    SUM(pageviews) AS pv,
                    SUM(sessions)  AS ss,
                    SUM(engaged)   AS eg
                ")
                ->where('site_id', $siteId)
                ->whereBetween('hour', [$start, $end])
                ->whereNotNull('url')->where('url', '!=', '')
                ->groupBy('url_noq')
                ->orderByDesc('pv')
                ->limit($limit)
                ->get();
        }

        // Fallback: compute Top Pages from RAW events
        return DB::table('traffic_events_raw')
            ->selectRaw("
                SUBSTRING_INDEX(url, '?', 1) AS url_noq,
                MAX(post_id) AS post_id,
                SUM(CASE WHEN event_type='page_view' THEN 1 ELSE 0 END) AS pv,
                COUNT(DISTINCT CASE
                    WHEN event_type='page_view'
                    THEN MD5(CONCAT_WS('|', anon_id, DATE_FORMAT(ts, '%Y-%m-%d %H')))
                END) AS ss,
                SUM(CASE WHEN event_type='scroll_depth' THEN 1 ELSE 0 END) AS eg
            ")
            ->where('site_id', $siteId)
            ->whereBetween('ts', [$start, $end])
            ->whereNotNull('url')->where('url', '!=', '')
            ->groupBy('url_noq')
            ->orderByDesc('pv')
            ->limit($limit)
            ->get();
    }

    private function topReferrers(int $siteId, Carbon $start, Carbon $end, int $limit = 8)
    {
        return DB::table('traffic_events_raw')
            ->selectRaw("
                LOWER(TRIM(LEADING 'www.' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(referrer, '/', 3), '//', -1))) AS name,
                SUM(CASE WHEN event_type='page_view' THEN 1 ELSE 0 END) AS pv,
                COUNT(DISTINCT CASE
                    WHEN event_type='page_view'
                    THEN MD5(CONCAT_WS('|', anon_id, DATE_FORMAT(ts, '%Y-%m-%d %H')))
                END) AS ss
            ")
            ->where('site_id', $siteId)
            ->whereBetween('ts', [$start, $end])
            ->whereNotNull('referrer')->where('referrer', '!=', '')
            ->groupBy('name')
            ->orderByDesc('pv')
            ->limit($limit)
            ->get();
    }

    private function topCountries(int $siteId, Carbon $start, Carbon $end, int $limit = 8)
    {
        $rows = DB::table('traffic_events_raw')
            ->selectRaw("
                UPPER(NULLIF(country, '')) AS code,
                COUNT(*) AS pv,
                COUNT(DISTINCT MD5(CONCAT_WS('|', anon_id, DATE_FORMAT(ts, '%Y-%m-%d %H')))) AS ss
            ")
            ->where('site_id', $siteId)
            ->whereBetween('ts', [$start, $end])
            ->where('event_type', 'page_view')
            ->groupBy('code')
            ->orderByDesc('pv')
            ->limit($limit)
            ->get();

        $locale = app()->getLocale() ?: 'en';
        $countryNames = class_exists(Countries::class)
            ? array_change_key_case(Countries::getNames($locale), CASE_UPPER)
            : null;

        return $rows->map(function ($r) use ($countryNames, $locale) {
            $code = strtoupper(trim((string)($r->code ?? '')));
            if ($code === 'UK') $code = 'GB';
            if ($code === '' || $code === 'XX' || $code === 'ZZ' || $code === 'T1') {
                $r->name = 'Unknown';
            } elseif ($code === 'EU') {
                $r->name = 'European Union';
            } elseif (is_array($countryNames) && isset($countryNames[$code])) {
                $r->name = $countryNames[$code];
            } elseif (class_exists(\Locale::class) && method_exists(\Locale::class, 'getDisplayRegion')) {
                $label = \Locale::getDisplayRegion('-'.$code, $locale);
                $r->name = ($label && $label !== '-'.$code) ? $label : $code;
            } else {
                $r->name = $code;
            }
            unset($r->code);
            return $r;
        });
    }

    private function topBrowsers(int $siteId, Carbon $start, Carbon $end, int $limit = 8)
    {
        return DB::table('traffic_events_raw')
            ->selectRaw("
                COALESCE(NULLIF(ua_browser, ''), 
                    CASE
                        WHEN ua LIKE '%CriOS%' THEN 'Chrome iOS'
                        WHEN ua LIKE '%SamsungBrowser%' THEN 'Samsung Internet'
                        WHEN ua LIKE '%Edg/%' THEN 'Edge'
                        WHEN ua LIKE '%OPR/%' OR ua LIKE '%Opera%' THEN 'Opera'
                        WHEN ua LIKE '%Firefox/%' THEN 'Firefox'
                        WHEN ua LIKE '%WhatsApp%' THEN 'WhatsApp'
                        WHEN ua LIKE '%Chrome/%' AND ua NOT LIKE '%Edg/%' AND ua NOT LIKE '%OPR/%' THEN 'Chrome'
                        WHEN ua LIKE '%Safari/%' AND ua NOT LIKE '%Chrome/%' THEN 'Safari'
                        ELSE 'Unknown'
                    END
                ) AS name,
                SUM(CASE WHEN event_type='page_view' THEN 1 ELSE 0 END) AS pv,
                COUNT(DISTINCT CASE
                    WHEN event_type='page_view'
                    THEN MD5(CONCAT_WS('|', anon_id, DATE_FORMAT(ts, '%Y-%m-%d %H')))
                END) AS ss
            ")
            ->where('site_id', $siteId)
            ->whereBetween('ts', [$start, $end])
            ->groupBy('name')
            ->orderByDesc('pv')
            ->limit($limit)
            ->get();
    }

    private function topOS(int $siteId, Carbon $start, Carbon $end, int $limit = 8)
    {
        return DB::table('traffic_events_raw')
            ->selectRaw("
                COALESCE(NULLIF(ua_os, ''), 
                    CASE
                        WHEN ua LIKE '%Windows%' THEN 'Windows'
                        WHEN ua LIKE '%iPhone%' OR ua LIKE '%iPad%' OR ua LIKE '%iOS%' THEN 'iOS'
                        WHEN ua LIKE '%Android%' THEN 'Android'
                        WHEN ua LIKE '%Mac OS X%' OR ua LIKE '%Macintosh%' THEN 'OS X'
                        WHEN ua LIKE '%Linux%' THEN 'Linux'
                        ELSE 'Unknown'
                    END
                ) AS name,
                SUM(CASE WHEN event_type='page_view' THEN 1 ELSE 0 END) AS pv,
                COUNT(DISTINCT CASE
                    WHEN event_type='page_view'
                    THEN MD5(CONCAT_WS('|', anon_id, DATE_FORMAT(ts, '%Y-%m-%d %H')))
                END) AS ss
            ")
            ->where('site_id', $siteId)
            ->whereBetween('ts', [$start, $end])
            ->groupBy('name')
            ->orderByDesc('pv')
            ->limit($limit)
            ->get();
    }

    /* ---------------- pages ---------------- */

    public function overview(Request $request)
    {
        $user = $request->user();

        // Plan gate: allow only if the plan enables traffic.
        if (!$this->featureEnabledForUser($user, 'traffic')) {
            abort(403);
        }

        $range = $this->sanitizeRange($request->input('range'));
        [$start, $end] = $this->rangeToDates($range);

        $hosts = $this->trafficSitesForUser($user); // <- only the user’s sites
        $trafficSiteId = $this->resolveSelectedSiteId($request, $hosts);
        $currentHost = $trafficSiteId ? $hosts->firstWhere('id', $trafficSiteId) : null;

        $series = collect();
        $topPages = collect();
        $referrers = collect();
        $countries = collect();
        $browsers = collect();
        $oss = collect();

        if ($trafficSiteId) {
            // 1) RAW everywhere
            $series    = $this->seriesFromRaw($trafficSiteId, $start, $end);
            $topPages  = $this->topPagesFromRaw($trafficSiteId, $start, $end, 20, false);
            $referrers = $this->topReferrers($trafficSiteId, $start, $end);
            $countries = $this->topCountries($trafficSiteId, $start, $end);
            $browsers  = $this->topBrowsers($trafficSiteId, $start, $end);
            $oss       = $this->topOS($trafficSiteId, $start, $end);
        }

        // 2) KPIs from RAW
        $windowSeconds = $end->diffInSeconds($start);
        $prevStart     = $start->copy()->subSeconds($windowSeconds);
        $prevEnd       = $start->copy();
        $windowDays    = max(1, (int) $end->diffInDays($start));

        $visitors = (int) \DB::table('traffic_events_raw')
            ->where('site_id', $trafficSiteId)
            ->whereBetween('ts', [$start, $end])
            ->where('event_type', 'page_view')
            ->distinct('anon_id')->count('anon_id');

        $tot = \DB::table('traffic_events_raw')
            ->selectRaw("
                SUM(CASE WHEN event_type='page_view'   THEN 1 ELSE 0 END) AS pv,
                COUNT(DISTINCT CASE
                    WHEN event_type='page_view'
                    THEN MD5(CONCAT_WS('|', anon_id, DATE_FORMAT(ts, '%Y-%m-%d %H')))
                END) AS ss,
                SUM(CASE WHEN event_type='scroll_depth' THEN 1 ELSE 0 END) AS eg
            ")
            ->where('site_id', $trafficSiteId)
            ->whereBetween('ts', [$start, $end])
            ->first();

        $prevVisitors = (int) \DB::table('traffic_events_raw')
            ->where('site_id', $trafficSiteId)
            ->whereBetween('ts', [$prevStart, $prevEnd])
            ->where('event_type', 'page_view')
            ->distinct('anon_id')->count('anon_id');

        $prevTot = \DB::table('traffic_events_raw')
            ->selectRaw("
                SUM(CASE WHEN event_type='page_view'   THEN 1 ELSE 0 END) AS pv,
                COUNT(DISTINCT CASE
                    WHEN event_type='page_view'
                    THEN MD5(CONCAT_WS('|', anon_id, DATE_FORMAT(ts, '%Y-%m-%d %H')))
                END) AS ss,
                SUM(CASE WHEN event_type='scroll_depth' THEN 1 ELSE 0 END) AS eg
            ")
            ->where('site_id', $trafficSiteId)
            ->whereBetween('ts', [$prevStart, $prevEnd])
            ->first();

        $liveNow = (int) \DB::table('traffic_events_raw')
            ->where('site_id', $trafficSiteId)
            ->where('event_type', 'page_view')
            ->whereBetween('ts', [$end->copy()->subMinutes(5), $end])
            ->distinct('anon_id')->count('anon_id');

        $pc = function ($cur, $prev) {
            if ($prev <= 0) return null;
            return round((($cur - $prev) / $prev) * 100, 1);
        };

        $kpi = [
            'windowDays' => $windowDays,
            'visitors'   => ['value' => (int) $visitors,          'delta' => $pc($visitors, (int)$prevVisitors)],
            'sessions'   => ['value' => (int) ($tot->ss ?? 0),     'delta' => $pc((int)($tot->ss ?? 0), (int)($prevTot->ss ?? 0))],
            'engagement' => ['value' => (int) ($tot->eg ?? 0),     'delta' => $pc((int)($tot->eg ?? 0), (int)($prevTot->eg ?? 0))],
            'pageviews'  => ['value' => (int) ($tot->pv ?? 0),     'delta' => $pc((int)($tot->pv ?? 0), (int)($prevTot->pv ?? 0))],
            'online'     => ['value' => $liveNow],
        ];

        return view('traffic.container', [
            'view'            => 'index',
            'hosts'           => $hosts,
            'currentHost'     => $currentHost,
            'traffic_site_id' => $trafficSiteId,
            'series'          => $series,
            'topPages'        => $topPages,
            'referrers'       => $referrers,
            'countries'       => $countries,
            'browsers'        => $browsers,
            'oss'             => $oss,
            'range'           => $range,
            'kpi'             => $kpi,
        ]);
    }

    public function health(Request $request)
    {
        $user = $request->user();
        if (!$this->featureEnabledForUser($user, 'traffic')) {
            return response()->json(['status' => 'forbidden', 'online' => 0], 403);
        }

        $hosts = $this->trafficSitesForUser($user);
        $siteId = (int) $request->query('traffic_site_id', $hosts->first()->id ?? 0);

        if (!$siteId || !$hosts->firstWhere('id', $siteId)) {
            return response()->json(['status' => 'no-site', 'online' => 0], 400);
        }

        $now = Carbon::now('UTC');
        $online = (int) DB::table('traffic_events_raw')
            ->where('site_id', $siteId)
            ->where('event_type', 'page_view')
            ->whereBetween('ts', [$now->copy()->subMinutes(5), $now])
            ->distinct('anon_id')->count('anon_id');

        return response()->json([
            'status' => 'ok',
            'online' => $online,
        ]);
    }

    public function show(Request $request, $postId = null)
    {
        $user = $request->user();
        if (!$this->featureEnabledForUser($user, 'traffic')) {
            abort(403);
        }

        $range = $this->sanitizeRange($request->input('range', '7d'));
        [$start, $end] = $this->rangeToDates($range);

        $hosts = $this->trafficSitesForUser($user);
        $trafficSiteId = $this->resolveSelectedSiteId($request, $hosts);
        $currentHost = $trafficSiteId ? $hosts->firstWhere('id', $trafficSiteId) : null;

        // Top content list (no per-post view in this minimal patch)
        if ($request->get('view') === 'top') {
            $topPages = collect();
            if ($trafficSiteId) {
                $topPages = $this->topPagesFromRaw($trafficSiteId, $start, $end, 50, true);
            }

            return view('traffic.container', [
                'view'            => 'show',
                'hosts'           => $hosts,
                'currentHost'     => $currentHost,
                'traffic_site_id' => $trafficSiteId,
                'topPages'        => $topPages,
                'range'           => $range,
            ]);
        }

        // Per-post page (chart + refs)
        if ($postId !== null) {
            $postId = (int) $postId;
            $series = $trafficSiteId ? $this->seriesFromRaw($trafficSiteId, $start, $end, $postId) : collect();

            $refs = \DB::table('traffic_events_raw')
                ->selectRaw("
                    LOWER(TRIM(LEADING 'www.' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(referrer, '/', 3), '//', -1))) AS referrer_host,
                    SUM(CASE WHEN event_type='page_view' THEN 1 ELSE 0 END) AS pv
                ")
                ->where('site_id', $trafficSiteId)
                ->where('post_id', $postId)
                ->whereBetween('ts', [$start, $end])
                ->groupBy('referrer_host')
                ->orderByDesc('pv')
                ->limit(20)
                ->get();

            return view('traffic.container', [
                'view'            => 'show',
                'hosts'           => $hosts,
                'currentHost'     => $currentHost,
                'traffic_site_id' => $trafficSiteId,
                'postId'          => $postId,
                'series'          => $series,
                'refs'            => $refs,
                'range'           => $range,
            ]);
        }

        abort(404);
    }

    /* simple list pages (respect scoping) */

    public function referrers(Request $request)
    {
        $user = $request->user();
        if (!$this->featureEnabledForUser($user, 'traffic')) abort(403);

        $range = $this->sanitizeRange($request->input('range'));
        [$start, $end] = $this->rangeToDates($range);

        $hosts = $this->trafficSitesForUser($user);
        $siteId = $this->resolveSelectedSiteId($request, $hosts);
        $currentHost = $siteId ? $hosts->firstWhere('id', $siteId) : null;

        $rows = $siteId ? $this->topReferrers($siteId, $start, $end, 200) : collect();

        return view('traffic.container', [
            'view' => 'list',
            'title' => __('Referrers'),
            'hosts' => $hosts,
            'currentHost' => $currentHost,
            'traffic_site_id' => $siteId,
            'range' => $range,
            'rows' => $rows,
            'nameKey' => 'name',
            'valueKey' => 'pv',
            'nameLabel' => __('Website'),
            'valueLabel' => __('Pageviews'),
        ]);
    }

    public function countries(Request $request)
    {
        $user = $request->user();
        if (!$this->featureEnabledForUser($user, 'traffic')) abort(403);

        $range = $this->sanitizeRange($request->input('range'));
        [$start, $end] = $this->rangeToDates($range);

        $hosts = $this->trafficSitesForUser($user);
        $siteId = $this->resolveSelectedSiteId($request, $hosts);
        $currentHost = $siteId ? $hosts->firstWhere('id', $siteId) : null;

        $rows = $siteId ? $this->topCountries($siteId, $start, $end, 200) : collect();

        return view('traffic.container', [
            'view' => 'list',
            'title' => __('Countries'),
            'hosts' => $hosts,
            'currentHost' => $currentHost,
            'traffic_site_id' => $siteId,
            'range' => $range,
            'rows' => $rows,
            'nameKey' => 'name',
            'valueKey' => 'pv',
            'nameLabel' => __('Name'),
            'valueLabel' => __('Pageviews'),
        ]);
    }

    public function browsers(Request $request)
    {
        $user = $request->user();
        if (!$this->featureEnabledForUser($user, 'traffic')) abort(403);

        $range = $this->sanitizeRange($request->input('range'));
        [$start, $end] = $this->rangeToDates($range);

        $hosts = $this->trafficSitesForUser($user);
        $siteId = $this->resolveSelectedSiteId($request, $hosts);
        $currentHost = $siteId ? $hosts->firstWhere('id', $siteId) : null;

        $rows = $siteId ? $this->topBrowsers($siteId, $start, $end, 200) : collect();

        return view('traffic.container', [
            'view' => 'list',
            'title' => __('Browsers'),
            'hosts' => $hosts,
            'currentHost' => $currentHost,
            'traffic_site_id' => $siteId,
            'range' => $range,
            'rows' => $rows,
            'nameKey' => 'name',
            'valueKey' => 'pv',
            'nameLabel' => __('Name'),
            'valueLabel' => __('Pageviews'),
        ]);
    }

    public function os(Request $request)
    {
        $user = $request->user();
        if (!$this->featureEnabledForUser($user, 'traffic')) abort(403);

        $range = $this->sanitizeRange($request->input('range'));
        [$start, $end] = $this->rangeToDates($range);

        $hosts = $this->trafficSitesForUser($user);
        $siteId = $this->resolveSelectedSiteId($request, $hosts);
        $currentHost = $siteId ? $hosts->firstWhere('id', $siteId) : null;

        $rows = $siteId ? $this->topOS($siteId, $start, $end, 200) : collect();

        return view('traffic.container', [
            'view' => 'list',
            'title' => __('Operating Systems'),
            'hosts' => $hosts,
            'currentHost' => $currentHost,
            'traffic_site_id' => $siteId,
            'range' => $range,
            'rows' => $rows,
            'nameKey' => 'name',
            'valueKey' => 'pv',
            'nameLabel' => __('Name'),
            'valueLabel' => __('Pageviews'),
        ]);
    }

    /* ---------------- RAW helpers (fast + consistent) ---------------- */

    // Hourly time-series from RAW
    private function seriesFromRaw(int $siteId, \Carbon\Carbon $start, \Carbon\Carbon $end, ?int $postId = null)
    {
        return \DB::table('traffic_events_raw')
            ->selectRaw("
                FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(ts)/3600)*3600) AS hour,
                SUM(CASE WHEN event_type='page_view' THEN 1 ELSE 0 END) AS pv,
                COUNT(DISTINCT CASE
                    WHEN event_type='page_view'
                    THEN MD5(CONCAT_WS('|', anon_id, DATE_FORMAT(ts, '%Y-%m-%d %H')))
                END) AS ss,
                SUM(CASE WHEN event_type='scroll_depth' THEN 1 ELSE 0 END) AS eg
            ")
            ->where('site_id', $siteId)
            ->when($postId !== null, fn($q) => $q->where('post_id', $postId))
            ->whereBetween('ts', [$start, $end])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }

    // Top pages from RAW (uses url_noq if you add it; otherwise trims query with SUBSTRING_INDEX)
    private function topPagesFromRaw(int $siteId, \Carbon\Carbon $start, \Carbon\Carbon $end, int $limit = 20, bool $paginate = false)
    {
        $builder = \DB::table('traffic_events_raw')
            ->selectRaw("
                /* prefer url_noq if column exists; fallback to SUBSTRING_INDEX */
                " . (Schema::hasColumn('traffic_events_raw','url_noq')
                    ? "url_noq"
                    : "SUBSTRING_INDEX(url, '?', 1)") . " AS url_noq,
                MAX(post_id) AS post_id,
                SUM(CASE WHEN event_type='page_view' THEN 1 ELSE 0 END) AS pv,
                COUNT(DISTINCT CASE
                    WHEN event_type='page_view'
                    THEN MD5(CONCAT_WS('|', anon_id, DATE_FORMAT(ts, '%Y-%m-%d %H')))
                END) AS ss,
                SUM(CASE WHEN event_type='scroll_depth' THEN 1 ELSE 0 END) AS eg
            ")
            ->where('site_id', $siteId)
            ->whereBetween('ts', [$start, $end])
            ->whereNotNull('url')->where('url', '!=', '')
            ->groupBy('url_noq')
            ->orderByDesc('pv');

        return $paginate ? $builder->paginate(50) : $builder->limit($limit)->get();
    }
}