<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ensure no NULLs remain before changing nullability
        DB::table('traffic_events_raw')->whereNull('country')->update(['country' => '']);

        Schema::table('traffic_events_raw', function (Blueprint $table) {
            $table->string('country', 2)->default('')->nullable(false)->change();
            $table->index(['site_id','ts','country'], 'ter_site_ts_country');
        });
    }

    public function down(): void
    {
        Schema::table('traffic_events_raw', function (Blueprint $table) {
            $table->dropIndex('ter_site_ts_country');
            $table->string('country', 2)->nullable()->default(null)->change();
        });
    }
};
