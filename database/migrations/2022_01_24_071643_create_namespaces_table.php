<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNamespacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('namespace')) {
            Schema::create('namespace', function (Blueprint $table) {
                $table->id();
                $table->integer('parent_id');
                $table->string('name');
                $table->string('label');
               // $table->timestamps();
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
        if (Schema::hasTable('namespace')) {
            Schema::dropIfExists('namespace');
        }
    }
}
