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
        Schema::create('tl_stories_peer_stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_stories_peer_stories_peer_stories', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stories_peer_stories')->cascadeOnDelete();
            $table->uuid('stories');
            $table->timestamps();
        });
        Schema::create('tl_stories_peer_stories_peer_stories__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_peer_stories_peer_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_0e76d0669187fdfdd988');
        });
        Schema::create('tl_stories_peer_stories_peer_stories__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_peer_stories_peer_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_a38c08a2e27476f621b2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stories_peer_stories_peer_stories__users');
        Schema::dropIfExists('tl_stories_peer_stories_peer_stories__chats');
        Schema::dropIfExists('tl_stories_peer_stories_peer_stories');
        Schema::dropIfExists('tl_stories_peer_stories');
    }
};
