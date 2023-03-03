<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReasonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('reasons')) {
            Schema::create('reasons', function (Blueprint $table) {
                $table->id();
                $table->string('reason', 255);
                $table->tinyInteger('status')->default(1)->comment('0 => InActive, 1 => Active');
                $table->timestamps();
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
        Schema::dropIfExists('reason');
    }
}
