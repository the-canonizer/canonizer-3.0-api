<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('languages')) {
            Schema::create('languages', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50);
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));;
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
        Schema::dropIfExists('languages');
    }
}
