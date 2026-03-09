<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('traffic_events_raw', function (Blueprint $t) {
            if (!Schema::hasColumn('traffic_events_raw', 'client_ip')) {
                $t->string('client_ip', 45)->nullable()->after('ua');
            }
            if (!Schema::hasColumn('traffic_events_raw', 'country')) {
                $t->char('country', 2)->nullable()->after('client_ip');
            }
            if (!Schema::hasColumn('traffic_events_raw', 'ua_browser')) {
                $t->string('ua_browser', 32)->nullable()->after('country');
            }
            if (!Schema::hasColumn('traffic_events_raw', 'ua_os')) {
                $t->string('ua_os', 32)->nullable()->after('ua_browser');
            }

            $t->index(['site_id', 'country']);
            $t->index(['site_id', 'ua_browser']);
            $t->index(['site_id', 'ua_os']);
        });
    }

    public function down(): void {
        Schema::table('traffic_events_raw', function (Blueprint $t) {
            if (Schema::hasColumn('traffic_events_raw', 'ua_os')) $t->dropColumn('ua_os');
            if (Schema::hasColumn('traffic_events_raw', 'ua_browser')) $t->dropColumn('ua_browser');
            if (Schema::hasColumn('traffic_events_raw', 'country')) $t->dropColumn('country');
            if (Schema::hasColumn('traffic_events_raw', 'client_ip')) $t->dropColumn('client_ip');
        });
    }
};
