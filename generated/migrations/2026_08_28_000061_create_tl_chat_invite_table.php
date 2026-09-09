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
            $table->index('constructor_id', 'ix_4e44037a81304037f9073fe0');
            $table->index('account_id', 'ix_e8a2ac2cdb1ddc8b447f371e');
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
            $table->index('photo', 'ix_721c22e251907d631118452d');
            $table->integer('participants_count');
            $table->integer('color');
            $table->uuid('subscription_pricing')->nullable();
            $table->index('subscription_pricing', 'ix_fab9781e1e4cbae5aa2aaf9d');
            $table->bigInteger('subscription_form_id')->nullable();
            $table->index('subscription_form_id', 'ix_b79ab4f9a59703c692246ec6');
            $table->uuid('bot_verification')->nullable();
            $table->index('bot_verification', 'ix_55e4dc13d2e702354e073e71');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_64c34a94a035df3c45d56a3c');
        });
        Schema::create('tl_chat_invite_chat_invite__participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chat_invite_chat_invite')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f414488cc4c652cec703');
            $table->index('account_id', 'ix_48132091c94489d42a39c274');
        });
        Schema::create('tl_chat_invite_chat_invite_already', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_invite')->cascadeOnDelete();
            $table->uuid('chat');
            $table->index('chat', 'ix_35aa50b70228584ca0e5aa12');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a44f1749ebba305e8e8cc3cb');
        });
        Schema::create('tl_chat_invite_chat_invite_peek', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_invite')->cascadeOnDelete();
            $table->uuid('chat');
            $table->index('chat', 'ix_4ce319817320897e3498081c');
            $table->integer('expires');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b2a19d92b45315b0a163bb67');
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
