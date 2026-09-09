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
            $table->index('constructor_id', 'ix_2fc059a5896bc19c90aeae1a');
            $table->index('account_id', 'ix_1df063408b1555ebb3fc0b37');
        });
        Schema::create('tl_top_peer_category_top_peer_category_bots_app', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cc696556a1815b58e9fb3550');
        });
        Schema::create('tl_top_peer_category_top_peer_category_bots_guest_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_92f1f0035626f08ad2d0b5af');
        });
        Schema::create('tl_top_peer_category_top_peer_category_bots_inline', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c95f52e14b8c8bb3e72e2ac8');
        });
        Schema::create('tl_top_peer_category_top_peer_category_bots_p_m', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4e7cca2dbf47edb49bd541cd');
        });
        Schema::create('tl_top_peer_category_top_peer_category_channels', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4a6ce708657db22fcc0e61ff');
        });
        Schema::create('tl_top_peer_category_top_peer_category_correspondents', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8d477d29fa3339b602ee24f3');
        });
        Schema::create('tl_top_peer_category_top_peer_category_forward_chats', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f19343109f7db4567df3cf62');
        });
        Schema::create('tl_top_peer_category_top_peer_category_forward_users', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_468d4134e4c02287e3665d44');
        });
        Schema::create('tl_top_peer_category_top_peer_category_groups', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c9a0b4d5e6d883414fa73a1b');
        });
        Schema::create('tl_top_peer_category_top_peer_category_phone_calls', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ddb5bfd8a049a5b941373f57');
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
