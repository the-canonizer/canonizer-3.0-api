<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnInPushNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('push_notification') && !Schema::hasColumn('push_notification', 'message_title') && !Schema::hasColumn('push_notification', 'message_response')) {
            Schema::table('push_notification', function (Blueprint $table) {
                $table->string('message_title', 255);
                $table->string('message_response', 500)->nullable();
                $table->Integer('created_at')->change();
                $table->Integer('updated_at')->change();
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
            //
        });
    }
}
