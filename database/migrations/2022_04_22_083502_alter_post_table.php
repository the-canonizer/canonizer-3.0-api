<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPostTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('post') && Schema::hasColumn('post', 'c_thread_id')) {
            Schema::table('post', function (Blueprint $table) {
                $table->renameColumn('c_thread_id', 'thread_id');
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
        if (Schema::hasTable('post') && Schema::hasColumn('post', 'c_thread_id')) {
            Schema::table('post', function (Blueprint $table) {
                $table->renameColumn('thread_id', 'c_thread_id');
            });
        }
    }
}
