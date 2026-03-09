<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('settings')->insert([
            ['name' => 'paddle', 'value' => '0'],
            ['name' => 'paddle_mode', 'value' => 'live'],
            ['name' => 'paddle_api_key', 'value' => ''],
            ['name' => 'paddle_client_token', 'value' => ''],
            ['name' => 'paddle_wh_secret', 'value' => ''],
        ]);

        Schema::table('plans', function (Blueprint $table) {
            $table->string('currency', 12)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
