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
        Schema::create('tl_theme_theme', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('creator')->default(false);
            $table->boolean('tl_default')->default(false);
            $table->boolean('for_chat')->default(false);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->text('slug')->nullable();
            $table->text('title')->nullable();
            $table->bigInteger('document')->nullable();
            $table->index('document', 'ix_97893d19d813272e8be60da0');
            $table->text('emoticon')->nullable();
            $table->integer('installs_count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4ebcfeac52d1e476330dd0e7');
            $table->index('account_id', 'ix_6637dfe3b4ed125902f8b000');
        });
        Schema::create('tl_theme_theme__settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_theme_theme', 'id', 'fk_b3bed1bc4dde67224180e6cb')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_cabe67af974f351c9860');
            $table->index('account_id', 'ix_2e3c64951b52d0adba86b284');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_theme_theme__settings');
        Schema::dropIfExists('tl_theme_theme');
    }
};
