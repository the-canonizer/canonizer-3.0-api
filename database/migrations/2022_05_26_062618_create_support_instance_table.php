<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupportInstanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('support_instance')) {
            Schema::create('support_instance', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('topic_support_id');
                $table->integer('camp_num');
                $table->integer('support_order');
                $table->string('submit_time');
                $table->string('status');
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
        Schema::dropIfExists('support_instance');
    }
}
