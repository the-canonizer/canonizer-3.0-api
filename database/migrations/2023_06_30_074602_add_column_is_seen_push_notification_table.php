<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
//use DB;

class AddColumnIsSeenPushNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('push_notification') && !Schema::hasColumn('push_notification','is_seen')) {
            Schema::table('push_notification', function (Blueprint $table) {
                $table->tinyInteger('is_seen')->default(0)->comment('0 => unseen, 1 => seen');
                $table->Integer('seen_time')->nullable();
             });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('push_notification', function (Blueprint $table) {
            $table->dropColumn('is_seen');
            $table->dropColumn('seen_time');
        });
    }
}
