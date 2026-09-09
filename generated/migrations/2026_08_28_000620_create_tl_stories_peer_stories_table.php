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
            $table->index('constructor_id', 'ix_dbdab55016a57dd75115286d');
            $table->index('account_id', 'ix_3a15e52b027df2e7c400fed7');
        });
        Schema::create('tl_stories_peer_stories_peer_stories', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stories_peer_stories')->cascadeOnDelete();
            $table->uuid('stories');
            $table->index('stories', 'ix_29e538459a26634bb143d087');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_02c328c37a1fb578c0d94b01');
        });
        Schema::create('tl_stories_peer_stories_peer_stories__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_peer_stories_peer_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0e76d0669187fdfdd988');
            $table->index('account_id', 'ix_4dc0a13ddf8bd75931cef95c');
        });
        Schema::create('tl_stories_peer_stories_peer_stories__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_peer_stories_peer_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a38c08a2e27476f621b2');
            $table->index('account_id', 'ix_b5e8c3fbadeac9972c856a83');
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
