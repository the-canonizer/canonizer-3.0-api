<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProcessedJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('processed_jobs')) {
            Schema::create('processed_jobs', function (Blueprint $table) {
                $table->increments('id');
                $table->longText('payload')->nullable();
                $table->string('status')->nullable();
                $table->integer('code')->nullable();
                $table->longText('response')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->integer('topic_num')->nullable();
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
        Schema::dropIfExists('processed_jobs');
    }
}
