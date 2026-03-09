<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('traffic_events_raw', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('site_id');
            $table->unsignedBigInteger('post_id')->nullable();
            $table->unsignedBigInteger('link_id')->nullable();
            $table->string('anon_id', 64);
            $table->enum('event_type', ['page_view','scroll_depth','heartbeat']);
            $table->string('url', 1024);
            $table->string('referrer', 1024)->nullable();
            $table->tinyInteger('depth')->unsigned()->nullable();
            $table->string('ua', 255)->nullable();
            $table->dateTime('ts', 6);
            $table->dateTime('ingested_at', 6)->useCurrent();
            $table->index(['site_id', 'ts']);
            $table->index(['site_id', 'post_id', 'ts']);
            $table->index(['site_id', 'link_id', 'ts']);
        });

        Schema::create('traffic_hourly', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id');
            $table->unsignedBigInteger('post_id')->nullable();
            $table->dateTime('hour'); // truncated to hour UTC
            $table->unsignedInteger('pageviews')->default(0);
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedInteger('depth25')->default(0);
            $table->unsignedInteger('depth50')->default(0);
            $table->unsignedInteger('depth75')->default(0);
            $table->unsignedInteger('depth90')->default(0);
            $table->unsignedInteger('engaged')->default(0);
            $table->primary(['site_id','post_id','hour']);
        });

        Schema::create('traffic_referrers_hourly', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id');
            $table->unsignedBigInteger('post_id')->nullable();
            $table->dateTime('hour');
            $table->string('referrer_host', 255);
            $table->unsignedInteger('pageviews')->default(0);
            $table->primary(['site_id','post_id','hour','referrer_host']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_referrers_hourly');
        Schema::dropIfExists('traffic_hourly');
        Schema::dropIfExists('traffic_events_raw');
    }
};
