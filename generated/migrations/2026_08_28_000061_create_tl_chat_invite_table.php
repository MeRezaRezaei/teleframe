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
        Schema::create('tl_chat_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_chat_invite_chat_invite', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_invite')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('channel')->default(false);
            $table->boolean('broadcast')->default(false);
            $table->boolean('public')->default(false);
            $table->boolean('megagroup')->default(false);
            $table->boolean('request_needed')->default(false);
            $table->boolean('verified')->default(false);
            $table->boolean('scam')->default(false);
            $table->boolean('fake')->default(false);
            $table->boolean('can_refulfill_subscription')->default(false);
            $table->text('title');
            $table->text('about')->nullable();
            $table->uuid('photo');
            $table->integer('participants_count');
            $table->integer('color');
            $table->uuid('subscription_pricing')->nullable();
            $table->bigInteger('subscription_form_id')->nullable();
            $table->uuid('bot_verification')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_chat_invite_chat_invite__participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chat_invite_chat_invite')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f414488cc4c652cec703');
        });
        Schema::create('tl_chat_invite_chat_invite_already', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_invite')->cascadeOnDelete();
            $table->uuid('chat');
            $table->timestamps();
        });
        Schema::create('tl_chat_invite_chat_invite_peek', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_invite')->cascadeOnDelete();
            $table->uuid('chat');
            $table->integer('expires');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_invite_chat_invite_peek');
        Schema::dropIfExists('tl_chat_invite_chat_invite_already');
        Schema::dropIfExists('tl_chat_invite_chat_invite__participants');
        Schema::dropIfExists('tl_chat_invite_chat_invite');
        Schema::dropIfExists('tl_chat_invite');
    }
};
