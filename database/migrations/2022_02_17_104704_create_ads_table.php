<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('ads')) {
            Schema::create('ads', function (Blueprint $table) {
                $table->id();
                $table->string('client_id', 50);
                $table->unsignedBigInteger('page_id');
                $table->string('slot', 50);
                $table->string('format', '50')->nullable();
                $table->tinyInteger('adtest')->default('0')->comment('0 => Off, 1 => On');
                $table->tinyInteger('is_responsive')->default(1)->comment('0 => True, 1 => Off');
                $table->tinyInteger('status')->default(1)->comment('0 => Inactive, 1 => Active');
                $table->integer('create_time');
                $table->integer('update_time');
            });

            Schema::table('ads', function ($table) {
                $table->foreign('page_id')->references('id')->on('pages');
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
        Schema::dropIfExists('ads');
    }
}
