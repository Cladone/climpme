<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class ApiGuardMiddleware
{
    public function handle($request, Closure $next)
    {
        // 1) Extract API key from common places
        $apiKey = $this->extractApiKey($request);
        if (!$apiKey) {
            return response()->json([
                'error' => 'unauthorized',
                'hint'  => 'Provide an API key via Authorization: Bearer, X-Api-Key, ?api_key=, or JSON body {api_key: "..."}'
            ], 401);
        }

        // 2) Detect which column stores the API key
        $col = $this->detectApiKeyColumn();
        if (!$col) {
            return response()->json([
                'error' => 'server_misconfigured',
                'hint'  => 'No api_token/api_key column found on users table'
            ], 500);
        }

        // 3) Resolve the user by API key
        $user = User::where($col, $apiKey)->first();
        if (!$user) {
            return response()->json([
                'error' => 'unauthorized',
                'hint'  => 'Invalid API key'
            ], 401);
        }

        // 4) (Optional) Gate/ability checks — only if you need them
        // Avoid calling ->cannot() on null; at this point $user is guaranteed.
        // if (method_exists($user, 'cannot') && $user->cannot('api-access')) {
        //     return response()->json(['error' => 'forbidden'], 403);
        // }

        // 5) Attach the user to the request + auth context
        auth()->setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function extractApiKey($request): ?string
    {
        // A) Authorization: Bearer <key>
        $auth = $request->header('Authorization');
        if ($auth && preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            return trim($m[1]);
        }

        // B) X-Api-Key: <key>
        $hdr = $request->header('X-Api-Key');
        if ($hdr) return trim($hdr);

        // C) ?api_key=<key>
        $q = $request->query('api_key');
        if ($q) return trim($q);

        // D) JSON body { api_key: "<key>" }
        $b = $request->input('api_key');
        if ($b) return trim($b);

        return null;
    }

    private function detectApiKeyColumn(): ?string
    {
        // Prefer these, in order
        $candidates = ['api_token', 'api_key'];
        $columns = Schema::getColumnListing('users');
        foreach ($candidates as $c) {
            if (in_array($c, $columns, true)) {
                return $c;
            }
        }
        return null;
    }
}
