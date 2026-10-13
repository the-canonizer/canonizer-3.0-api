<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * credential_id can be up to 1023 raw bytes per the WebAuthn spec, so it is stored whole as
 * TEXT; uniqueness and lookups use credential_id_hash (SHA-256 hex) because TEXT can't be
 * indexed whole. user_id matches person.id (BIGINT UNSIGNED) and cascades on delete.
 */
class CreateUserPasskeysTable extends Migration
{
    public function up()
    {
        Schema::create('user_passkeys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->text('credential_id'); // base64url
            $table->char('credential_id_hash', 64)->unique();
            $table->text('public_key'); // PEM
            $table->unsignedBigInteger('sign_count')->default(0);
            $table->string('transports')->nullable(); // JSON array
            $table->string('aaguid', 64)->nullable(); // hex
            $table->string('label')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('person')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_passkeys');
    }
}
