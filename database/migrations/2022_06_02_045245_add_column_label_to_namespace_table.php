<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnLabelToNamespaceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('namespace', function (Blueprint $table) {
            if(!Schema::hasColumn('namespace', 'label')) {
                $table->string('label');
            }

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('namespace', function (Blueprint $table) {
            // Drop Column
            if(Schema::hasColumn('namespace', 'label')) {
                $table->dropColumn('label');
            }

        });
    }
}
