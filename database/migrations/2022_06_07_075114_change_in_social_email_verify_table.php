<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeInSocialEmailVerifyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('social_email_verify') && Schema::hasColumn('social_email_verify', 'created_at') && Schema::hasColumn('social_email_verify', 'updated_at')) {
            Schema::table('social_email_verify', function (Blueprint $table) {
                $table->Integer('created_at')->change();
                $table->Integer('updated_at')->change();
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
        Schema::table('social_email_verify', function (Blueprint $table) {
            //
        });
    }
}
