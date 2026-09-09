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
        Schema::create('tl_future_salts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_79fdaf0ae8a17071b1af6d0f');
            $table->index('account_id', 'ix_4299386c09f70ed9836085b3');
        });
        Schema::create('tl_future_salts_future_salts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_future_salts')->cascadeOnDelete();
            $table->bigInteger('req_msg_id');
            $table->index('req_msg_id', 'ix_f45852a380e999bc64c03250');
            $table->integer('now');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1b86416ec9c7cdd5b46f3d18');
        });
        Schema::create('tl_future_salts_future_salts__salts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_future_salts_future_salts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ee1429652d938fee9d22');
            $table->index('account_id', 'ix_0ec89e369c8b8d7eb47133a3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_future_salts_future_salts__salts');
        Schema::dropIfExists('tl_future_salts_future_salts');
        Schema::dropIfExists('tl_future_salts');
    }
};
