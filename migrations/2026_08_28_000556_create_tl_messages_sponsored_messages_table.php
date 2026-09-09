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
            $table->index('constructor_id', 'ix_60d426dab345ca60890f287e');
            $table->index('account_id', 'ix_0eaed410949ae124e8b978bc');
        });
        Schema::create('tl_messages_sponsored_messages_sponsored_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_sponsored_messages')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('posts_between')->nullable();
            $table->integer('start_delay')->nullable();
            $table->integer('between_delay')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_92767ce76314f5213f2c5519');
        });
        Schema::create('tl_messages_sponsored_messages_sponsored_mess_8fd982913adc', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_sponsored_messages_sponsored_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1ec8e14f08f1b3ccd227');
            $table->index('account_id', 'ix_ff62278c455383dada3063a6');
        });
        Schema::create('tl_messages_sponsored_messages_sponsored_messages__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_sponsored_messages_sponsored_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_825ed9e4db8b88fbebfe');
            $table->index('account_id', 'ix_ea84a89a79160c556eaf9a7d');
        });
        Schema::create('tl_messages_sponsored_messages_sponsored_messages__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_sponsored_messages_sponsored_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5c6642e9d55463a138e8');
            $table->index('account_id', 'ix_50cf0793d0101641ef75dbf7');
        });
        Schema::create('tl_messages_sponsored_messages_sponsored_messages_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_sponsored_messages')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_138753c635a543699afa6dcb');
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
