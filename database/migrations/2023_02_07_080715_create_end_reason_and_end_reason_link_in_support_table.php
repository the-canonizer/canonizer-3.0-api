<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEndReasonAndEndReasonLinkInSupportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (Schema::hasTable('support')) {
            if (!Schema::hasColumn('support', 'end_reason')) {
                Schema::table('support', function (Blueprint $table) {
                    $table->text('end_reason')->nullable();
                });
            }
            if (!Schema::hasColumn('support', 'end_reason_link')) {
                Schema::table('support', function (Blueprint $table) {
                    $table->string('end_reason_link', 255)->nullable();
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

        if (Schema::hasTable('support')) {
            if (Schema::hasColumn('support', 'end_reason')) {
                Schema::table('support', function (Blueprint $table) {
                    $table->dropColumn('end_reason');
                });
            }
            if (Schema::hasColumn('support', 'end_reason_link')) {
                Schema::table('support', function (Blueprint $table) {
                    $table->dropColumn('end_reason_link');
                });
            }
        }
    }
}
