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
        Schema::create('tl_chat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_chat_channel', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('creator')->default(false);
            $table->boolean('left')->default(false);
            $table->boolean('broadcast')->default(false);
            $table->boolean('verified')->default(false);
            $table->boolean('megagroup')->default(false);
            $table->boolean('restricted')->default(false);
            $table->boolean('signatures')->default(false);
            $table->boolean('min')->default(false);
            $table->boolean('scam')->default(false);
            $table->boolean('has_link')->default(false);
            $table->boolean('has_geo')->default(false);
            $table->boolean('slowmode_enabled')->default(false);
            $table->boolean('call_active')->default(false);
            $table->boolean('call_not_empty')->default(false);
            $table->boolean('fake')->default(false);
            $table->boolean('gigagroup')->default(false);
            $table->boolean('noforwards')->default(false);
            $table->boolean('join_to_send')->default(false);
            $table->boolean('join_request')->default(false);
            $table->boolean('forum')->default(false);
            $table->bigInteger('flags2')->nullable();
            $table->boolean('stories_hidden')->default(false);
            $table->boolean('stories_hidden_min')->default(false);
            $table->boolean('stories_unavailable')->default(false);
            $table->boolean('signature_profiles')->default(false);
            $table->boolean('autotranslation')->default(false);
            $table->boolean('broadcast_messages_allowed')->default(false);
            $table->boolean('monoforum')->default(false);
            $table->boolean('forum_tabs')->default(false);
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash')->nullable();
            $table->text('title');
            $table->text('username')->nullable();
            $table->uuid('photo');
            $table->integer('date');
            $table->uuid('admin_rights')->nullable();
            $table->uuid('banned_rights')->nullable();
            $table->uuid('default_banned_rights')->nullable();
            $table->integer('participants_count')->nullable();
            $table->uuid('stories_max_id')->nullable();
            $table->uuid('color')->nullable();
            $table->uuid('profile_color')->nullable();
            $table->uuid('emoji_status')->nullable();
            $table->integer('level')->nullable();
            $table->integer('subscription_until_date')->nullable();
            $table->bigInteger('bot_verification_icon')->nullable();
            $table->bigInteger('send_paid_messages_stars')->nullable();
            $table->bigInteger('linked_monoforum_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_chat_channel__restriction_reason', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chat_channel')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_aa98ef05ad9699b2b8e7');
        });
        Schema::create('tl_chat_channel__usernames', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chat_channel')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_452d0ce189a06eaafe5f');
        });
        Schema::create('tl_chat_channel_forbidden', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('broadcast')->default(false);
            $table->boolean('megagroup')->default(false);
            $table->boolean('monoforum')->default(false);
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->text('title');
            $table->integer('until_date')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_chat_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('creator')->default(false);
            $table->boolean('left')->default(false);
            $table->boolean('deactivated')->default(false);
            $table->boolean('call_active')->default(false);
            $table->boolean('call_not_empty')->default(false);
            $table->boolean('noforwards')->default(false);
            $table->bigInteger('tl_id');
            $table->text('title');
            $table->uuid('photo');
            $table->integer('participants_count');
            $table->integer('date');
            $table->integer('version');
            $table->uuid('migrated_to')->nullable();
            $table->uuid('admin_rights')->nullable();
            $table->uuid('default_banned_rights')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_chat_chat_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->timestamps();
        });
        Schema::create('tl_chat_chat_forbidden', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->text('title');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_chat_forbidden');
        Schema::dropIfExists('tl_chat_chat_empty');
        Schema::dropIfExists('tl_chat_chat');
        Schema::dropIfExists('tl_chat_channel_forbidden');
        Schema::dropIfExists('tl_chat_channel__usernames');
        Schema::dropIfExists('tl_chat_channel__restriction_reason');
        Schema::dropIfExists('tl_chat_channel');
        Schema::dropIfExists('tl_chat');
    }
};
