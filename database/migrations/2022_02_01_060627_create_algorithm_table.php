<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAlgorithmTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('algorithms')) {
            Schema::create('algorithms', function (Blueprint $table) {
                $table->id();
                $table->string('algorithm_key', 50)->unique();
                $table->string('algorithm_label',50);
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
        if (Schema::hasTable('algorithms')) {
           Schema::dropIfExists('algorithms');
        }
    }
}
