<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSharesAlgoDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('shares_algo_data')) {
            Schema::create('shares_algo_data', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('nick_name_id');
                $table->date('as_of_date');
                $table->string('share_value');
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
        Schema::dropIfExists('shares_algo_data');
    }
}
