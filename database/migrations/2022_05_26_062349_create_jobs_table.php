<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->increments('id');
                $table->string('queue');
                $table->longText('payload');
                $table->tinyInteger('attempts');
                $table->integer('reserved_at')->nullable();
                $table->integer('available_at');
                $table->integer('created_at');
                $table->string('job_clazz')->nullable();
                $table->string('model_clazz')->nullable();
                $table->string('model_id')->nullable();
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
        Schema::dropIfExists('jobs');
    }
}
