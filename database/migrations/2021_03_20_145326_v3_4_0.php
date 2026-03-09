<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class V340 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('settings')->insert(
            [
                [
                    'name' => 'short_domain',
                    'value' => '0'
                ]
            ]
        );

        Schema::table('link_pixel', function (Blueprint $table) {
            $table->integer('link_id')->unsigned()->change();
            $table->integer('pixel_id')->unsigned()->change();
        });

        Schema::table('links', function (Blueprint $table) {
            $table->increments('id')->change();
            $table->integer('user_id')->unsigned()->nullable()->change();
            $table->integer('space_id')->unsigned()->nullable()->change();
            $table->integer('domain_id')->unsigned()->nullable()->change();
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->increments('id')->change();
            $table->integer('user_id')->unsigned()->change();
        });

        Schema::table('spaces', function (Blueprint $table) {
            $table->increments('id')->change();
            $table->integer('user_id')->unsigned()->change();
        });

        Schema::table('pixels', function (Blueprint $table) {
            $table->increments('id')->change();
            $table->integer('user_id')->unsigned()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
