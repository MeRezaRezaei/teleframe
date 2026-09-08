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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_bind_auth_key_inner_bind_auth_key_inner', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bind_auth_key_inner')->cascadeOnDelete();
            $table->bigInteger('nonce');
            $table->bigInteger('temp_auth_key_id');
            $table->bigInteger('perm_auth_key_id');
            $table->bigInteger('temp_session_id');
            $table->integer('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bind_auth_key_inner_bind_auth_key_inner');
        Schema::dropIfExists('tl_bind_auth_key_inner');
    }
};
