<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChangeAgreeLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('change_agree_logs')) {
            Schema::create('change_agree_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('change_id')->nullable();
                $table->integer('topic_num')->default(0);
                $table->integer('camp_num')->nullable();
                $table->integer('nick_name_id')->nullable();
                $table->string('change_for')->nullable();
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
        Schema::dropIfExists('change_agree_logs');
    }
}
