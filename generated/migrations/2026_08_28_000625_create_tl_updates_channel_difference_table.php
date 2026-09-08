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
        Schema::create('tl_updates_channel_difference', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_updates_channel_difference_channel_difference', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates_channel_difference')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('final')->default(false);
            $table->integer('pts');
            $table->integer('timeout')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_updates_channel_difference_channel_differe_ae354d886f41', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_channel_difference_channel_difference')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e5674676a3b55c3a7e5c');
        });
        Schema::create('tl_updates_channel_difference_channel_differe_a6da2c454a94', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_channel_difference_channel_difference')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ea9e2fbd771127157afe');
        });
        Schema::create('tl_updates_channel_difference_channel_difference__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_channel_difference_channel_difference')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_622b09ff679c2edabca5');
        });
        Schema::create('tl_updates_channel_difference_channel_difference__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_channel_difference_channel_difference')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_c0061fda1c7224e24825');
        });
        Schema::create('tl_updates_channel_difference_channel_difference_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates_channel_difference')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('final')->default(false);
            $table->integer('pts');
            $table->integer('timeout')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_updates_channel_difference_channel_difference_too_long', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates_channel_difference')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('final')->default(false);
            $table->integer('timeout')->nullable();
            $table->uuid('dialog');
            $table->timestamps();
        });
        Schema::create('tl_updates_channel_difference_channel_differe_ecde56c8e6a0', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_channel_difference_channel_difference_too_long')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_afaa48c05fc1911e2f23');
        });
        Schema::create('tl_updates_channel_difference_channel_differe_82fee27980cc', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_channel_difference_channel_difference_too_long')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_c5482b5e38bc9306763d');
        });
        Schema::create('tl_updates_channel_difference_channel_differe_0962ef95307b', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_channel_difference_channel_difference_too_long')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8b10a9b8fd88b9e0bd0d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_updates_channel_difference_channel_differe_0962ef95307b');
        Schema::dropIfExists('tl_updates_channel_difference_channel_differe_82fee27980cc');
        Schema::dropIfExists('tl_updates_channel_difference_channel_differe_ecde56c8e6a0');
        Schema::dropIfExists('tl_updates_channel_difference_channel_difference_too_long');
        Schema::dropIfExists('tl_updates_channel_difference_channel_difference_empty');
        Schema::dropIfExists('tl_updates_channel_difference_channel_difference__users');
        Schema::dropIfExists('tl_updates_channel_difference_channel_difference__chats');
        Schema::dropIfExists('tl_updates_channel_difference_channel_differe_a6da2c454a94');
        Schema::dropIfExists('tl_updates_channel_difference_channel_differe_ae354d886f41');
        Schema::dropIfExists('tl_updates_channel_difference_channel_difference');
        Schema::dropIfExists('tl_updates_channel_difference');
    }
};
