<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddArchiveSupportFlagInSupportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('support') && !Schema::hasColumn('support', 'archive_support_flag')) {
            Schema::table('support', function (Blueprint $table) {
                $table->tinyInteger('archive_support_flag')->default(0)->comment('0 => Support ended temporarily, 1 => support ended permanentely');
                $table->timestamp('archive_support_flag_date')->nullable();
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
        Schema::table('support', function (Blueprint $table) {
            if(Schema::hasColumn('support', 'archive_support_flag')){
                $table->dropColumn('archive_support_flag');
                $table->dropColumn('archive_support_flag_date');
            }
        });
    }
}
