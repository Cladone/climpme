<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure required columns exist on traffic_sites
        Schema::table('traffic_sites', function (Blueprint $table) {
            if (!Schema::hasColumn('traffic_sites', 'origin_host')) {
                $table->string('origin_host')->index();
            }
            if (!Schema::hasColumn('traffic_sites', 'workspace_id')) {
                $table->unsignedBigInteger('workspace_id')->index();
            }
        });

        // Add composite unique (workspace_id, origin_host) if missing
        $u = DB::selectOne("
            SELECT COUNT(1) AS c
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name   = 'traffic_sites'
              AND index_name   = 'traffic_sites_ws_host_unique'
        ");
        if (!$u || (int)$u->c === 0) {
            Schema::table('traffic_sites', function (Blueprint $table) {
                $table->unique(['workspace_id', 'origin_host'], 'traffic_sites_ws_host_unique');
            });
        }

        // Add index on traffic_events_raw(site_id, ts) if missing
        $idx = DB::selectOne("
            SELECT COUNT(1) AS c
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name   = 'traffic_events_raw'
              AND index_name   = 'traffic_events_raw_site_id_ts_index'
        ");
        if (!$idx || (int)$idx->c === 0) {
            Schema::table('traffic_events_raw', function (Blueprint $table) {
                $table->index(['site_id', 'ts'], 'traffic_events_raw_site_id_ts_index');
            });
        }
    }

    public function down(): void
    {
        // Drop unique if it exists
        $u = DB::selectOne("
            SELECT COUNT(1) AS c
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name   = 'traffic_sites'
              AND index_name   = 'traffic_sites_ws_host_unique'
        ");
        if ($u && (int)$u->c > 0) {
            Schema::table('traffic_sites', function (Blueprint $table) {
                $table->dropUnique('traffic_sites_ws_host_unique');
            });
        }

        // Drop index if it exists
        $idx = DB::selectOne("
            SELECT COUNT(1) AS c
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name   = 'traffic_events_raw'
              AND index_name   = 'traffic_events_raw_site_id_ts_index'
        ");
        if ($idx && (int)$idx->c > 0) {
            Schema::table('traffic_events_raw', function (Blueprint $table) {
                $table->dropIndex('traffic_events_raw_site_id_ts_index');
            });
        }
    }
};
