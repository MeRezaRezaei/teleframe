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
        Schema::create('tl_messages_votes_list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_votes_list_votes_list', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_votes_list')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('count');
            $table->text('next_offset')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_messages_votes_list_votes_list__votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_votes_list_votes_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_4a4444076db8b8124f52');
        });
        Schema::create('tl_messages_votes_list_votes_list__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_votes_list_votes_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_1d0a41f052d15c42e42e');
        });
        Schema::create('tl_messages_votes_list_votes_list__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_votes_list_votes_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_4c48215bd28d57d286fc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_votes_list_votes_list__users');
        Schema::dropIfExists('tl_messages_votes_list_votes_list__chats');
        Schema::dropIfExists('tl_messages_votes_list_votes_list__votes');
        Schema::dropIfExists('tl_messages_votes_list_votes_list');
        Schema::dropIfExists('tl_messages_votes_list');
    }
};
