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
        Schema::create('tl_contacts_top_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_contacts_top_peers_top_peers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_contacts_top_peers')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_contacts_top_peers_top_peers__categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_top_peers_top_peers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f95b8e3b38d9001e6285');
        });
        Schema::create('tl_contacts_top_peers_top_peers__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_top_peers_top_peers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_4b81e21996936281668f');
        });
        Schema::create('tl_contacts_top_peers_top_peers__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_contacts_top_peers_top_peers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_9985806507b85ce6dd49');
        });
        Schema::create('tl_contacts_top_peers_top_peers_disabled', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_contacts_top_peers')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_contacts_top_peers_top_peers_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_contacts_top_peers')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contacts_top_peers_top_peers_not_modified');
        Schema::dropIfExists('tl_contacts_top_peers_top_peers_disabled');
        Schema::dropIfExists('tl_contacts_top_peers_top_peers__users');
        Schema::dropIfExists('tl_contacts_top_peers_top_peers__chats');
        Schema::dropIfExists('tl_contacts_top_peers_top_peers__categories');
        Schema::dropIfExists('tl_contacts_top_peers_top_peers');
        Schema::dropIfExists('tl_contacts_top_peers');
    }
};
