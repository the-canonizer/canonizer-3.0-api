<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesFolderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('file_folder')) {
            Schema::create('file_folder', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->string('name', 100)->unique();
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
        Schema::dropIfExists('file_folder');
    }
}
