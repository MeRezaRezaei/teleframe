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
        Schema::create('tl_user_full', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_user_full_user_full', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_user_full')->cascadeOnDelete();
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
            $table->bigInteger('tl_id');
            $table->text('about')->nullable();
            $table->uuid('settings');
            $table->uuid('personal_photo')->nullable();
            $table->uuid('profile_photo')->nullable();
            $table->uuid('fallback_photo')->nullable();
            $table->uuid('notify_settings');
            $table->uuid('bot_info')->nullable();
            $table->integer('pinned_msg_id')->nullable();
            $table->integer('common_chats_count');
            $table->integer('folder_id')->nullable();
            $table->integer('ttl_period')->nullable();
            $table->uuid('theme')->nullable();
            $table->text('private_forward_name')->nullable();
            $table->uuid('bot_group_admin_rights')->nullable();
            $table->uuid('bot_broadcast_admin_rights')->nullable();
            $table->uuid('wallpaper')->nullable();
            $table->uuid('stories')->nullable();
            $table->uuid('business_work_hours')->nullable();
            $table->uuid('business_location')->nullable();
            $table->uuid('business_greeting_message')->nullable();
            $table->uuid('business_away_message')->nullable();
            $table->uuid('business_intro')->nullable();
            $table->uuid('birthday')->nullable();
            $table->bigInteger('personal_channel_id')->nullable();
            $table->integer('personal_channel_message')->nullable();
            $table->integer('stargifts_count')->nullable();
            $table->uuid('starref_program')->nullable();
            $table->uuid('bot_verification')->nullable();
            $table->bigInteger('send_paid_messages_stars')->nullable();
            $table->uuid('disallowed_gifts')->nullable();
            $table->uuid('stars_rating')->nullable();
            $table->uuid('stars_my_pending_rating')->nullable();
            $table->integer('stars_my_pending_rating_date')->nullable();
            $table->uuid('main_tab')->nullable();
            $table->uuid('saved_music')->nullable();
            $table->uuid('note')->nullable();
            $table->bigInteger('bot_manager_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_user_full_user_full');
        Schema::dropIfExists('tl_user_full');
    }
};
