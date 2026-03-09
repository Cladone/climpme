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
        DB::table('settings')->insert(
            [
                ['name' => 'storage_driver', 'value' => 'public'],
                ['name' => 'storage_key', 'value' => ''],
                ['name' => 'storage_secret', 'value' => ''],
                ['name' => 'storage_region', 'value' => ''],
                ['name' => 'storage_bucket', 'value' => ''],
                ['name' => 'storage_url', 'value' => ''],
                ['name' => 'storage_endpoint', 'value' => ''],
                ['name' => 'storage_use_path_style_endpoint', 'value' => ''],
                ['name' => 'storage_throw', 'value' => ''],
                ['name' => 'user_avatar_filesize', 'value' => 2],
                ['name' => 'user_avatar_size', 'value' => 256],
                ['name' => 'user_avatar_format', 'value' => 'jpeg,png,gif,bmp'],
            ]
        );

        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar', 48)->nullable()->after('password');
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
