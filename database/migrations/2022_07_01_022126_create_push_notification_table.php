<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePushNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('push_notification')) {
            Schema::create('push_notification', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->integer('topic_num')->nullable();
                $table->integer('camp_num')->nullable();
                $table->string('notification_type', 255);
                $table->text('message_body');
                $table->string('fcm_token', 500)->nullable();
                $table->tinyInteger('is_read')->default(0)->comment('0 => Not Read, 1 => Read');
                $table->timestamps();
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
        Schema::dropIfExists('push_notification');
    }
}
