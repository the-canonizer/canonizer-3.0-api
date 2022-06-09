<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnNamesFromNewsFeedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('news_feed', function (Blueprint $table) {
            //
            $table->renameColumn('submit_time_tmp', 'submit_time');
            $table->renameColumn('end_time_tmp', 'end_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('news_feed', function (Blueprint $table) {
            //
            $table->renameColumn('submit_time', 'submit_time_tmp');
            $table->renameColumn('end_time', 'end_time_tmp');
        });
    }
}
