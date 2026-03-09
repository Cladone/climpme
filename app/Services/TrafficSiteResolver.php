<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TrafficSiteResolver
{
    /** Lowercase + strip leading www. */
    public static function normalize(?string $host): ?string
    {
        if (!$host) return null;
        $h = mb_strtolower($host);
        return (strpos($h, 'www.') === 0) ? substr($h, 4) : $h;
    }

    /** Return site_id if exists, otherwise null */
    public static function find(int $workspaceId, ?string $host): ?int
    {
        $n = self::normalize($host);
        if (!$n) return null;
        return DB::table('traffic_sites')
            ->where('workspace_id', $workspaceId)
            ->where('origin_host', $n)
            ->value('id');
    }

    /** Find or create a site_id for (workspace_id, origin_host) */
    public static function findOrCreate(int $workspaceId, ?string $host): ?int
    {
        $n = self::normalize($host);
        if (!$n) return null;

        // Try to read first for speed
        $id = self::find($workspaceId, $n);
        if ($id) return $id;

        // Upsert to avoid races
        try {
            return DB::table('traffic_sites')->insertGetId([
                'workspace_id' => $workspaceId,
                'origin_host'  => $n,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            // Another request may have inserted it; read again
            return self::find($workspaceId, $n);
        }
    }
}
