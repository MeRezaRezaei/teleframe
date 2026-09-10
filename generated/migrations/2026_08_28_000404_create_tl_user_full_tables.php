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
        Schema::create('tl_user_full_user_full', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('blocked')->default(false);
            $table->boolean('phone_calls_available')->default(false);
            $table->boolean('phone_calls_private')->default(false);
            $table->boolean('can_pin_message')->default(false);
            $table->boolean('has_scheduled')->default(false);
            $table->boolean('video_calls_available')->default(false);
            $table->boolean('voice_messages_forbidden')->default(false);
            $table->boolean('translations_disabled')->default(false);
            $table->boolean('stories_pinned_available')->default(false);
            $table->boolean('blocked_my_stories_from')->default(false);
            $table->boolean('wallpaper_overridden')->default(false);
            $table->boolean('contact_require_premium')->default(false);
            $table->boolean('read_dates_private')->default(false);
            $table->bigInteger('flags2')->nullable();
            $table->boolean('sponsored_enabled')->default(false);
            $table->boolean('can_view_revenue')->default(false);
            $table->boolean('bot_can_manage_emoji_status')->default(false);
            $table->boolean('display_gifts_button')->default(false);
            $table->boolean('noforwards_my_enabled')->default(false);
            $table->boolean('noforwards_peer_enabled')->default(false);
            $table->boolean('unofficial_security_risk')->default(false);
            $table->bigInteger('tl_id')->nullable();
            $table->text('about')->nullable();
            $table->bigInteger('settings')->nullable();
            $table->index('settings', 'ix_f93217f51a6cbedff6cb88a6');
            $table->bigInteger('personal_photo')->nullable();
            $table->index('personal_photo', 'ix_6dfb8991a50fd382730469c3');
            $table->bigInteger('profile_photo')->nullable();
            $table->index('profile_photo', 'ix_27bfaa613fd04058a7980136');
            $table->bigInteger('fallback_photo')->nullable();
            $table->index('fallback_photo', 'ix_490d8808f8cfefdc841676d1');
            $table->bigInteger('notify_settings')->nullable();
            $table->index('notify_settings', 'ix_7c260b54755bbdaf23386eff');
            $table->bigInteger('bot_info')->nullable();
            $table->index('bot_info', 'ix_1d1dcac4fee119a7d7bac889');
            $table->integer('pinned_msg_id')->nullable();
            $table->integer('common_chats_count')->nullable();
            $table->integer('folder_id')->nullable();
            $table->integer('ttl_period')->nullable();
            $table->bigInteger('theme')->nullable();
            $table->index('theme', 'ix_c37557c3dbd635f873816161');
            $table->text('private_forward_name')->nullable();
            $table->bigInteger('bot_group_admin_rights')->nullable();
            $table->index('bot_group_admin_rights', 'ix_7d75ffc7dc9edd9f4b786a7b');
            $table->bigInteger('bot_broadcast_admin_rights')->nullable();
            $table->index('bot_broadcast_admin_rights', 'ix_95bbc7d549fdc65068852c19');
            $table->bigInteger('wallpaper')->nullable();
            $table->index('wallpaper', 'ix_8539a68e056d73e892ffb345');
            $table->bigInteger('stories')->nullable();
            $table->index('stories', 'ix_cd9982c19aa7ad1306afba94');
            $table->bigInteger('business_work_hours')->nullable();
            $table->index('business_work_hours', 'ix_f07b7125c4507e792d440869');
            $table->bigInteger('business_location')->nullable();
            $table->index('business_location', 'ix_bc79bf6912408757b94eaf67');
            $table->bigInteger('business_greeting_message')->nullable();
            $table->index('business_greeting_message', 'ix_064d2e375b8cccc23e9dc18e');
            $table->bigInteger('business_away_message')->nullable();
            $table->index('business_away_message', 'ix_489abc79a87210511973bf48');
            $table->bigInteger('business_intro')->nullable();
            $table->index('business_intro', 'ix_1b2fa0ba9e744400b639a87e');
            $table->bigInteger('birthday')->nullable();
            $table->index('birthday', 'ix_03149cc838d00ca3e875bbd4');
            $table->bigInteger('personal_channel_id')->nullable();
            $table->index('personal_channel_id', 'ix_19db77602fef21f456152de1');
            $table->integer('personal_channel_message')->nullable();
            $table->integer('stargifts_count')->nullable();
            $table->bigInteger('starref_program')->nullable();
            $table->index('starref_program', 'ix_9b62cd7cb07d1dea5219ad19');
            $table->bigInteger('bot_verification')->nullable();
            $table->index('bot_verification', 'ix_adaa029e4bbd89114f20daa3');
            $table->bigInteger('send_paid_messages_stars')->nullable();
            $table->bigInteger('disallowed_gifts')->nullable();
            $table->index('disallowed_gifts', 'ix_bdc076b47b0b444aca01f061');
            $table->bigInteger('stars_rating')->nullable();
            $table->index('stars_rating', 'ix_74c70bab68190e3827f49b52');
            $table->bigInteger('stars_my_pending_rating')->nullable();
            $table->index('stars_my_pending_rating', 'ix_bbcf8175bed9cfe0784b37f7');
            $table->integer('stars_my_pending_rating_date')->nullable();
            $table->bigInteger('main_tab')->nullable();
            $table->index('main_tab', 'ix_480adc6aa4c12db44249efca');
            $table->bigInteger('saved_music')->nullable();
            $table->index('saved_music', 'ix_4d3a57137d9c5f3079c85311');
            $table->bigInteger('note')->nullable();
            $table->index('note', 'ix_58ad3180bada4c8038ba4500');
            $table->bigInteger('bot_manager_id')->nullable();
            $table->index('bot_manager_id', 'ix_87c1796a58c9a2ae376a7242');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_653f4a9b3d4b89ab91ed4445');
            $table->index('account_id', 'ix_2580e8c2d6fd5f096d360cc3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_user_full_user_full');
    }
};
