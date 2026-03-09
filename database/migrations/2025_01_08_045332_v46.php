<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('settings')->insert([
            ['name' => 'webhook_domain_created', 'value' => null],
            ['name' => 'webhook_domain_deleted', 'value' => null],
            ['name' => 'auth_remember_me_duration', 'value' => 129600],
            ['name' => 'image_driver', 'value' => 'gd'],
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('authed_at')->nullable()->index('authed_at')->after('tfa_code_created_at');
        });

        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn('target_type');
            $table->dropColumn('country_target');
            $table->dropColumn('platform_target');
            $table->dropColumn('language_target');
            $table->dropColumn('rotation_target');
            $table->dropColumn('ends_at');
            $table->dropColumn('disabled');
            $table->string('targets_type', 32)->after('image')->nullable();
            $table->text('targets')->after('targets_type')->nullable();
            $table->renameColumn('password', 'redirect_password');
            $table->renameColumn('privacy_password', 'password');
            $table->renameColumn('expiration_clicks', 'clicks_limit');
            $table->boolean('sensitive_content')->after('password')->nullable();
            $table->timestamp('active_period_start_at')->after('password')->nullable()->index('active_period_start_at');
            $table->timestamp('active_period_end_at')->after('password')->nullable()->index('active_period_end_at');
        });

        DB::update("UPDATE `links` SET `clicks` = 0");

        Schema::drop('stats');

        Schema::create('stats', function (Blueprint $table) {
            $table->integer('link_id')->index('link_id')->index('link_id');
            $table->string('browser', 64)->nullable();
            $table->string('operating_system', 64)->nullable();
            $table->string('device', 64)->nullable();
            $table->string('country', 64)->nullable();
            $table->string('city', 128)->nullable();
            $table->string('referrer', 255)->nullable();
            $table->char('language', 2)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['link_id', 'created_at']);
            $table->index(['link_id', 'created_at', 'referrer']);
            $table->index(['link_id', 'created_at', 'browser']);
            $table->index(['link_id', 'created_at', 'operating_system']);
            $table->index(['link_id', 'created_at', 'device']);
            $table->index(['link_id', 'created_at', 'country']);
            $table->index(['link_id', 'created_at', 'city']);
            $table->index(['link_id', 'created_at', 'language']);
        });

        foreach (DB::table('plans')->select('*')->cursor() as $row) {
            $features = json_decode($row->features, true);

            unset($features['link_disabling']);

            DB::statement("UPDATE `plans` SET `features` = :features WHERE `id` = :id", ['features' => json_encode($features), 'id' => $row->id]);
        }

        DB::table('settings')->where('name', '=', 'request_user_agent')->update(['value' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
