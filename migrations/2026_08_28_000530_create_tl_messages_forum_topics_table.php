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
        Schema::create('tl_messages_forum_topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_forum_topics_forum_topics', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_forum_topics')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('order_by_create_date')->default(false);
            $table->integer('count');
            $table->integer('pts');
            $table->timestamps();
        });
        Schema::create('tl_messages_forum_topics_forum_topics__topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_forum_topics_forum_topics')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_6b2d645185200386d191');
        });
        Schema::create('tl_messages_forum_topics_forum_topics__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_forum_topics_forum_topics')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_061dc5ad011c1efcec9d');
        });
        Schema::create('tl_messages_forum_topics_forum_topics__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_forum_topics_forum_topics')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_b373bdfc0ca2e9246d90');
        });
        Schema::create('tl_messages_forum_topics_forum_topics__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_forum_topics_forum_topics')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_66d1bff450ffac14b475');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_forum_topics_forum_topics__users');
        Schema::dropIfExists('tl_messages_forum_topics_forum_topics__chats');
        Schema::dropIfExists('tl_messages_forum_topics_forum_topics__messages');
        Schema::dropIfExists('tl_messages_forum_topics_forum_topics__topics');
        Schema::dropIfExists('tl_messages_forum_topics_forum_topics');
        Schema::dropIfExists('tl_messages_forum_topics');
    }
};
