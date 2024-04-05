<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsArchiveCampTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('camp') && !Schema::hasColumn('camp', 'is_archive')) {
            Schema::table('camp', function (Blueprint $table) {
                $table->tinyInteger('is_archive')->default(0)->comment('0 => Unarchive, 1 => Archive')->after('is_one_level');
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
        Schema::table('camp', function (Blueprint $table) {
            $table->dropColumn('is_archive');
        });
    }
}
