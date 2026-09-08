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
        Schema::create('tl_updates_difference', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_updates_difference_difference', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates_difference')->cascadeOnDelete();
            $table->uuid('state');
            $table->timestamps();
        });
        Schema::create('tl_updates_difference_difference__new_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_984b2af625660e04e6db');
        });
        Schema::create('tl_updates_difference_difference__new_encrypted_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_a753c9ceec7276a92004');
        });
        Schema::create('tl_updates_difference_difference__other_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_42087b8394f822c27d04');
        });
        Schema::create('tl_updates_difference_difference__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_1e51c017a1272b9db7f2');
        });
        Schema::create('tl_updates_difference_difference__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_15bc8d11547fcb45b9b2');
        });
        Schema::create('tl_updates_difference_difference_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates_difference')->cascadeOnDelete();
            $table->integer('date');
            $table->integer('seq');
            $table->timestamps();
        });
        Schema::create('tl_updates_difference_difference_slice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates_difference')->cascadeOnDelete();
            $table->uuid('intermediate_state');
            $table->timestamps();
        });
        Schema::create('tl_updates_difference_difference_slice__new_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8a197ff68922b821fd18');
        });
        Schema::create('tl_updates_difference_difference_slice__new_e_864e87a82655', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_4695944bc26300827182');
        });
        Schema::create('tl_updates_difference_difference_slice__other_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d12bf810563b935b9de6');
        });
        Schema::create('tl_updates_difference_difference_slice__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_9beb1d81e061c1e94742');
        });
        Schema::create('tl_updates_difference_difference_slice__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_difference_difference_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_c94152998cdebdaea062');
        });
        Schema::create('tl_updates_difference_difference_too_long', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates_difference')->cascadeOnDelete();
            $table->integer('pts');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_updates_difference_difference_too_long');
        Schema::dropIfExists('tl_updates_difference_difference_slice__users');
        Schema::dropIfExists('tl_updates_difference_difference_slice__chats');
        Schema::dropIfExists('tl_updates_difference_difference_slice__other_updates');
        Schema::dropIfExists('tl_updates_difference_difference_slice__new_e_864e87a82655');
        Schema::dropIfExists('tl_updates_difference_difference_slice__new_messages');
        Schema::dropIfExists('tl_updates_difference_difference_slice');
        Schema::dropIfExists('tl_updates_difference_difference_empty');
        Schema::dropIfExists('tl_updates_difference_difference__users');
        Schema::dropIfExists('tl_updates_difference_difference__chats');
        Schema::dropIfExists('tl_updates_difference_difference__other_updates');
        Schema::dropIfExists('tl_updates_difference_difference__new_encrypted_messages');
        Schema::dropIfExists('tl_updates_difference_difference__new_messages');
        Schema::dropIfExists('tl_updates_difference_difference');
        Schema::dropIfExists('tl_updates_difference');
    }
};
