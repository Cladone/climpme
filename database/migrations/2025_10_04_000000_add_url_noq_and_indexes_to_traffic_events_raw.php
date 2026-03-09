<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add a STORED generated column (MariaDB 10.2+/MySQL 5.7+)
        // If your engine/version doesn’t support STORED, add a nullable column and backfill instead.
        DB::statement("
            ALTER TABLE traffic_events_raw
            ADD COLUMN url_noq VARCHAR(1024)
                GENERATED ALWAYS AS (SUBSTRING_INDEX(url, '?', 1)) STORED
        ");

        // Core indexes for RAW reporting
        Schema::table('traffic_events_raw', function (Blueprint $table) {
            $table->index(['site_id', 'event_type', 'ts'], 'idx_site_event_ts');
            // Index on url_noq for Top Content
            $table->index(['site_id', 'url_noq', 'ts'], 'idx_site_url_ts');
            // If you often filter by post_id per-page view:
            $table->index(['site_id', 'post_id', 'ts'], 'idx_site_post_ts');
        });
    }

    public function down(): void
    {
        Schema::table('traffic_events_raw', function (Blueprint $table) {
            $table->dropIndex('idx_site_event_ts');
            $table->dropIndex('idx_site_url_ts');
            $table->dropIndex('idx_site_post_ts');
        });

        // Drop generated column
        DB::statement("ALTER TABLE traffic_events_raw DROP COLUMN url_noq");
    }
};