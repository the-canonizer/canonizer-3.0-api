<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddArchiveTimeColumnInCampTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('camp') && !Schema::hasColumn('camp', 'archive_action_time')) {
            Schema::table('camp', function (Blueprint $table) {
                $table->integer('archive_action_time')->default(0);
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
            $table->dropColumn('archive_action_time');
        });
    }
}
