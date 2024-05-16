<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('uploads')) {

            Schema::table('uploads', function (Blueprint $table) {
                if(!Schema::hasColumn('uploads', 'folder_id')){
                    $table->integer('folder_id')->nullable()->after('user_id');
                }
                if(!Schema::hasColumn('uploads', 'file_path')){
                    $table->string('file_path',555)->nullable()->after('file_type');
                }
                if(!Schema::hasColumn('uploads', 'deleted_at')){
                    $table->unsignedInteger('deleted_at')->nullable()->after('updated_at');
                }
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
        if (Schema::hasTable('uploads')) {
            Schema::table('uploads', function (Blueprint $table) {

                if(Schema::hasColumn('uploads', 'folder_id')){
                    $table->dropColumn('folder_id');
                }
                if(Schema::hasColumn('uploads', 'file_apth')){
                    $table->dropColumn('file_path');
                }
                if(Schema::hasColumn('uploads', 'deleted_at')){
                    $table->dropColumn('deleted_at');
                }
                
            });
        }
    }
}
