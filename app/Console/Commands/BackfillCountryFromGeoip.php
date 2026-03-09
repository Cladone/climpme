<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use GeoIp2\Database\Reader;

class BackfillCountryFromGeoip extends Command
{
    protected $signature = 'traffic:backfill-country
        {--db= : Absolute path to GeoLite2-Country.mmdb}
        {--start= : Start datetime (UTC) e.g. 2025-09-01 00:00:00}
        {--end= : End datetime (UTC) e.g. 2025-10-04 23:59:59}
        {--limit=0 : Max rows to update (0 = no limit)}
        {--chunk=1000 : Chunk size for scanning}';

    protected $description = 'Fill empty traffic_events_raw.country from GeoLite2 by client_ip';

    public function handle(): int
    {
        $dbPath = $this->option('db') ?: storage_path('app/GeoLite2-Country.mmdb');
        if (!is_file($dbPath)) {
            $this->error("GeoIP DB not found at: {$dbPath}");
            $this->line('Use --db=/absolute/path/to/GeoLite2-Country.mmdb or place it in storage/app.');
            return self::FAILURE;
        }

        try {
            $reader = new Reader($dbPath);
        } catch (\Throwable $e) {
            $this->error('Failed to open GeoIP DB: ' . $e->getMessage());
            return self::FAILURE;
        }

        $chunk  = (int) $this->option('chunk') ?: 1000;
        $limit  = (int) $this->option('limit') ?: 0;
        $start  = $this->option('start');
        $end    = $this->option('end');

        $scan = DB::table('traffic_events_raw')
            ->select('id', 'client_ip')
            ->where(function ($q) {
                $q->whereNull('country')->orWhere('country', '');
            })
            ->whereNotNull('client_ip');

        if ($start) $scan->where('ts', '>=', $start);
        if ($end)   $scan->where('ts', '<=', $end);

        $total = (clone $scan)->count();
        if ($total === 0) {
            $this->info('Nothing to backfill. All rows have a country.');
            return self::SUCCESS;
        }

        $this->info("Scanning {$total} rows…");
        $updated = 0;
        $seen    = 0;

        $this->output->progressStart($total);

        // Process by ID for memory safety
        $scan->orderBy('id')->chunkById($chunk, function ($rows) use (&$updated, &$seen, $limit, $reader) {
            foreach ($rows as $row) {
                $seen++;

                $ip = $row->client_ip;

                // Skip invalid/private/reserved IPs
                if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    $this->output->progressAdvance();
                    continue;
                }

                $iso = '';
                try {
                    $resp = $reader->country($ip);
                    $iso  = strtoupper((string) ($resp->country->isoCode ?? ''));
                } catch (\GeoIp2\Exception\AddressNotFoundException $e) {
                    $iso = '';
                } catch (\Throwable $e) {
                    $iso = '';
                }

                if ($iso !== '') {
                    DB::table('traffic_events_raw')->where('id', $row->id)->update(['country' => $iso]);
                    $updated++;
                }

                $this->output->progressAdvance();

                if ($limit > 0 && $updated >= $limit) {
                    // Stop early if user asked for a cap
                    return false; // break chunkById
                }
            }
        }, 'id');

        $this->output->progressFinish();
        $this->info("Done. Updated {$updated} / {$total} rows.");

        return self::SUCCESS;
    }
}
