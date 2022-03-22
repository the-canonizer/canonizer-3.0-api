<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Countries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('phone_code', 255);
                $table->string('country_code', 255);
                $table->string('name', 255);
                $table->string('alpha_3', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->tinyInteger('status')->default(1)->comment('0 => Inactive, 1 => Active');
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
        Schema::dropIfExists('countries');
    }
}
