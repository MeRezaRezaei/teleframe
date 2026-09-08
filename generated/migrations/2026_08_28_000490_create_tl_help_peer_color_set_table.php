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
        Schema::create('tl_help_peer_color_set', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_help_peer_color_set_peer_color_profile_set', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_peer_color_set')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_help_peer_color_set_peer_color_profile_set_fb247985ae1f', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_peer_color_set_peer_color_profile_set')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f77324d389530c50b907');
        });
        Schema::create('tl_help_peer_color_set_peer_color_profile_set__bg_colors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_peer_color_set_peer_color_profile_set')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_9d678e4d8b91e056f239');
        });
        Schema::create('tl_help_peer_color_set_peer_color_profile_set_d09cf1f8f0b4', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_peer_color_set_peer_color_profile_set')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8199fc405b7c1eb4dee8');
        });
        Schema::create('tl_help_peer_color_set_peer_color_set', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_peer_color_set')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_help_peer_color_set_peer_color_set__colors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_peer_color_set_peer_color_set')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_60878cbab65c0f3904f2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_peer_color_set_peer_color_set__colors');
        Schema::dropIfExists('tl_help_peer_color_set_peer_color_set');
        Schema::dropIfExists('tl_help_peer_color_set_peer_color_profile_set_d09cf1f8f0b4');
        Schema::dropIfExists('tl_help_peer_color_set_peer_color_profile_set__bg_colors');
        Schema::dropIfExists('tl_help_peer_color_set_peer_color_profile_set_fb247985ae1f');
        Schema::dropIfExists('tl_help_peer_color_set_peer_color_profile_set');
        Schema::dropIfExists('tl_help_peer_color_set');
    }
};
