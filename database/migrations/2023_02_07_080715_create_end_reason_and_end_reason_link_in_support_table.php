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
            if (!Schema::hasColumn('support', 'reason')) {
                Schema::table('support', function (Blueprint $table) {
                    $table->text('reason')->nullable();
                });
            }
            if (!Schema::hasColumn('support', 'reason_link')) {
                Schema::table('support', function (Blueprint $table) {
                    $table->string('reason_link', 255)->nullable();
                });
            }
            if (!Schema::hasColumn('support', 'reason_summary')) {
                Schema::table('support', function (Blueprint $table) {
                    $table->string('reason_summary', 255)->nullable();
                });
            }
            if (!Schema::hasColumn('support', 'is_system_generated')) {
                Schema::table('support', function (Blueprint $table) {
                    $table->tinyInteger('is_system_generated')->default(0)->comment('0 => No, 1 => Yes');
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
            if (Schema::hasColumn('support', 'reason')) {
                Schema::table('support', function (Blueprint $table) {
                    $table->dropColumn('reason');
                });
            }
            if (Schema::hasColumn('support', 'reason_link')) {
                Schema::table('support', function (Blueprint $table) {
                    $table->dropColumn('reason_link');
                });
            }
        }
    }
}
