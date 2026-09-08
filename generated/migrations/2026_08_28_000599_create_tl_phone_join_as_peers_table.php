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
        Schema::create('tl_phone_join_as_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_phone_join_as_peers_join_as_peers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_join_as_peers')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_phone_join_as_peers_join_as_peers__peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_join_as_peers_join_as_peers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_6c44ef7c01564ec5430b');
        });
        Schema::create('tl_phone_join_as_peers_join_as_peers__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_join_as_peers_join_as_peers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_2cc3507b519d187a9330');
        });
        Schema::create('tl_phone_join_as_peers_join_as_peers__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_join_as_peers_join_as_peers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_46f25a9cf86aed2a1720');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_join_as_peers_join_as_peers__users');
        Schema::dropIfExists('tl_phone_join_as_peers_join_as_peers__chats');
        Schema::dropIfExists('tl_phone_join_as_peers_join_as_peers__peers');
        Schema::dropIfExists('tl_phone_join_as_peers_join_as_peers');
        Schema::dropIfExists('tl_phone_join_as_peers');
    }
};
