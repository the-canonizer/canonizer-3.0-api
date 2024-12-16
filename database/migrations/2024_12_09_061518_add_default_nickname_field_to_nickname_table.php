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
            $table->after('private', function (Blueprint $table) {
                $table->boolean('default')->default(false)->comment('This column set a default nickname for only first contribution.');
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('nick_name', 'default')) {
            Schema::table('nick_name', function (Blueprint $table) {
                $table->dropColumn('default');
            });
        }
    }
};
