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
        Schema::create('tl_messages_sponsored_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_sponsored_messages_sponsored_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_sponsored_messages')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('posts_between')->nullable();
            $table->integer('start_delay')->nullable();
            $table->integer('between_delay')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_messages_sponsored_messages_sponsored_mess_8fd982913adc', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_sponsored_messages_sponsored_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_1ec8e14f08f1b3ccd227');
        });
        Schema::create('tl_messages_sponsored_messages_sponsored_messages__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_sponsored_messages_sponsored_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_825ed9e4db8b88fbebfe');
        });
        Schema::create('tl_messages_sponsored_messages_sponsored_messages__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_sponsored_messages_sponsored_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_5c6642e9d55463a138e8');
        });
        Schema::create('tl_messages_sponsored_messages_sponsored_messages_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_sponsored_messages')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_sponsored_messages_sponsored_messages_empty');
        Schema::dropIfExists('tl_messages_sponsored_messages_sponsored_messages__users');
        Schema::dropIfExists('tl_messages_sponsored_messages_sponsored_messages__chats');
        Schema::dropIfExists('tl_messages_sponsored_messages_sponsored_mess_8fd982913adc');
        Schema::dropIfExists('tl_messages_sponsored_messages_sponsored_messages');
        Schema::dropIfExists('tl_messages_sponsored_messages');
    }
};
