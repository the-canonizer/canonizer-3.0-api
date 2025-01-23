<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFulltextIndexToCampNameOnCampTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('camp', function (Blueprint $table) {
            $table->fullText('camp_name', 'camp_name_fulltext_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('camp', function (Blueprint $table) {
            $table->dropIndex('camp_name_fulltext_index');
        });
    }
}
