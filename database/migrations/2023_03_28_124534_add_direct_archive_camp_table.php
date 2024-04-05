<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDirectArchiveCampTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('camp') && !Schema::hasColumn('camp', 'direct_archive')) {
            Schema::table('camp', function (Blueprint $table) {
                $table->tinyInteger('direct_archive')->default(0)->comment('0 => unarchived or parent is archived, 1 => directly Archive')->after('is_archive');
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
            $table->dropColumn('direct_archive');
        });
    }
}
