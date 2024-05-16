<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('statement')) {
            Schema::create('statement', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('topic_num')->default(0);
                $table->integer('camp_num')->default(0);
                $table->longText('value');
                $table->text('note')->nullable();
                $table->integer('submit_time')->default(0);
                $table->integer('submitter_nick_id')->default(0);
                $table->integer('go_live_time')->default(0);
                $table->integer('objector_nick_id')->nullable();
                $table->integer('object_time')->nullable();
                $table->text('object_reason')->nullable();
                $table->integer('proposed')->nullable();
                $table->bigInteger('replacement')->nullable();
                $table->string('language')->nullable();
                $table->integer('grace_period')->default(0);
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
        Schema::dropIfExists('statement');
    }
}
