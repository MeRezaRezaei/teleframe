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
        Schema::create('tl_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_929adf8f3c8890cac388c8b1');
            $table->index('account_id', 'ix_cd78bea9853017e7f6ba0196');
        });
        Schema::create('tl_message_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('out')->default(false);
            $table->boolean('mentioned')->default(false);
            $table->boolean('media_unread')->default(false);
            $table->boolean('silent')->default(false);
            $table->boolean('post')->default(false);
            $table->boolean('from_scheduled')->default(false);
            $table->boolean('legacy')->default(false);
            $table->boolean('edit_hide')->default(false);
            $table->boolean('pinned')->default(false);
            $table->boolean('noforwards')->default(false);
            $table->boolean('invert_media')->default(false);
            $table->bigInteger('flags2')->nullable();
            $table->boolean('offline')->default(false);
            $table->boolean('video_processing_pending')->default(false);
            $table->boolean('paid_suggested_post_stars')->default(false);
            $table->boolean('paid_suggested_post_ton')->default(false);
            $table->integer('tl_id');
            $table->bigInteger('from_id')->nullable();
            $table->index('from_id', 'ix_d97fd253b0ba59adf1285c67');
            $table->integer('from_boosts_applied')->nullable();
            $table->text('from_rank')->nullable();
            $table->bigInteger('peer_id');
            $table->index('peer_id', 'ix_f360b825e7c500a6ba5f29f9');
            $table->bigInteger('saved_peer_id')->nullable();
            $table->index('saved_peer_id', 'ix_9d943cc56d19e1159318889e');
            $table->uuid('fwd_from')->nullable();
            $table->index('fwd_from', 'ix_c33f2ee4c38fb2e08b26e572');
            $table->bigInteger('via_bot_id')->nullable();
            $table->index('via_bot_id', 'ix_d6c84bd9da5b3096cebfad33');
            $table->bigInteger('via_business_bot_id')->nullable();
            $table->index('via_business_bot_id', 'ix_c533c994e3b0972e731c858b');
            $table->bigInteger('guestchat_via_from')->nullable();
            $table->index('guestchat_via_from', 'ix_46aae42c28a1b350888e6f64');
            $table->uuid('reply_to')->nullable();
            $table->index('reply_to', 'ix_4ec53d242c4e317915b607ff');
            $table->integer('date');
            $table->text('message');
            $table->uuid('media')->nullable();
            $table->index('media', 'ix_b6385b153c213a47bb27e754');
            $table->uuid('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_38503b573454894ba372ad9e');
            $table->integer('views')->nullable();
            $table->integer('forwards')->nullable();
            $table->uuid('replies')->nullable();
            $table->index('replies', 'ix_bea31798dd43d5d6950b5948');
            $table->integer('edit_date')->nullable();
            $table->text('post_author')->nullable();
            $table->bigInteger('grouped_id')->nullable();
            $table->index('grouped_id', 'ix_17aab07910089e046badd805');
            $table->uuid('reactions')->nullable();
            $table->index('reactions', 'ix_84d3c16f645d850d8c9328e8');
            $table->integer('ttl_period')->nullable();
            $table->integer('quick_reply_shortcut_id')->nullable();
            $table->bigInteger('effect')->nullable();
            $table->uuid('factcheck')->nullable();
            $table->index('factcheck', 'ix_20a76e689398ba1bbfc86f91');
            $table->integer('report_delivery_until_date')->nullable();
            $table->bigInteger('paid_message_stars')->nullable();
            $table->uuid('suggested_post')->nullable();
            $table->index('suggested_post', 'ix_0d84abc4aea49c09b4496c2c');
            $table->integer('schedule_repeat_period')->nullable();
            $table->text('summary_from_language')->nullable();
            $table->uuid('rich_message')->nullable();
            $table->index('rich_message', 'ix_b39d41dacb169032d892be38');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_488f3bd2a1d1897b1f6406e9');
            $table->unique(['account_id', 'guestchat_via_from', 'tl_id'], 'ux_09dd3ed3bc970fbdbb77');
        });
        Schema::create('tl_message_message__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_12c0b220c53ed6b87b94');
            $table->index('account_id', 'ix_e3e367ddc50e44f7f6bb6951');
        });
        Schema::create('tl_message_message__restriction_reason', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_02bd6d1931bd78bf6392');
            $table->index('account_id', 'ix_723ee1facbc55569e9f31eb6');
        });
        Schema::create('tl_message_message_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('tl_id');
            $table->bigInteger('peer_id')->nullable();
            $table->index('peer_id', 'ix_cffc6dd6704d59339107b775');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6278fc03b9bf1424dd3f6312');
            $table->unique(['account_id', 'peer_id', 'tl_id'], 'ux_06979b349742400c8c04');
        });
        Schema::create('tl_message_message_service', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('out')->default(false);
            $table->boolean('mentioned')->default(false);
            $table->boolean('media_unread')->default(false);
            $table->boolean('reactions_are_possible')->default(false);
            $table->boolean('silent')->default(false);
            $table->boolean('post')->default(false);
            $table->boolean('legacy')->default(false);
            $table->integer('tl_id');
            $table->bigInteger('from_id')->nullable();
            $table->index('from_id', 'ix_dca25bbfd604d995cdfccc49');
            $table->bigInteger('peer_id');
            $table->index('peer_id', 'ix_8efd14434bbfeae9a1790a2c');
            $table->bigInteger('saved_peer_id')->nullable();
            $table->index('saved_peer_id', 'ix_21c9055cb42f2b5cd31e8279');
            $table->uuid('reply_to')->nullable();
            $table->index('reply_to', 'ix_18669cc5dc136850e0a06f8d');
            $table->integer('date');
            $table->uuid('action');
            $table->index('action', 'ix_677c1fb2bfc9b532f5e5219e');
            $table->uuid('reactions')->nullable();
            $table->index('reactions', 'ix_568fa9fb968e339f119f11bc');
            $table->integer('ttl_period')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_799db91208004665ad2a2f8b');
            $table->unique(['account_id', 'saved_peer_id', 'tl_id'], 'ux_1d255784b4a3ef9c7f4c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_message_service');
        Schema::dropIfExists('tl_message_message_empty');
        Schema::dropIfExists('tl_message_message__restriction_reason');
        Schema::dropIfExists('tl_message_message__entities');
        Schema::dropIfExists('tl_message_message');
        Schema::dropIfExists('tl_message');
    }
};
