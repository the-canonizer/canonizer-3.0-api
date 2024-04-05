<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOldParentCampNumCampTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('camp') && !Schema::hasColumn('camp', 'old_parent_camp_num')) {
            Schema::table('camp', function (Blueprint $table) {
                $table->integer('old_parent_camp_num')->nullable();
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
        if (Schema::hasColumn('camp', 'old_parent_camp_num')) {
            Schema::table('camp', function (Blueprint $table) {
                $table->dropColumn('old_parent_camp_num');
            });
        }
    }
}
