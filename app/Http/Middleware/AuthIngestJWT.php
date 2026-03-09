<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

class AuthIngestJWT
{
    private function normHost(?string $h): ?string
    {
        if (!$h) return null;
        $h = mb_strtolower($h);
        return str_starts_with($h, 'www.') ? substr($h, 4) : $h;
    }

    public function handle($request, Closure $next)
    {
        $auth = $request->header('Authorization', '');
        if (!preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
            return response()->json(['error' => 'missing_token'], 401);
        }
        $token = trim($m[1]);

        try {
            $payload = JWT::decode($token, new Key(config('traffic.jwt_secret'), 'HS256'));
        } catch (Throwable $e) {
            \Log::warning('ingest.jwt.decode_fail', ['err' => $e->getMessage()]);
            return response()->json(['error' => 'invalid_token', 'hint' => $e->getMessage()], 403);
        }

        $now = time();
        if (($payload->nbf ?? 0) > $now + 5) {
            return response()->json(['error' => 'token_not_yet_valid', 'nbf' => $payload->nbf, 'now' => $now], 403);
        }
        if (($payload->exp ?? 0) <= $now) {
            return response()->json(['error' => 'token_expired', 'exp' => $payload->exp, 'now' => $now], 403);
        }

        // --- issuer: compare by host only, ignore scheme/port/trailing slash
        $issHost = $this->normHost(parse_url((string)($payload->iss ?? ''), PHP_URL_HOST));
        $appHost = $this->normHost(parse_url((string)config('app.url'), PHP_URL_HOST));
        if ($issHost && $appHost && $issHost !== $appHost) {
            return response()->json(['error' => 'issuer_mismatch', 'iss_host' => $issHost, 'app_host' => $appHost], 403);
        }

        // --- scope
        $scopes = (array)($payload->scp ?? []);
        if (!in_array('traffic:write', $scopes, true)) {
            return response()->json(['error' => 'insufficient_scope', 'scopes' => $scopes], 403);
        }

        // --- optional Origin/Referer enforcement: only enforce if header present
        $hdr = $request->headers->get('Origin') ?: $request->headers->get('Referer');
        $hdrHost = $this->normHost($hdr ? parse_url($hdr, PHP_URL_HOST) : null);
        $tokenOrigin = $this->normHost($payload->origin_host ?? null);
        if ($hdrHost && $tokenOrigin && $hdrHost !== $tokenOrigin) {
            return response()->json(['error' => 'origin_mismatch', 'expected' => $tokenOrigin, 'got' => $hdrHost], 403);
        }

        // expose to controller
        $request->attributes->set('site', $payload->origin_host ?? null);
        $request->attributes->set('site_id', $payload->site_id ?? null);

        return $next($request);
    }
}
