<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTopicSupportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('topic_support')) {
            Schema::create('topic_support', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('topic_num')->default(0);
                $table->integer('nick_name_id')->nullable();
                $table->integer('delegate_nick_id')->nullable();
                $table->integer('submit_time')->nullable();
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
        Schema::dropIfExists('topic_support');
    }
}
