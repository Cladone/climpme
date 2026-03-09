<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AggregateTrafficHourly extends Command
{
    protected $signature = 'traffic:aggregate-hourly {--window=120 : minutes of lookback}';
    protected $description = 'Aggregate raw traffic events into hourly rollups';

    public function handle()
    {
        $window = (int)$this->option('window');
        $end = Carbon::now()->minute(0)->second(0);
        $start = $end->copy()->subMinutes($window);

        // Load raw events in window
        $rows = DB::table('traffic_events_raw')
            ->whereBetween('ts', [$start, $end])
            ->orderBy('site_id')->orderBy('post_id')->orderBy('anon_id')->orderBy('ts')
            ->get();

        // Build rollups in memory
        $buckets = [];
        $lastEvent = []; // per site+anon

        foreach ($rows as $r) {
            $hour = Carbon::parse($r->ts)->minute(0)->second(0)->toDateTimeString();
            $key = "{$r->site_id}|".($r->post_id ?? 0)."|$hour";
            if (!isset($buckets[$key])) {
                $buckets[$key] = [
                    'site_id' => $r->site_id,
                    'post_id' => $r->post_id,
                    'hour'    => $hour,
                    'pageviews' => 0, 'sessions' => 0,
                    'depth25'=>0,'depth50'=>0,'depth75'=>0,'depth90'=>0,'engaged'=>0,
                    '_session_depths' => [], // anon_id => max depth
                    '_session_first'  => [], // anon_id => first ts
                    '_session_last'   => [], // anon_id => last ts
                ];
            }
            $b =& $buckets[$key];

            if ($r->event_type === 'page_view') {
                $b['pageviews']++;
                // session start logic (30 min gap)
                $laKey = "{$r->site_id}|{$r->anon_id}";
                $prev = $lastEvent[$laKey] ?? null;
                if (!$prev || Carbon::parse($r->ts)->diffInMinutes(Carbon::parse($prev)) > 30) {
                    $b['sessions']++;
                    $b['_session_depths'][$r->anon_id] = 0;
                    $b['_session_first'][$r->anon_id]  = $r->ts;
                }
                $lastEvent[$laKey] = $r->ts;
                $b['_session_last'][$r->anon_id] = $r->ts;
            } elseif ($r->event_type === 'scroll_depth' && $r->depth !== null) {
                $d = (int)$r->depth;
                if ($d >= 25) $b['depth25']++;
                if ($d >= 50) $b['depth50']++;
                if ($d >= 75) $b['depth75']++;
                if ($d >= 90) $b['depth90']++;
                $b['_session_depths'][$r->anon_id] = max($b['_session_depths'][$r->anon_id] ?? 0, $d);
                $b['_session_last'][$r->anon_id] = $r->ts;
            } elseif ($r->event_type === 'heartbeat') {
                $b['_session_last'][$r->anon_id] = $r->ts;
            }
        }

        // compute engaged
        foreach ($buckets as $key => $b) {
            $eng = 0;
            foreach ($b['_session_depths'] as $anon => $maxD) {
                $tFirst = isset($b['_session_first'][$anon]) ? Carbon::parse($b['_session_first'][$anon]) : null;
                $tLast  = isset($b['_session_last'][$anon]) ? Carbon::parse($b['_session_last'][$anon]) : null;
                $dur = ($tFirst && $tLast) ? $tFirst->diffInSeconds($tLast) : 0;
                if ($maxD >= 50 || $dur >= 30) $eng++;
            }
            $buckets[$key]['engaged'] = $eng;

            // drop temp fields
            unset($buckets[$key]['_session_depths'], $buckets[$key]['_session_first'], $buckets[$key]['_session_last']);
        }

        // upsert
        $totalBuckets = 0;
        foreach ($buckets as $b) {
            DB::table('traffic_hourly')->updateOrInsert(
                ['site_id'=>$b['site_id'], 'post_id'=>$b['post_id'], 'hour'=>$b['hour']],
                [
                    'pageviews'=>$b['pageviews'],
                    'sessions'=>$b['sessions'],
                    'depth25'=>$b['depth25'],
                    'depth50'=>$b['depth50'],
                    'depth75'=>$b['depth75'],
                    'depth90'=>$b['depth90'],
                    'engaged'=>$b['engaged'],
                ]
            );
            $totalBuckets++;
        }

        $this->info(sprintf(
            'Aggregated %d buckets from %s to %s',
            $totalBuckets,
            $start->toDateTimeString(),
            $end->toDateTimeString()
        ));

    }
}
