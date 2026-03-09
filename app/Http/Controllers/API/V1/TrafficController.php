<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TrafficController extends Controller
{
    /**
     * Ingest batched traffic events from the front-end beacon.
     * Auth: Bearer <JWT> (validated by AuthIngestJWT middleware).
     * The middleware should set request attributes:
     *   - 'site_id' (int) and/or 'origin_host'/'site' (origin host)
     */
    public function ingest(Request $request)
    {
        // Attributes set by AuthIngestJWT (preferred)
        $siteId     = $request->attributes->get('site_id');
        $originHost = $request->attributes->get('origin_host') ?? $request->attributes->get('site');

        // Body
        $events = $request->input('events');
        if (!is_array($events) || empty($events)) {
            return response()->json(['error' => 'bad_payload'], 422);
        }

        // Fallback: if site_id missing, try resolving via origin_host
        if (!$siteId && $originHost) {
            $siteId = $this->siteIdFromDomain($originHost);
        }
        if (!$siteId) {
            return response()->json(['error' => 'unknown_site'], 404);
        }

        // ---- Normalize request-level headers ----
        $reqRef = (string) ($request->headers->get('referer') ?? '');
        $ua     = substr($request->userAgent() ?? '', 0, 255);

        // Prefer real client IP when behind Cloudflare
        $clientIp  = $this->realClientIp($request);
        // Country: CF header first, then optional GeoIP fallback
        $country   = $this->cfCountryOrGeo($request, $clientIp);

        if (config('app.debug')) {
            logger()->info('traffic.ingest.headers', [
                'cf_ipcountry'     => $request->header('CF-IPCountry'),
                'cf_connecting_ip' => $request->header('CF-Connecting-IP'),
                'chosen_ip'        => $clientIp,
                'final_country'    => $country,
                'host'             => $request->getHost(),
            ]);
        }

        // ---- UA bucketing closures ----
        $browser = function (string $ua): string {
            $u = strtolower($ua);
            if (str_contains($u, 'crios')) return 'Chrome iOS';
            if (str_contains($u, 'samsungbrowser')) return 'Samsung Internet';
            if (str_contains($u, 'edg/')) return 'Edge';
            if (str_contains($u, 'opr/') || str_contains($u, 'opera')) return 'Opera';
            if (str_contains($u, 'whatsapp')) return 'WhatsApp';
            if (str_contains($u, 'firefox/')) return 'Firefox';
            if (str_contains($u, 'chrome/') && !str_contains($u, 'edg/') && !str_contains($u, 'opr/')) return 'Chrome';
            if (str_contains($u, 'safari/') && !str_contains($u, 'chrome/')) return 'Safari';
            return 'Unknown';
        };

        $os = function (string $ua): string {
            $u = strtolower($ua);
            if (str_contains($u, 'windows')) return 'Windows';
            if (str_contains($u, 'iphone') || str_contains($u, 'ipad') || str_contains($u, 'ios')) return 'iOS';
            if (str_contains($u, 'android')) return 'Android';
            if (str_contains($u, 'mac os x') || str_contains($u, 'macintosh')) return 'OS X';
            if (str_contains($u, 'linux')) return 'Linux';
            return 'Unknown';
        };

        $rows = [];
        $now  = now('UTC');

        foreach ($events as $e) {
            $type = isset($e['type']) ? (string) $e['type'] : null;
            if (!in_array($type, ['page_view', 'scroll_depth', 'heartbeat'], true)) {
                continue; // ignore unknown types
            }

            // Timestamp (ms -> Carbon)
            $tsMs = isset($e['ts']) ? (int) $e['ts'] : (int) round(microtime(true) * 1000);
            try {
                $ts = Carbon::createFromTimestampMs($tsMs);
            } catch (\Throwable $ex) {
                $ts = now('UTC');
            }

            // ---- URL & Referrer handling (safe fallbacks) ----
            $url = substr((string)($e['url'] ?? ''), 0, 1024);
            if ($url === '' && $reqRef !== '') {
                // If event didn't provide a URL, fall back to the request's Referer
                $url = substr($reqRef, 0, 1024);
            }
            $referrer = substr((string)($e['referrer'] ?? $reqRef), 0, 1024);

            // ---- post_id: numeric-only; otherwise store NULL ----
            $postIdRaw = $e['post_id'] ?? null;
            $postId    = is_numeric($postIdRaw) ? (int) $postIdRaw : null;

            // ---- link_id: if you expect numeric, coerce; else pass-through/null ----
            $linkIdRaw = $e['link_id'] ?? null;
            $linkId    = is_numeric($linkIdRaw) ? (int) $linkIdRaw : null;

            $rows[] = [
                'site_id'     => (int) $siteId,
                'post_id'     => $postId,
                'link_id'     => $linkId,
                'anon_id'     => substr((string)($e['anon_id'] ?? ''), 0, 64),
                'event_type'  => $type,
                'url'         => $url,
                'referrer'    => $referrer,
                'depth'       => isset($e['depth']) ? (int) $e['depth'] : null,
                'ua'          => $ua,
                'client_ip'   => $clientIp,
                // ✅ Use CF header or GeoIP fallback (server-side only)
                'country'     => $country,
                'ua_browser'  => $browser($ua),
                'ua_os'       => $os($ua),
                'ts'          => $ts->format('Y-m-d H:i:s.u'),
                'ingested_at' => $now,
            ];
        }

        if ($rows) {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('traffic_events_raw')->insert($chunk);
            }
        }

        return response()->json(['ok' => true, 'received' => count($rows)]);
    }

    /* ------------------------ helpers ------------------------ */

    private function normalizeHost(string $host): string
    {
        $h = mb_strtolower($host);
        return strpos($h, 'www.') === 0 ? substr($h, 4) : $h;
    }

    private function siteIdFromDomain(?string $domain)
    {
        if (!$domain) return null;
        $host = $this->normalizeHost($domain);
        return DB::table('traffic_sites')->where('origin_host', $host)->value('id');
    }

    /**
     * Prefer the real client IP when behind Cloudflare.
     * Requires TrustProxies config or we can read the header directly.
     */
    private function realClientIp(Request $request): string
    {
        return $request->header('CF-Connecting-IP') ?: $request->ip();
    }

    /**
     * Normalize Cloudflare's country header to ISO-2.
     * Returns '' for unknown/special cases so UI can show "Unknown".
     */
    private function cfCountry(Request $request): string
    {
        $c = strtoupper((string) $request->header('CF-IPCountry', ''));
        if ($c === '' || $c === 'XX' || $c === 'T1') return ''; // Unknown / Tor
        if ($c === 'UK') $c = 'GB';                              // normalize
        return $c;                                              // e.g. GH, US, DE, EU
    }

    /**
     * CF country if present; otherwise (optionally) GeoIP by IP.
     * If GeoIP DB isn't available, returns ''.
     */
    private function cfCountryOrGeo(Request $request, string $clientIp): string
    {
        $c = $this->cfCountry($request);
        if ($c !== '') return $c;

        // Optional GeoIP fallback (only if DB & library exist)
        $db = config('services.geoip.mmdb_path') ?: env('GEOIP_DB_PATH', storage_path('geo/GeoLite2-Country.mmdb'));
        if (!is_file($db)) {
            return ''; // no DB present, skip
        }

        try {
            // Use FQCN to avoid requiring the import if the package isn't installed
            $reader = new \GeoIp2\Database\Reader($db);
            $rec    = $reader->country($clientIp);
            $iso    = strtoupper($rec->country->isoCode ?? '');
            if ($iso === 'UK') $iso = 'GB';
            if ($iso === 'XX' || $iso === 'T1') $iso = '';
            return $iso ?: '';
        } catch (\Throwable $e) {
            // swallow errors (missing lib, bad DB, private IPs, etc.)
            return '';
        }
    }
}
