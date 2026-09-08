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
        Schema::create('tl_top_peer_category', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_top_peer_category_top_peer_category_bots_app', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_top_peer_category_top_peer_category_bots_guest_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_top_peer_category_top_peer_category_bots_inline', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_top_peer_category_top_peer_category_bots_p_m', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_top_peer_category_top_peer_category_channels', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_top_peer_category_top_peer_category_correspondents', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_top_peer_category_top_peer_category_forward_chats', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_top_peer_category_top_peer_category_forward_users', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_top_peer_category_top_peer_category_groups', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_top_peer_category_top_peer_category_phone_calls', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_phone_calls');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_groups');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_forward_users');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_forward_chats');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_correspondents');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_channels');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_bots_p_m');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_bots_inline');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_bots_guest_chat');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_bots_app');
        Schema::dropIfExists('tl_top_peer_category');
    }
};
