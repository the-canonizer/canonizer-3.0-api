<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeColumnsOfNewsFeedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('news_feed', function (Blueprint $table) {
            $table->integer('submit_time_tmp')->nullable();
            $table->integer('end_time_tmp')->nullable();
        });

        DB::STATEMENT('update news_feed set submit_time_tmp = UNIX_TIMESTAMP(CURRENT_TIMESTAMP)');
        DB::STATEMENT('update news_feed set end_time_tmp = CASE WHEN end_time IS NOT NULL THEN UNIX_TIMESTAMP(CURRENT_TIMESTAMP) ELSE NULL END');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('news_feed', function (Blueprint $table) {
            $table->dropColumn('submit_time_tmp');
            $table->dropColumn('end_time_tmp');
        });
    }
}
