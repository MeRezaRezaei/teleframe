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
        Schema::create('tl_stories_peer_stories_peer_stories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('stories')->nullable();
            $table->index('stories', 'ix_29e538459a26634bb143d087');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_25d653f36fd10d9f4855e384');
            $table->index('account_id', 'ix_02c328c37a1fb578c0d94b01');
        });
        Schema::create('tl_stories_peer_stories_peer_stories__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_605fc2165cd5945d73089819')->references('id')->on('tl_stories_peer_stories_peer_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0e76d0669187fdfdd988');
            $table->index('account_id', 'ix_4dc0a13ddf8bd75931cef95c');
        });
        Schema::create('tl_stories_peer_stories_peer_stories__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_d83e33c2774f0cea4e62d6ad')->references('id')->on('tl_stories_peer_stories_peer_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
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
    }
};
