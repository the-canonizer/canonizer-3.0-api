<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentUserIdToPersonTable extends Migration
{
    public function up()
    {
        Schema::table('person', function (Blueprint $table) {
            $table->integer('parent_user_id')->nullable()->default(null)->after('id');
            $table->index('parent_user_id');
        });
    }

    public function down()
    {
        Schema::table('person', function (Blueprint $table) {
            $table->dropIndex(['parent_user_id']);
            $table->dropColumn('parent_user_id');
        });
    }
}
