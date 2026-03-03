<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFulltextIndexToParsedValueStatement extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('statement', function (Blueprint $table) {
            $table->fullText('parsed_value', 'parsed_value_fulltext_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('statement', function (Blueprint $table) {
            $table->dropIndex('parsed_value_fulltext_index');
        });
    }
}
