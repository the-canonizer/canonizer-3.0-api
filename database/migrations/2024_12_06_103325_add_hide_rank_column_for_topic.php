<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHideRankColumnForTopic extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('topic')) {
            if (!Schema::hasColumn('topic', 'is_rank_hidden')) {
                Schema::table('topic', function (Blueprint $table) {
                    $table->boolean('is_rank_hidden')->default(0)->after('is_one_level');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('topic')) {
            if (Schema::hasColumn('topic', 'is_rank_hidden')) {
                Schema::table('topic', function (Blueprint $table) {
                    $table->dropColumn('is_rank_hidden');
                });
            }
        }
    }
}
