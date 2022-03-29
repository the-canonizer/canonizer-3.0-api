<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('uploads')) {
            Schema::create('uploads', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->integer('folder_id')->nullable();
                $table->string('file_id', 255);
                $table->string('file_name', 255);
                $table->string('file_type', 50);
                $table->string('file_path', 500)->nullable();
                $table->unsignedInteger('created_at');
                $table->unsignedInteger('updated_at');
                $table->unsignedInteger('deleted_at')->nullable();
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
        Schema::dropIfExists('uploads');
    }
}
