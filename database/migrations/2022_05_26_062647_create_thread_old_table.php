<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThreadOldTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('thread_old')) {
            Schema::create('thread_old', function (Blueprint $table) {
                $table->bigIncrements('thread_id');
                $table->unsignedBigInteger('thread_num')->default(0);
                $table->unsignedBigInteger('topic_num')->default(0);
                $table->unsignedBigInteger('camp_num')->default(0);
                $table->text('subject')->default(0);
                $table->unsignedInteger('views')->default(0);
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
        Schema::dropIfExists('thread_old');
    }
}
