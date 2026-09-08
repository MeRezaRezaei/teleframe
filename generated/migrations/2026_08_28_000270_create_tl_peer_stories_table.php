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
        Schema::create('tl_peer_stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_peer_stories_peer_stories', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_peer_stories')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('peer');
            $table->integer('max_read_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_peer_stories_peer_stories__stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_peer_stories_peer_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_91b33e4d9d8ccbbfb49d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_peer_stories_peer_stories__stories');
        Schema::dropIfExists('tl_peer_stories_peer_stories');
        Schema::dropIfExists('tl_peer_stories');
    }
};
