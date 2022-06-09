<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsFeedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('news_feed')) {
            Schema::create('news_feed', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('topic_num')->default(0);
                $table->integer('camp_num')->default(0);
                $table->text('display_text');
                $table->string('link')->nullable();
                $table->integer('order_id');
                $table->integer('available_for_child')->default(0);
                $table->integer('submit_time')->default(0);
                $table->integer('end_time')->nullable();
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
        Schema::dropIfExists('news_feed');
    }
}
