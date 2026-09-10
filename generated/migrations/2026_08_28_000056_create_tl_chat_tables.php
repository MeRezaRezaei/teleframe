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
        Schema::create('tl_chat_channel', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
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
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->text('title')->nullable();
            $table->text('username')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_10efede4bce8aea0faf13276');
            $table->integer('date')->nullable();
            $table->bigInteger('admin_rights')->nullable();
            $table->index('admin_rights', 'ix_3d1308ee87098f1b532b64f2');
            $table->bigInteger('banned_rights')->nullable();
            $table->index('banned_rights', 'ix_a5cab5c83b0b86b18cb96bac');
            $table->bigInteger('default_banned_rights')->nullable();
            $table->index('default_banned_rights', 'ix_0fea252101f874a7fa40d9ca');
            $table->integer('participants_count')->nullable();
            $table->bigInteger('stories_max_id')->nullable();
            $table->index('stories_max_id', 'ix_5cc6c164da0ab77793190142');
            $table->bigInteger('color')->nullable();
            $table->index('color', 'ix_d76a70db4a7ba01b309c4dca');
            $table->bigInteger('profile_color')->nullable();
            $table->index('profile_color', 'ix_2871ea1150577b7af3e2fef0');
            $table->bigInteger('emoji_status')->nullable();
            $table->index('emoji_status', 'ix_ca772b021add35153249a344');
            $table->integer('level')->nullable();
            $table->integer('subscription_until_date')->nullable();
            $table->bigInteger('bot_verification_icon')->nullable();
            $table->bigInteger('send_paid_messages_stars')->nullable();
            $table->bigInteger('linked_monoforum_id')->nullable();
            $table->index('linked_monoforum_id', 'ix_34592a2555533117b85c4e86');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ee000172a7aac9841adb9d2a');
            $table->index('account_id', 'ix_51556f3422d0983b889fca37');
        });
        Schema::create('tl_chat_channel__restriction_reason', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_35de690f3c31afcfb16f660f')->references('id')->on('tl_chat_channel')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_aa98ef05ad9699b2b8e7');
            $table->index('account_id', 'ix_775c4692872f6ac373ebc77e');
        });
        Schema::create('tl_chat_channel__usernames', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_7536876c67441da486967eaf')->references('id')->on('tl_chat_channel')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_452d0ce189a06eaafe5f');
            $table->index('account_id', 'ix_c9aac6fbeddd477cef23dac1');
        });
        Schema::create('tl_chat_channel_forbidden', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('broadcast')->default(false);
            $table->boolean('megagroup')->default(false);
            $table->boolean('monoforum')->default(false);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->text('title')->nullable();
            $table->integer('until_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_68ca5e93ad13934226e37414');
            $table->index('account_id', 'ix_d2b515d16ce4b2714257faf6');
        });
        Schema::create('tl_chat_chat', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('creator')->default(false);
            $table->boolean('left')->default(false);
            $table->boolean('deactivated')->default(false);
            $table->boolean('call_active')->default(false);
            $table->boolean('call_not_empty')->default(false);
            $table->boolean('noforwards')->default(false);
            $table->bigInteger('tl_id')->nullable();
            $table->text('title')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_1ce66349df93fd253af19b24');
            $table->integer('participants_count')->nullable();
            $table->integer('date')->nullable();
            $table->integer('version')->nullable();
            $table->bigInteger('migrated_to')->nullable();
            $table->index('migrated_to', 'ix_5d91492ba2c9b77b2bf55bf6');
            $table->bigInteger('admin_rights')->nullable();
            $table->index('admin_rights', 'ix_cad50a8f9db3b00a548363bc');
            $table->bigInteger('default_banned_rights')->nullable();
            $table->index('default_banned_rights', 'ix_b7e990a757b598c1b64a391d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_418454d1b02fdfc5710a1f5b');
            $table->index('account_id', 'ix_a20319775f75cf33521285a3');
        });
        Schema::create('tl_chat_chat_empty', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_12b2bee77359496e9727d596');
            $table->index('account_id', 'ix_fb343dee47fbd3f4b5b25266');
        });
        Schema::create('tl_chat_chat_forbidden', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->text('title')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ed072938bd61ec5ca0736807');
            $table->index('account_id', 'ix_4640b7de76c7a2038a377236');
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
    }
};
