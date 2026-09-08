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
        Schema::create('tl_help_peer_colors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_help_peer_colors_peer_colors', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_peer_colors')->cascadeOnDelete();
            $table->integer('hash');
            $table->timestamps();
        });
        Schema::create('tl_help_peer_colors_peer_colors__colors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_peer_colors_peer_colors')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ac5b2a6fe1908222ee3d');
        });
        Schema::create('tl_help_peer_colors_peer_colors_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_peer_colors')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_peer_colors_peer_colors_not_modified');
        Schema::dropIfExists('tl_help_peer_colors_peer_colors__colors');
        Schema::dropIfExists('tl_help_peer_colors_peer_colors');
        Schema::dropIfExists('tl_help_peer_colors');
    }
};
