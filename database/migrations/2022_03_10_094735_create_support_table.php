<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('support')) {
            Schema::create('suport', function (Blueprint $table) {
                $table->increments('support_id');
                $table->integer('nick_name_id');
                $table->integer('delegate_nick_name_id')->default(0);
                $table->integer('topic_num');
                $table->integer('camp_num');
                $table->integer('support_order')->default(1);
                $table->integer('start')->default(0);
                $table->integer('end')->default(0);  
                $table->integer('flags')->nullable();
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
        Schema::dropIfExists('support');
    }
}
