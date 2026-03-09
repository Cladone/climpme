<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TrafficSiteResolver;
use Firebase\JWT\JWT;

class IngestTokenController extends Controller
{
    public function issue(Request $request)
    {
        $user = $request->user(); // set by api.guard
        if (!$user) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $workspaceId = (int) ($user->workspace_id ?? $user->id);
        $origin = TrafficSiteResolver::normalize($request->input('origin_host'));
        $siteId = $origin ? TrafficSiteResolver::findOrCreate($workspaceId, $origin) : null;

        $ttl = (int) config('traffic.token_ttl', 900);
        $now = time();

        $payload = [
            'iss' => config('app.url'),
            'sub' => 'traffic-ingest',
            'iat' => $now,
            'nbf' => $now - 5,
            'exp' => $now + $ttl,
            'scp' => ['traffic:write'],
            'workspace_id' => $workspaceId,
            'origin_host'  => $origin,
            'site_id'      => $siteId,
        ];

        $secret = (string) config('traffic.jwt_secret', '');
        if ($secret === '') {
            return response()->json(['error' => 'server_misconfigured', 'hint' => 'Missing TRAFFIC_JWT_SECRET'], 500);
        }

        $token = JWT::encode($payload, $secret, 'HS256');

        return response()->json([
            'token'        => $token,
            'expires_in'   => $ttl,
            'workspace_id' => $workspaceId,
            'site_id'      => $siteId,
        ]);
    }
}
