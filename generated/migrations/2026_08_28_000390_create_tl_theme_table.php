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
        Schema::create('tl_theme', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c5e91e32e3def9d3153a2547');
            $table->index('account_id', 'ix_b3ecab8229cc6da864c11a6d');
        });
        Schema::create('tl_theme_theme', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_theme')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('creator')->default(false);
            $table->boolean('tl_default')->default(false);
            $table->boolean('for_chat')->default(false);
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->text('slug');
            $table->text('title');
            $table->uuid('document')->nullable();
            $table->index('document', 'ix_97893d19d813272e8be60da0');
            $table->text('emoticon')->nullable();
            $table->integer('installs_count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6637dfe3b4ed125902f8b000');
            $table->unique(['account_id', 'tl_id'], 'ux_c8bf640fc39a64b59b36');
        });
        Schema::create('tl_theme_theme__settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_theme_theme')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_cabe67af974f351c9860');
            $table->index('account_id', 'ix_2e3c64951b52d0adba86b284');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_theme_theme__settings');
        Schema::dropIfExists('tl_theme_theme');
        Schema::dropIfExists('tl_theme');
    }
};
