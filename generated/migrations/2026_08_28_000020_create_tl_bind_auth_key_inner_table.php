<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tl_bind_auth_key_inner', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_59c3a67d30c1e356a85a1ede');
            $table->index('account_id', 'ix_c8cf4c43e66d164ce91e779d');
        });
        Schema::create('tl_bind_auth_key_inner_bind_auth_key_inner', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bind_auth_key_inner')->cascadeOnDelete();
            $table->bigInteger('nonce');
            $table->bigInteger('temp_auth_key_id');
            $table->index('temp_auth_key_id', 'ix_510f10d7821c60301c257437');
            $table->bigInteger('perm_auth_key_id');
            $table->index('perm_auth_key_id', 'ix_4452b975a31f31decfeb71ae');
            $table->bigInteger('temp_session_id');
            $table->index('temp_session_id', 'ix_ffe1b6de8fedf5d4864173da');
            $table->integer('expires_at');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1612df208560a48ea5f02a91');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bind_auth_key_inner_bind_auth_key_inner');
        Schema::dropIfExists('tl_bind_auth_key_inner');
    }
};
