<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostOldTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('post_old')) {
            Schema::create('post_old', function (Blueprint $table) {
                $table->bigIncrements('post_id');
                $table->bigInteger('post_num')->default(0);
                $table->bigInteger('thread_num')->default(0);
                $table->bigInteger('topic_num')->default(0);
                $table->bigInteger('camp_num')->default(0);
                $table->bigInteger('nick_id')->default(0);
                $table->text('message');
                $table->integer('submit_time')->default(0);
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
        Schema::dropIfExists('post_old');
    }
}
