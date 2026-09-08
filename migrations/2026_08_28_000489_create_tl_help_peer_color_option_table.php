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
        Schema::create('tl_help_peer_color_option', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_help_peer_color_option_peer_color_option', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_peer_color_option')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('hidden')->default(false);
            $table->integer('color_id');
            $table->uuid('colors')->nullable();
            $table->uuid('dark_colors')->nullable();
            $table->integer('channel_min_level')->nullable();
            $table->integer('group_min_level')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_peer_color_option_peer_color_option');
        Schema::dropIfExists('tl_help_peer_color_option');
    }
};
