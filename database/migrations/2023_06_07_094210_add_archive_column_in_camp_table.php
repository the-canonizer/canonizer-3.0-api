<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddArchiveColumnInCampTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('camp')) {
            if(!Schema::hasColumn('camp', 'is_archive')){
                Schema::table('camp', function (Blueprint $table) {
                    $table->tinyInteger('is_archive')->default(0)->comment('0 => Unarchive, 1 => Archive')->after('is_one_level');
                });
            }
            if(!Schema::hasColumn('camp', 'direct_archive')){
                Schema::table('camp', function (Blueprint $table) {
                    $table->tinyInteger('direct_archive')->default(0)->comment('0 => unarchived or parent is archived, 1 => directly Archive')->after('is_archive');
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
        Schema::table('camp', function (Blueprint $table) {
            if(Schema::hasColumn('camp', 'is_archive')){
                $table->dropColumn('is_archive');
                $table->dropColumn('direct_archive');
            }
        });
    }
}
