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
        Schema::create('tl_messages_chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_3bb36d860f3f49756f4478c6');
            $table->index('account_id', 'ix_9f5c1fcf79dc860496979a17');
        });
        Schema::create('tl_messages_chats_chats', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_chats')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8c0c5565c465b7a42e865438');
        });
        Schema::create('tl_messages_chats_chats__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_chats_chats')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a161e15d51ec8f4755a4');
            $table->index('account_id', 'ix_fbee355b21a927a0c6274a5a');
        });
        Schema::create('tl_messages_chats_chats_slice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_chats')->cascadeOnDelete();
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f01b106cb752631407da5930');
        });
        Schema::create('tl_messages_chats_chats_slice__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_chats_chats_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ec48ed0b85bc1a232040');
            $table->index('account_id', 'ix_5ff73df8692424d7b29206eb');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_chats_chats_slice__chats');
        Schema::dropIfExists('tl_messages_chats_chats_slice');
        Schema::dropIfExists('tl_messages_chats_chats__chats');
        Schema::dropIfExists('tl_messages_chats_chats');
        Schema::dropIfExists('tl_messages_chats');
    }
};
