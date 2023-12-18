<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nick_name', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->after('owner_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('nick_name', 'user_id')) {
            Schema::table('nick_name', function (Blueprint $table) {
                $table->dropForeign('nick_name_user_id_foreign');
                $table->dropColumn('user_id');
            });
        }
    }
};