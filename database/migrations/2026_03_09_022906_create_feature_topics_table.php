<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feature_topics', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->integer('topic_num')->unsigned();
            $table->integer('camp_num')->unsigned()->nullable();
            $table->string('file_relative_path', 500)->nullable();
            $table->string('file_full_path', 500)->nullable();
            $table->tinyInteger('active')->default(1);
            $table->integer('created_at')->unsigned();
            $table->integer('updated_at')->unsigned()->nullable();
            $table->integer('deleted_at')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_topics');
    }
};
