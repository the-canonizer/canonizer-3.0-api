<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLabelInNNamespaceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('namespace') && !Schema::hasColumn('namespace', 'label')) {
            Schema::table('namespace', function (Blueprint $table) {
                $table->string('label')->nullable();
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
        if (Schema::hasTable('namespace') && Schema::hasColumn('namespace', 'label')) {
            Schema::table('namespace', function (Blueprint $table) {
                $table->dropColumn('label');
            });
        }
    }
}
