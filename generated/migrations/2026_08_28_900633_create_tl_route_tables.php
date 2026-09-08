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
        Schema::create('tl_route_account_accept_authorization', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_cancel_password_email', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_change_authorization_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_change_phone', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_check_username', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_clear_recent_emoji_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_confirm_bot_connection', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_confirm_password_email', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_confirm_phone', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_create_business_chat_link', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_create_theme', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_decline_password_reset', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_delete_account', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_delete_auto_save_exceptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_delete_business_chat_link', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_delete_passkey', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_delete_secure_value', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_delete_web_browser_settings_exceptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_disable_peer_connected_bot', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_edit_business_chat_link', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_finish_takeout_session', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_account_t_t_l', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_authorization_form', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_authorizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_auto_download_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_auto_save_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_bot_business_connection', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_business_chat_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_channel_default_emoji_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_channel_restricted_status_emojis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_chat_themes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_collectible_emoji_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_connected_bots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_contact_sign_up_notification', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_content_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_default_background_emojis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_default_emoji_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_default_group_photo_emojis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_default_profile_photo_emojis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_global_privacy_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_notify_exceptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_notify_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_paid_messages_revenue', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_passkeys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_password', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_password_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_privacy', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_reactions_notify_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_recent_emoji_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_saved_music_ids', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_saved_ringtones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_theme', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_themes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_tmp_password', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_unique_gift_chat_themes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_wall_paper', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_wall_papers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_web_authorizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_get_web_browser_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_init_passkey_registration', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_init_takeout_session', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_install_theme', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_install_wall_paper', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_invalidate_sign_in_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_register_device', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_register_passkey', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_reorder_usernames', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_report_peer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_report_profile_photo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_resend_password_email', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_reset_authorization', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_reset_notify_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_reset_password', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_reset_wall_papers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_reset_web_authorization', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_reset_web_authorizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_resolve_business_chat_link', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_save_auto_download_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_save_auto_save_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_save_music', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_save_ringtone', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_save_secure_value', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_save_theme', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_save_wall_paper', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_send_change_phone_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_send_confirm_phone_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_send_verify_email_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_send_verify_phone_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_set_account_t_t_l', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_set_authorization_t_t_l', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_set_contact_sign_up_notification', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_set_content_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_set_global_privacy_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_set_main_profile_tab', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_set_privacy', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_set_reactions_notify_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_toggle_connected_bot_paused', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_toggle_no_paid_messages_exception', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_toggle_sponsored_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_toggle_username', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_toggle_web_browser_settings_exception', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_unregister_device', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_birthday', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_business_away_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_business_greeting_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_business_intro', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_business_location', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_business_work_hours', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_color', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_connected_bot', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_device_locked', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_emoji_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_notify_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_password_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_personal_channel', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_profile', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_theme', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_username', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_update_web_browser_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_upload_ringtone', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_upload_theme', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_upload_wall_paper', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_verify_email', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_account_verify_phone', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_aicompose_create_tone', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_aicompose_delete_tone', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_aicompose_get_tone', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_aicompose_get_tone_example', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_aicompose_get_tones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_aicompose_save_tone', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_aicompose_update_tone', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_accept_login_token', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_bind_temp_auth_key', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_cancel_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_check_paid_auth', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_check_password', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_check_recovery_password', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_drop_temp_auth_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_export_authorization', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_export_login_token', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_finish_passkey_login', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_import_authorization', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_import_bot_authorization', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_import_login_token', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_import_web_token_authorization', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_init_passkey_login', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_log_out', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_recover_password', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_report_missing_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_request_firebase_sms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_request_password_recovery', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_resend_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_reset_authorizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_reset_login_email', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_send_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_sign_in', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_auth_sign_up', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_add_preview_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_allow_send_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_answer_webhook_j_s_o_n_query', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_can_send_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_check_download_file_params', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_check_username', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_create_bot', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_delete_preview_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_edit_access_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_edit_preview_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_export_bot_token', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_get_access_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_get_bot_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_get_bot_menu_button', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_get_bot_recommendations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_get_popular_app_bots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_get_preview_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_get_requested_web_view_button', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_invoke_web_view_custom_method', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_reorder_preview_medias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_reorder_usernames', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_request_web_view_button', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_reset_bot_commands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_send_custom_request', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_set_bot_broadcast_default_admin_rights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_set_bot_commands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_set_bot_group_default_admin_rights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_set_bot_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_set_bot_menu_button', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_set_custom_verification', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_set_join_chat_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_toggle_user_emoji_status_permission', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_toggle_username', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_update_star_ref_program', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_bots_update_user_emoji_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_check_search_posts_flood', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_check_username', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_convert_to_gigagroup', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_create_channel', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_deactivate_all_usernames', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_delete_channel', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_delete_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_delete_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_delete_participant_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_edit_admin', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_edit_banned', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_edit_location', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_edit_photo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_edit_title', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_export_message_link', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_get_admin_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_get_admined_public_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_get_channel_recommendations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_get_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_get_full_channel', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_get_groups_for_discussion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_get_inactive_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_get_left_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_get_message_author', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_get_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_get_participant', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_get_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_get_send_as', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_invite_to_channel', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_join_channel', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_leave_channel', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_read_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_read_message_contents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_reorder_usernames', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_report_anti_spam_false_positive', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_report_spam', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_restrict_sponsored_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_search_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_set_boosts_to_unblock_restrictions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_set_discussion_group', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_set_emoji_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_set_main_profile_tab', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_set_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_toggle_anti_spam', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_toggle_autotranslation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_toggle_forum', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_toggle_join_request', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_toggle_join_to_send', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_toggle_participants_hidden', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_toggle_pre_history_hidden', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_toggle_signatures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_toggle_slow_mode', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_toggle_username', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_toggle_view_forum_as_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_update_color', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_update_emoji_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_update_paid_messages_price', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_channels_update_username', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_chatlists_check_chatlist_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_chatlists_delete_exported_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_chatlists_edit_exported_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_chatlists_export_chatlist_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_chatlists_get_chatlist_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_chatlists_get_exported_invites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_chatlists_hide_chatlist_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_chatlists_join_chatlist_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_chatlists_join_chatlist_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_chatlists_leave_chatlist', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_accept_contact', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_add_contact', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_block', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_block_from_replies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_delete_by_phones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_delete_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_edit_close_friends', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_export_contact_token', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_get_birthdays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_get_blocked', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_get_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_get_located', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_get_sponsored_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_get_top_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_import_contact_token', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_import_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_reset_saved', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_reset_top_peer_rating', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_resolve_phone', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_resolve_username', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_search', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_set_blocked', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_toggle_top_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_unblock', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_contacts_update_contact_note', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_destroy_session', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_folders_edit_peer_folders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_fragment_get_collectible_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_get_future_salts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_accept_terms_of_service', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_dismiss_suggestion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_edit_user_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_app_config', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_app_update', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_cdn_config', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_config', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_countries_list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_deep_link_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_invite_text', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_nearest_dc', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_passport_config', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_peer_colors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_peer_profile_colors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_premium_promo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_promo_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_recent_me_urls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_support', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_support_name', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_terms_of_service_update', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_timezones_list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_get_user_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_hide_promo_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_save_app_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_help_set_bot_updates_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_langpack_get_difference', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_langpack_get_lang_pack', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_langpack_get_language', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_accept_encryption', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_accept_url_auth', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_add_chat_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_add_poll_answer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_append_todo_list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_check_chat_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_check_history_import', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_check_history_import_peer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_check_quick_reply_shortcut', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_check_url_auth_match_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_clear_all_drafts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_clear_recent_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_clear_recent_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_click_sponsored_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_compose_message_with_a_i', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_create_chat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_create_forum_topic', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_decline_url_auth', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_delete_chat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_delete_chat_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_delete_exported_chat_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_delete_fact_check', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_delete_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_delete_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_delete_participant_reaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_delete_participant_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_delete_phone_call_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_delete_poll_answer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_delete_quick_reply_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_delete_quick_reply_shortcut', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_delete_revoked_exported_chat_invites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_delete_saved_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_delete_scheduled_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_delete_topic_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_discard_encryption', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_edit_chat_about', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_edit_chat_admin', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_edit_chat_creator', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_edit_chat_default_banned_rights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_edit_chat_participant_rank', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_edit_chat_photo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_edit_chat_title', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_edit_exported_chat_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_edit_fact_check', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_edit_forum_topic', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_edit_inline_bot_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_edit_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_edit_quick_reply_shortcut', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_export_chat_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_fave_sticker', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_forward_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_admins_with_invites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_all_drafts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_all_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_archived_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_attach_menu_bot', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_attach_menu_bots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_available_effects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_available_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_bot_app', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_bot_callback_answer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_chat_invite_importers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_common_chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_default_history_t_t_l', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_default_tag_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_dh_config', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_dialog_filters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_discussion_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_document_by_hash', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_emoji_game_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_emoji_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_emoji_keywords', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_emoji_keywords_difference', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_emoji_profile_photo_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_emoji_status_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_emoji_sticker_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_emoji_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_emoji_u_r_l', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_exported_chat_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_exported_chat_invites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_extended_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_faved_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_featured_emoji_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_featured_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_forum_topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_forum_topics_by_i_d', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_full_chat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_future_chat_creator_after_leave', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_game_high_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_inline_bot_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_inline_game_high_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_mask_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_message_edit_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_message_reactions_list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_messages_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_messages_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_my_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_old_featured_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_onlines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_outbox_read_date', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_paid_reaction_privacy', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_peer_dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_peer_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_personal_channel_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_pinned_dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_pinned_saved_dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_poll_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_poll_votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_prepared_inline_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_quick_replies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_quick_reply_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_recent_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_recent_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_recent_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_replies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_rich_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_saved_dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_saved_dialogs_by_i_d', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_saved_gifs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_saved_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_saved_reaction_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_scheduled_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_scheduled_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_search_results_calendar', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_search_results_positions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_sponsored_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_sticker_set', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_top_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_unread_mentions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_unread_poll_votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_unread_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_web_page', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_get_web_page_preview', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_hide_all_chat_join_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_hide_chat_join_request', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_hide_peer_settings_bar', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_import_chat_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_init_history_import', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_install_sticker_set', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_mark_dialog_unread', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_migrate_chat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_prolong_web_view', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_rate_transcribed_audio', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_read_discussion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_read_encrypted_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_read_featured_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_read_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_read_mentions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_read_message_contents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_read_poll_votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_read_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_read_saved_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_reorder_pinned_dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_reorder_pinned_forum_topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_reorder_pinned_saved_dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_reorder_quick_replies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_reorder_sticker_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_report', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_report_encrypted_spam', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_report_messages_delivery', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_report_music_listen', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_report_reaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_report_read_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_report_spam', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_report_sponsored_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_request_app_web_view', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_request_encryption', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_request_main_web_view', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_request_simple_web_view', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_request_url_auth', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_request_web_view', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_save_default_send_as', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_save_draft', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_save_gif', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_save_prepared_inline_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_save_recent_sticker', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_search', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_search_custom_emoji', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_search_emoji_sticker_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_search_global', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_search_sent_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_search_sticker_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_search_stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_send_bot_requested_peer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_send_encrypted', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_send_encrypted_file', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_send_encrypted_service', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_send_inline_bot_result', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_send_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_send_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_send_multi_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_send_paid_reaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_send_quick_reply_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_send_reaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_send_scheduled_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_send_screenshot_notification', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_send_vote', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_send_web_view_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_send_web_view_result_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_set_bot_callback_answer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_set_bot_guest_chat_result', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_set_bot_precheckout_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_set_bot_shipping_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_set_chat_available_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_set_chat_theme', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_set_chat_wall_paper', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_set_default_history_t_t_l', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_set_default_reaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_set_encrypted_typing', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_set_game_score', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_set_history_t_t_l', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_set_inline_bot_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_set_inline_game_score', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_set_typing', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_start_bot', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_start_history_import', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_summarize_text', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_toggle_bot_in_attach_menu', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_toggle_dialog_filter_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_toggle_dialog_pin', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_toggle_no_forwards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_toggle_paid_reaction_privacy', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_toggle_peer_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_toggle_saved_dialog_pin', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_toggle_sticker_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_toggle_suggested_post_approval', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_toggle_todo_completed', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_transcribe_audio', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_translate_text', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_uninstall_sticker_set', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_unpin_all_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_update_dialog_filter', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_update_dialog_filters_order', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_update_pinned_forum_topic', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_update_pinned_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_update_saved_reaction_tag', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_upload_encrypted_file', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_upload_imported_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_upload_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_messages_view_sponsored_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_apply_gift_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_assign_app_store_transaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_assign_play_market_transaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_bot_cancel_stars_subscription', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_can_purchase_store', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_change_stars_subscription', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_check_can_send_gift', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_check_gift_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_clear_saved_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_connect_star_ref_bot', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_convert_star_gift', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_craft_star_gift', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_create_star_gift_collection', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_delete_star_gift_collection', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_edit_connected_star_ref_bot', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_export_invoice', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_fulfill_stars_subscription', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_bank_card_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_connected_star_ref_bot', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_connected_star_ref_bots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_craft_star_gifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_giveaway_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_payment_form', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_payment_receipt', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_resale_star_gifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_saved_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_saved_star_gift', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_saved_star_gifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_star_gift_active_auctions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_star_gift_auction_acquired_gifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_star_gift_auction_state', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_star_gift_collections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_star_gift_upgrade_attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_star_gift_upgrade_preview', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_star_gift_withdrawal_url', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_star_gifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_stars_revenue_ads_account_url', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_stars_revenue_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_stars_revenue_withdrawal_url', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_stars_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_stars_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_stars_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_stars_transactions_by_i_d', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_suggested_star_ref_bots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_unique_star_gift', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_get_unique_star_gift_value_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_launch_prepaid_giveaway', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_refund_stars_charge', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_reorder_star_gift_collections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_resolve_star_gift_offer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_save_star_gift', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_send_payment_form', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_send_star_gift_offer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_send_stars_form', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_toggle_chat_star_gift_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_toggle_star_gifts_pinned_to_top', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_transfer_star_gift', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_update_star_gift_collection', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_update_star_gift_price', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_upgrade_star_gift', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_payments_validate_requested_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_accept_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_confirm_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_create_conference_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_create_group_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_decline_conference_call_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_delete_conference_call_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_delete_group_call_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_delete_group_call_participant_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_discard_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_discard_group_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_edit_group_call_participant', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_edit_group_call_title', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_export_group_call_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_get_call_config', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_get_group_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_get_group_call_chain_blocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_get_group_call_join_as', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_get_group_call_stars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_get_group_call_stream_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_get_group_call_stream_rtmp_url', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_get_group_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_invite_conference_call_participant', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_invite_to_group_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_join_group_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_join_group_call_presentation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_leave_group_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_leave_group_call_presentation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_received_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_request_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_save_call_debug', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_save_call_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_save_default_group_call_join_as', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_save_default_send_as', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_send_conference_call_broadcast', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_send_group_call_encrypted_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_send_group_call_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_send_signaling_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_set_call_rating', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_start_scheduled_group_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_toggle_group_call_record', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_toggle_group_call_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_phone_toggle_group_call_start_subscription', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_photos_get_user_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_photos_update_profile_photo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_photos_upload_contact_profile_photo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_photos_upload_profile_photo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_ping', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_ping_delay_disconnect', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_premium_apply_boost', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_premium_get_boosts_list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_premium_get_boosts_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_premium_get_my_boosts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_premium_get_user_boosts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_req__d_h_params', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_req_pq', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_req_pq_multi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_rpc_drop_answer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_set_client__d_h_params', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_smsjobs_finish_job', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_smsjobs_get_sms_job', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_smsjobs_get_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_smsjobs_is_eligible_to_join', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_smsjobs_join', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_smsjobs_leave', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_smsjobs_update_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stats_get_broadcast_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stats_get_megagroup_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stats_get_message_public_forwards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stats_get_message_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stats_get_poll_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stats_get_story_public_forwards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stats_get_story_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stats_load_async_graph', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stickers_add_sticker_to_set', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stickers_change_sticker', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stickers_change_sticker_position', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stickers_check_short_name', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stickers_create_sticker_set', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stickers_delete_sticker_set', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stickers_remove_sticker_from_set', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stickers_rename_sticker_set', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stickers_replace_sticker', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stickers_set_sticker_set_thumb', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stickers_suggest_short_name', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_activate_stealth_mode', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_can_send_story', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_create_album', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_delete_album', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_edit_story', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_export_story_link', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_get_album_stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_get_albums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_get_all_read_peer_stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_get_all_stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_get_chats_to_send', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_get_peer_stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_get_pinned_stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_get_stories_archive', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_get_stories_by_i_d', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_get_stories_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_get_story_reactions_list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_get_story_views_list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_increment_story_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_reorder_albums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_report', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_search_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_send_reaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_send_story', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_start_live', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_toggle_all_stories_hidden', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_toggle_peer_stories_hidden', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_toggle_pinned_to_top', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_stories_update_album', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_updates_get_channel_difference', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_updates_get_difference', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_updates_get_state', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_upload_get_cdn_file', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_upload_get_file', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_upload_get_web_file', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_upload_save_big_file_part', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_upload_save_file_part', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_users_get_full_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_users_get_saved_music', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_users_get_saved_music_by_i_d', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_users_set_secure_value_errors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
        Schema::create('tl_route_users_suggest_birthday', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_route_users_suggest_birthday');
        Schema::dropIfExists('tl_route_users_set_secure_value_errors');
        Schema::dropIfExists('tl_route_users_get_saved_music_by_i_d');
        Schema::dropIfExists('tl_route_users_get_saved_music');
        Schema::dropIfExists('tl_route_users_get_full_user');
        Schema::dropIfExists('tl_route_upload_save_file_part');
        Schema::dropIfExists('tl_route_upload_save_big_file_part');
        Schema::dropIfExists('tl_route_upload_get_web_file');
        Schema::dropIfExists('tl_route_upload_get_file');
        Schema::dropIfExists('tl_route_upload_get_cdn_file');
        Schema::dropIfExists('tl_route_updates_get_state');
        Schema::dropIfExists('tl_route_updates_get_difference');
        Schema::dropIfExists('tl_route_updates_get_channel_difference');
        Schema::dropIfExists('tl_route_stories_update_album');
        Schema::dropIfExists('tl_route_stories_toggle_pinned_to_top');
        Schema::dropIfExists('tl_route_stories_toggle_peer_stories_hidden');
        Schema::dropIfExists('tl_route_stories_toggle_all_stories_hidden');
        Schema::dropIfExists('tl_route_stories_start_live');
        Schema::dropIfExists('tl_route_stories_send_story');
        Schema::dropIfExists('tl_route_stories_send_reaction');
        Schema::dropIfExists('tl_route_stories_search_posts');
        Schema::dropIfExists('tl_route_stories_report');
        Schema::dropIfExists('tl_route_stories_reorder_albums');
        Schema::dropIfExists('tl_route_stories_increment_story_views');
        Schema::dropIfExists('tl_route_stories_get_story_views_list');
        Schema::dropIfExists('tl_route_stories_get_story_reactions_list');
        Schema::dropIfExists('tl_route_stories_get_stories_views');
        Schema::dropIfExists('tl_route_stories_get_stories_by_i_d');
        Schema::dropIfExists('tl_route_stories_get_stories_archive');
        Schema::dropIfExists('tl_route_stories_get_pinned_stories');
        Schema::dropIfExists('tl_route_stories_get_peer_stories');
        Schema::dropIfExists('tl_route_stories_get_chats_to_send');
        Schema::dropIfExists('tl_route_stories_get_all_stories');
        Schema::dropIfExists('tl_route_stories_get_all_read_peer_stories');
        Schema::dropIfExists('tl_route_stories_get_albums');
        Schema::dropIfExists('tl_route_stories_get_album_stories');
        Schema::dropIfExists('tl_route_stories_export_story_link');
        Schema::dropIfExists('tl_route_stories_edit_story');
        Schema::dropIfExists('tl_route_stories_delete_album');
        Schema::dropIfExists('tl_route_stories_create_album');
        Schema::dropIfExists('tl_route_stories_can_send_story');
        Schema::dropIfExists('tl_route_stories_activate_stealth_mode');
        Schema::dropIfExists('tl_route_stickers_suggest_short_name');
        Schema::dropIfExists('tl_route_stickers_set_sticker_set_thumb');
        Schema::dropIfExists('tl_route_stickers_replace_sticker');
        Schema::dropIfExists('tl_route_stickers_rename_sticker_set');
        Schema::dropIfExists('tl_route_stickers_remove_sticker_from_set');
        Schema::dropIfExists('tl_route_stickers_delete_sticker_set');
        Schema::dropIfExists('tl_route_stickers_create_sticker_set');
        Schema::dropIfExists('tl_route_stickers_check_short_name');
        Schema::dropIfExists('tl_route_stickers_change_sticker_position');
        Schema::dropIfExists('tl_route_stickers_change_sticker');
        Schema::dropIfExists('tl_route_stickers_add_sticker_to_set');
        Schema::dropIfExists('tl_route_stats_load_async_graph');
        Schema::dropIfExists('tl_route_stats_get_story_stats');
        Schema::dropIfExists('tl_route_stats_get_story_public_forwards');
        Schema::dropIfExists('tl_route_stats_get_poll_stats');
        Schema::dropIfExists('tl_route_stats_get_message_stats');
        Schema::dropIfExists('tl_route_stats_get_message_public_forwards');
        Schema::dropIfExists('tl_route_stats_get_megagroup_stats');
        Schema::dropIfExists('tl_route_stats_get_broadcast_stats');
        Schema::dropIfExists('tl_route_smsjobs_update_settings');
        Schema::dropIfExists('tl_route_smsjobs_leave');
        Schema::dropIfExists('tl_route_smsjobs_join');
        Schema::dropIfExists('tl_route_smsjobs_is_eligible_to_join');
        Schema::dropIfExists('tl_route_smsjobs_get_status');
        Schema::dropIfExists('tl_route_smsjobs_get_sms_job');
        Schema::dropIfExists('tl_route_smsjobs_finish_job');
        Schema::dropIfExists('tl_route_set_client__d_h_params');
        Schema::dropIfExists('tl_route_rpc_drop_answer');
        Schema::dropIfExists('tl_route_req_pq_multi');
        Schema::dropIfExists('tl_route_req_pq');
        Schema::dropIfExists('tl_route_req__d_h_params');
        Schema::dropIfExists('tl_route_premium_get_user_boosts');
        Schema::dropIfExists('tl_route_premium_get_my_boosts');
        Schema::dropIfExists('tl_route_premium_get_boosts_status');
        Schema::dropIfExists('tl_route_premium_get_boosts_list');
        Schema::dropIfExists('tl_route_premium_apply_boost');
        Schema::dropIfExists('tl_route_ping_delay_disconnect');
        Schema::dropIfExists('tl_route_ping');
        Schema::dropIfExists('tl_route_photos_upload_profile_photo');
        Schema::dropIfExists('tl_route_photos_upload_contact_profile_photo');
        Schema::dropIfExists('tl_route_photos_update_profile_photo');
        Schema::dropIfExists('tl_route_photos_get_user_photos');
        Schema::dropIfExists('tl_route_phone_toggle_group_call_start_subscription');
        Schema::dropIfExists('tl_route_phone_toggle_group_call_settings');
        Schema::dropIfExists('tl_route_phone_toggle_group_call_record');
        Schema::dropIfExists('tl_route_phone_start_scheduled_group_call');
        Schema::dropIfExists('tl_route_phone_set_call_rating');
        Schema::dropIfExists('tl_route_phone_send_signaling_data');
        Schema::dropIfExists('tl_route_phone_send_group_call_message');
        Schema::dropIfExists('tl_route_phone_send_group_call_encrypted_message');
        Schema::dropIfExists('tl_route_phone_send_conference_call_broadcast');
        Schema::dropIfExists('tl_route_phone_save_default_send_as');
        Schema::dropIfExists('tl_route_phone_save_default_group_call_join_as');
        Schema::dropIfExists('tl_route_phone_save_call_log');
        Schema::dropIfExists('tl_route_phone_save_call_debug');
        Schema::dropIfExists('tl_route_phone_request_call');
        Schema::dropIfExists('tl_route_phone_received_call');
        Schema::dropIfExists('tl_route_phone_leave_group_call_presentation');
        Schema::dropIfExists('tl_route_phone_leave_group_call');
        Schema::dropIfExists('tl_route_phone_join_group_call_presentation');
        Schema::dropIfExists('tl_route_phone_join_group_call');
        Schema::dropIfExists('tl_route_phone_invite_to_group_call');
        Schema::dropIfExists('tl_route_phone_invite_conference_call_participant');
        Schema::dropIfExists('tl_route_phone_get_group_participants');
        Schema::dropIfExists('tl_route_phone_get_group_call_stream_rtmp_url');
        Schema::dropIfExists('tl_route_phone_get_group_call_stream_channels');
        Schema::dropIfExists('tl_route_phone_get_group_call_stars');
        Schema::dropIfExists('tl_route_phone_get_group_call_join_as');
        Schema::dropIfExists('tl_route_phone_get_group_call_chain_blocks');
        Schema::dropIfExists('tl_route_phone_get_group_call');
        Schema::dropIfExists('tl_route_phone_get_call_config');
        Schema::dropIfExists('tl_route_phone_export_group_call_invite');
        Schema::dropIfExists('tl_route_phone_edit_group_call_title');
        Schema::dropIfExists('tl_route_phone_edit_group_call_participant');
        Schema::dropIfExists('tl_route_phone_discard_group_call');
        Schema::dropIfExists('tl_route_phone_discard_call');
        Schema::dropIfExists('tl_route_phone_delete_group_call_participant_messages');
        Schema::dropIfExists('tl_route_phone_delete_group_call_messages');
        Schema::dropIfExists('tl_route_phone_delete_conference_call_participants');
        Schema::dropIfExists('tl_route_phone_decline_conference_call_invite');
        Schema::dropIfExists('tl_route_phone_create_group_call');
        Schema::dropIfExists('tl_route_phone_create_conference_call');
        Schema::dropIfExists('tl_route_phone_confirm_call');
        Schema::dropIfExists('tl_route_phone_accept_call');
        Schema::dropIfExists('tl_route_payments_validate_requested_info');
        Schema::dropIfExists('tl_route_payments_upgrade_star_gift');
        Schema::dropIfExists('tl_route_payments_update_star_gift_price');
        Schema::dropIfExists('tl_route_payments_update_star_gift_collection');
        Schema::dropIfExists('tl_route_payments_transfer_star_gift');
        Schema::dropIfExists('tl_route_payments_toggle_star_gifts_pinned_to_top');
        Schema::dropIfExists('tl_route_payments_toggle_chat_star_gift_notifications');
        Schema::dropIfExists('tl_route_payments_send_stars_form');
        Schema::dropIfExists('tl_route_payments_send_star_gift_offer');
        Schema::dropIfExists('tl_route_payments_send_payment_form');
        Schema::dropIfExists('tl_route_payments_save_star_gift');
        Schema::dropIfExists('tl_route_payments_resolve_star_gift_offer');
        Schema::dropIfExists('tl_route_payments_reorder_star_gift_collections');
        Schema::dropIfExists('tl_route_payments_refund_stars_charge');
        Schema::dropIfExists('tl_route_payments_launch_prepaid_giveaway');
        Schema::dropIfExists('tl_route_payments_get_unique_star_gift_value_info');
        Schema::dropIfExists('tl_route_payments_get_unique_star_gift');
        Schema::dropIfExists('tl_route_payments_get_suggested_star_ref_bots');
        Schema::dropIfExists('tl_route_payments_get_stars_transactions_by_i_d');
        Schema::dropIfExists('tl_route_payments_get_stars_transactions');
        Schema::dropIfExists('tl_route_payments_get_stars_subscriptions');
        Schema::dropIfExists('tl_route_payments_get_stars_status');
        Schema::dropIfExists('tl_route_payments_get_stars_revenue_withdrawal_url');
        Schema::dropIfExists('tl_route_payments_get_stars_revenue_stats');
        Schema::dropIfExists('tl_route_payments_get_stars_revenue_ads_account_url');
        Schema::dropIfExists('tl_route_payments_get_star_gifts');
        Schema::dropIfExists('tl_route_payments_get_star_gift_withdrawal_url');
        Schema::dropIfExists('tl_route_payments_get_star_gift_upgrade_preview');
        Schema::dropIfExists('tl_route_payments_get_star_gift_upgrade_attributes');
        Schema::dropIfExists('tl_route_payments_get_star_gift_collections');
        Schema::dropIfExists('tl_route_payments_get_star_gift_auction_state');
        Schema::dropIfExists('tl_route_payments_get_star_gift_auction_acquired_gifts');
        Schema::dropIfExists('tl_route_payments_get_star_gift_active_auctions');
        Schema::dropIfExists('tl_route_payments_get_saved_star_gifts');
        Schema::dropIfExists('tl_route_payments_get_saved_star_gift');
        Schema::dropIfExists('tl_route_payments_get_saved_info');
        Schema::dropIfExists('tl_route_payments_get_resale_star_gifts');
        Schema::dropIfExists('tl_route_payments_get_payment_receipt');
        Schema::dropIfExists('tl_route_payments_get_payment_form');
        Schema::dropIfExists('tl_route_payments_get_giveaway_info');
        Schema::dropIfExists('tl_route_payments_get_craft_star_gifts');
        Schema::dropIfExists('tl_route_payments_get_connected_star_ref_bots');
        Schema::dropIfExists('tl_route_payments_get_connected_star_ref_bot');
        Schema::dropIfExists('tl_route_payments_get_bank_card_data');
        Schema::dropIfExists('tl_route_payments_fulfill_stars_subscription');
        Schema::dropIfExists('tl_route_payments_export_invoice');
        Schema::dropIfExists('tl_route_payments_edit_connected_star_ref_bot');
        Schema::dropIfExists('tl_route_payments_delete_star_gift_collection');
        Schema::dropIfExists('tl_route_payments_create_star_gift_collection');
        Schema::dropIfExists('tl_route_payments_craft_star_gift');
        Schema::dropIfExists('tl_route_payments_convert_star_gift');
        Schema::dropIfExists('tl_route_payments_connect_star_ref_bot');
        Schema::dropIfExists('tl_route_payments_clear_saved_info');
        Schema::dropIfExists('tl_route_payments_check_gift_code');
        Schema::dropIfExists('tl_route_payments_check_can_send_gift');
        Schema::dropIfExists('tl_route_payments_change_stars_subscription');
        Schema::dropIfExists('tl_route_payments_can_purchase_store');
        Schema::dropIfExists('tl_route_payments_bot_cancel_stars_subscription');
        Schema::dropIfExists('tl_route_payments_assign_play_market_transaction');
        Schema::dropIfExists('tl_route_payments_assign_app_store_transaction');
        Schema::dropIfExists('tl_route_payments_apply_gift_code');
        Schema::dropIfExists('tl_route_messages_view_sponsored_message');
        Schema::dropIfExists('tl_route_messages_upload_media');
        Schema::dropIfExists('tl_route_messages_upload_imported_media');
        Schema::dropIfExists('tl_route_messages_upload_encrypted_file');
        Schema::dropIfExists('tl_route_messages_update_saved_reaction_tag');
        Schema::dropIfExists('tl_route_messages_update_pinned_message');
        Schema::dropIfExists('tl_route_messages_update_pinned_forum_topic');
        Schema::dropIfExists('tl_route_messages_update_dialog_filters_order');
        Schema::dropIfExists('tl_route_messages_update_dialog_filter');
        Schema::dropIfExists('tl_route_messages_unpin_all_messages');
        Schema::dropIfExists('tl_route_messages_uninstall_sticker_set');
        Schema::dropIfExists('tl_route_messages_translate_text');
        Schema::dropIfExists('tl_route_messages_transcribe_audio');
        Schema::dropIfExists('tl_route_messages_toggle_todo_completed');
        Schema::dropIfExists('tl_route_messages_toggle_suggested_post_approval');
        Schema::dropIfExists('tl_route_messages_toggle_sticker_sets');
        Schema::dropIfExists('tl_route_messages_toggle_saved_dialog_pin');
        Schema::dropIfExists('tl_route_messages_toggle_peer_translations');
        Schema::dropIfExists('tl_route_messages_toggle_paid_reaction_privacy');
        Schema::dropIfExists('tl_route_messages_toggle_no_forwards');
        Schema::dropIfExists('tl_route_messages_toggle_dialog_pin');
        Schema::dropIfExists('tl_route_messages_toggle_dialog_filter_tags');
        Schema::dropIfExists('tl_route_messages_toggle_bot_in_attach_menu');
        Schema::dropIfExists('tl_route_messages_summarize_text');
        Schema::dropIfExists('tl_route_messages_start_history_import');
        Schema::dropIfExists('tl_route_messages_start_bot');
        Schema::dropIfExists('tl_route_messages_set_typing');
        Schema::dropIfExists('tl_route_messages_set_inline_game_score');
        Schema::dropIfExists('tl_route_messages_set_inline_bot_results');
        Schema::dropIfExists('tl_route_messages_set_history_t_t_l');
        Schema::dropIfExists('tl_route_messages_set_game_score');
        Schema::dropIfExists('tl_route_messages_set_encrypted_typing');
        Schema::dropIfExists('tl_route_messages_set_default_reaction');
        Schema::dropIfExists('tl_route_messages_set_default_history_t_t_l');
        Schema::dropIfExists('tl_route_messages_set_chat_wall_paper');
        Schema::dropIfExists('tl_route_messages_set_chat_theme');
        Schema::dropIfExists('tl_route_messages_set_chat_available_reactions');
        Schema::dropIfExists('tl_route_messages_set_bot_shipping_results');
        Schema::dropIfExists('tl_route_messages_set_bot_precheckout_results');
        Schema::dropIfExists('tl_route_messages_set_bot_guest_chat_result');
        Schema::dropIfExists('tl_route_messages_set_bot_callback_answer');
        Schema::dropIfExists('tl_route_messages_send_web_view_result_message');
        Schema::dropIfExists('tl_route_messages_send_web_view_data');
        Schema::dropIfExists('tl_route_messages_send_vote');
        Schema::dropIfExists('tl_route_messages_send_screenshot_notification');
        Schema::dropIfExists('tl_route_messages_send_scheduled_messages');
        Schema::dropIfExists('tl_route_messages_send_reaction');
        Schema::dropIfExists('tl_route_messages_send_quick_reply_messages');
        Schema::dropIfExists('tl_route_messages_send_paid_reaction');
        Schema::dropIfExists('tl_route_messages_send_multi_media');
        Schema::dropIfExists('tl_route_messages_send_message');
        Schema::dropIfExists('tl_route_messages_send_media');
        Schema::dropIfExists('tl_route_messages_send_inline_bot_result');
        Schema::dropIfExists('tl_route_messages_send_encrypted_service');
        Schema::dropIfExists('tl_route_messages_send_encrypted_file');
        Schema::dropIfExists('tl_route_messages_send_encrypted');
        Schema::dropIfExists('tl_route_messages_send_bot_requested_peer');
        Schema::dropIfExists('tl_route_messages_search_stickers');
        Schema::dropIfExists('tl_route_messages_search_sticker_sets');
        Schema::dropIfExists('tl_route_messages_search_sent_media');
        Schema::dropIfExists('tl_route_messages_search_global');
        Schema::dropIfExists('tl_route_messages_search_emoji_sticker_sets');
        Schema::dropIfExists('tl_route_messages_search_custom_emoji');
        Schema::dropIfExists('tl_route_messages_search');
        Schema::dropIfExists('tl_route_messages_save_recent_sticker');
        Schema::dropIfExists('tl_route_messages_save_prepared_inline_message');
        Schema::dropIfExists('tl_route_messages_save_gif');
        Schema::dropIfExists('tl_route_messages_save_draft');
        Schema::dropIfExists('tl_route_messages_save_default_send_as');
        Schema::dropIfExists('tl_route_messages_request_web_view');
        Schema::dropIfExists('tl_route_messages_request_url_auth');
        Schema::dropIfExists('tl_route_messages_request_simple_web_view');
        Schema::dropIfExists('tl_route_messages_request_main_web_view');
        Schema::dropIfExists('tl_route_messages_request_encryption');
        Schema::dropIfExists('tl_route_messages_request_app_web_view');
        Schema::dropIfExists('tl_route_messages_report_sponsored_message');
        Schema::dropIfExists('tl_route_messages_report_spam');
        Schema::dropIfExists('tl_route_messages_report_read_metrics');
        Schema::dropIfExists('tl_route_messages_report_reaction');
        Schema::dropIfExists('tl_route_messages_report_music_listen');
        Schema::dropIfExists('tl_route_messages_report_messages_delivery');
        Schema::dropIfExists('tl_route_messages_report_encrypted_spam');
        Schema::dropIfExists('tl_route_messages_report');
        Schema::dropIfExists('tl_route_messages_reorder_sticker_sets');
        Schema::dropIfExists('tl_route_messages_reorder_quick_replies');
        Schema::dropIfExists('tl_route_messages_reorder_pinned_saved_dialogs');
        Schema::dropIfExists('tl_route_messages_reorder_pinned_forum_topics');
        Schema::dropIfExists('tl_route_messages_reorder_pinned_dialogs');
        Schema::dropIfExists('tl_route_messages_read_saved_history');
        Schema::dropIfExists('tl_route_messages_read_reactions');
        Schema::dropIfExists('tl_route_messages_read_poll_votes');
        Schema::dropIfExists('tl_route_messages_read_message_contents');
        Schema::dropIfExists('tl_route_messages_read_mentions');
        Schema::dropIfExists('tl_route_messages_read_history');
        Schema::dropIfExists('tl_route_messages_read_featured_stickers');
        Schema::dropIfExists('tl_route_messages_read_encrypted_history');
        Schema::dropIfExists('tl_route_messages_read_discussion');
        Schema::dropIfExists('tl_route_messages_rate_transcribed_audio');
        Schema::dropIfExists('tl_route_messages_prolong_web_view');
        Schema::dropIfExists('tl_route_messages_migrate_chat');
        Schema::dropIfExists('tl_route_messages_mark_dialog_unread');
        Schema::dropIfExists('tl_route_messages_install_sticker_set');
        Schema::dropIfExists('tl_route_messages_init_history_import');
        Schema::dropIfExists('tl_route_messages_import_chat_invite');
        Schema::dropIfExists('tl_route_messages_hide_peer_settings_bar');
        Schema::dropIfExists('tl_route_messages_hide_chat_join_request');
        Schema::dropIfExists('tl_route_messages_hide_all_chat_join_requests');
        Schema::dropIfExists('tl_route_messages_get_web_page_preview');
        Schema::dropIfExists('tl_route_messages_get_web_page');
        Schema::dropIfExists('tl_route_messages_get_unread_reactions');
        Schema::dropIfExists('tl_route_messages_get_unread_poll_votes');
        Schema::dropIfExists('tl_route_messages_get_unread_mentions');
        Schema::dropIfExists('tl_route_messages_get_top_reactions');
        Schema::dropIfExists('tl_route_messages_get_stickers');
        Schema::dropIfExists('tl_route_messages_get_sticker_set');
        Schema::dropIfExists('tl_route_messages_get_sponsored_messages');
        Schema::dropIfExists('tl_route_messages_get_search_results_positions');
        Schema::dropIfExists('tl_route_messages_get_search_results_calendar');
        Schema::dropIfExists('tl_route_messages_get_scheduled_messages');
        Schema::dropIfExists('tl_route_messages_get_scheduled_history');
        Schema::dropIfExists('tl_route_messages_get_saved_reaction_tags');
        Schema::dropIfExists('tl_route_messages_get_saved_history');
        Schema::dropIfExists('tl_route_messages_get_saved_gifs');
        Schema::dropIfExists('tl_route_messages_get_saved_dialogs_by_i_d');
        Schema::dropIfExists('tl_route_messages_get_saved_dialogs');
        Schema::dropIfExists('tl_route_messages_get_rich_message');
        Schema::dropIfExists('tl_route_messages_get_replies');
        Schema::dropIfExists('tl_route_messages_get_recent_stickers');
        Schema::dropIfExists('tl_route_messages_get_recent_reactions');
        Schema::dropIfExists('tl_route_messages_get_recent_locations');
        Schema::dropIfExists('tl_route_messages_get_quick_reply_messages');
        Schema::dropIfExists('tl_route_messages_get_quick_replies');
        Schema::dropIfExists('tl_route_messages_get_prepared_inline_message');
        Schema::dropIfExists('tl_route_messages_get_poll_votes');
        Schema::dropIfExists('tl_route_messages_get_poll_results');
        Schema::dropIfExists('tl_route_messages_get_pinned_saved_dialogs');
        Schema::dropIfExists('tl_route_messages_get_pinned_dialogs');
        Schema::dropIfExists('tl_route_messages_get_personal_channel_history');
        Schema::dropIfExists('tl_route_messages_get_peer_settings');
        Schema::dropIfExists('tl_route_messages_get_peer_dialogs');
        Schema::dropIfExists('tl_route_messages_get_paid_reaction_privacy');
        Schema::dropIfExists('tl_route_messages_get_outbox_read_date');
        Schema::dropIfExists('tl_route_messages_get_onlines');
        Schema::dropIfExists('tl_route_messages_get_old_featured_stickers');
        Schema::dropIfExists('tl_route_messages_get_my_stickers');
        Schema::dropIfExists('tl_route_messages_get_messages_views');
        Schema::dropIfExists('tl_route_messages_get_messages_reactions');
        Schema::dropIfExists('tl_route_messages_get_messages');
        Schema::dropIfExists('tl_route_messages_get_message_reactions_list');
        Schema::dropIfExists('tl_route_messages_get_message_edit_data');
        Schema::dropIfExists('tl_route_messages_get_mask_stickers');
        Schema::dropIfExists('tl_route_messages_get_inline_game_high_scores');
        Schema::dropIfExists('tl_route_messages_get_inline_bot_results');
        Schema::dropIfExists('tl_route_messages_get_history');
        Schema::dropIfExists('tl_route_messages_get_game_high_scores');
        Schema::dropIfExists('tl_route_messages_get_future_chat_creator_after_leave');
        Schema::dropIfExists('tl_route_messages_get_full_chat');
        Schema::dropIfExists('tl_route_messages_get_forum_topics_by_i_d');
        Schema::dropIfExists('tl_route_messages_get_forum_topics');
        Schema::dropIfExists('tl_route_messages_get_featured_stickers');
        Schema::dropIfExists('tl_route_messages_get_featured_emoji_stickers');
        Schema::dropIfExists('tl_route_messages_get_faved_stickers');
        Schema::dropIfExists('tl_route_messages_get_extended_media');
        Schema::dropIfExists('tl_route_messages_get_exported_chat_invites');
        Schema::dropIfExists('tl_route_messages_get_exported_chat_invite');
        Schema::dropIfExists('tl_route_messages_get_emoji_u_r_l');
        Schema::dropIfExists('tl_route_messages_get_emoji_stickers');
        Schema::dropIfExists('tl_route_messages_get_emoji_sticker_groups');
        Schema::dropIfExists('tl_route_messages_get_emoji_status_groups');
        Schema::dropIfExists('tl_route_messages_get_emoji_profile_photo_groups');
        Schema::dropIfExists('tl_route_messages_get_emoji_keywords_difference');
        Schema::dropIfExists('tl_route_messages_get_emoji_keywords');
        Schema::dropIfExists('tl_route_messages_get_emoji_groups');
        Schema::dropIfExists('tl_route_messages_get_emoji_game_info');
        Schema::dropIfExists('tl_route_messages_get_document_by_hash');
        Schema::dropIfExists('tl_route_messages_get_discussion_message');
        Schema::dropIfExists('tl_route_messages_get_dialogs');
        Schema::dropIfExists('tl_route_messages_get_dialog_filters');
        Schema::dropIfExists('tl_route_messages_get_dh_config');
        Schema::dropIfExists('tl_route_messages_get_default_tag_reactions');
        Schema::dropIfExists('tl_route_messages_get_default_history_t_t_l');
        Schema::dropIfExists('tl_route_messages_get_common_chats');
        Schema::dropIfExists('tl_route_messages_get_chats');
        Schema::dropIfExists('tl_route_messages_get_chat_invite_importers');
        Schema::dropIfExists('tl_route_messages_get_bot_callback_answer');
        Schema::dropIfExists('tl_route_messages_get_bot_app');
        Schema::dropIfExists('tl_route_messages_get_available_reactions');
        Schema::dropIfExists('tl_route_messages_get_available_effects');
        Schema::dropIfExists('tl_route_messages_get_attach_menu_bots');
        Schema::dropIfExists('tl_route_messages_get_attach_menu_bot');
        Schema::dropIfExists('tl_route_messages_get_archived_stickers');
        Schema::dropIfExists('tl_route_messages_get_all_stickers');
        Schema::dropIfExists('tl_route_messages_get_all_drafts');
        Schema::dropIfExists('tl_route_messages_get_admins_with_invites');
        Schema::dropIfExists('tl_route_messages_forward_messages');
        Schema::dropIfExists('tl_route_messages_fave_sticker');
        Schema::dropIfExists('tl_route_messages_export_chat_invite');
        Schema::dropIfExists('tl_route_messages_edit_quick_reply_shortcut');
        Schema::dropIfExists('tl_route_messages_edit_message');
        Schema::dropIfExists('tl_route_messages_edit_inline_bot_message');
        Schema::dropIfExists('tl_route_messages_edit_forum_topic');
        Schema::dropIfExists('tl_route_messages_edit_fact_check');
        Schema::dropIfExists('tl_route_messages_edit_exported_chat_invite');
        Schema::dropIfExists('tl_route_messages_edit_chat_title');
        Schema::dropIfExists('tl_route_messages_edit_chat_photo');
        Schema::dropIfExists('tl_route_messages_edit_chat_participant_rank');
        Schema::dropIfExists('tl_route_messages_edit_chat_default_banned_rights');
        Schema::dropIfExists('tl_route_messages_edit_chat_creator');
        Schema::dropIfExists('tl_route_messages_edit_chat_admin');
        Schema::dropIfExists('tl_route_messages_edit_chat_about');
        Schema::dropIfExists('tl_route_messages_discard_encryption');
        Schema::dropIfExists('tl_route_messages_delete_topic_history');
        Schema::dropIfExists('tl_route_messages_delete_scheduled_messages');
        Schema::dropIfExists('tl_route_messages_delete_saved_history');
        Schema::dropIfExists('tl_route_messages_delete_revoked_exported_chat_invites');
        Schema::dropIfExists('tl_route_messages_delete_quick_reply_shortcut');
        Schema::dropIfExists('tl_route_messages_delete_quick_reply_messages');
        Schema::dropIfExists('tl_route_messages_delete_poll_answer');
        Schema::dropIfExists('tl_route_messages_delete_phone_call_history');
        Schema::dropIfExists('tl_route_messages_delete_participant_reactions');
        Schema::dropIfExists('tl_route_messages_delete_participant_reaction');
        Schema::dropIfExists('tl_route_messages_delete_messages');
        Schema::dropIfExists('tl_route_messages_delete_history');
        Schema::dropIfExists('tl_route_messages_delete_fact_check');
        Schema::dropIfExists('tl_route_messages_delete_exported_chat_invite');
        Schema::dropIfExists('tl_route_messages_delete_chat_user');
        Schema::dropIfExists('tl_route_messages_delete_chat');
        Schema::dropIfExists('tl_route_messages_decline_url_auth');
        Schema::dropIfExists('tl_route_messages_create_forum_topic');
        Schema::dropIfExists('tl_route_messages_create_chat');
        Schema::dropIfExists('tl_route_messages_compose_message_with_a_i');
        Schema::dropIfExists('tl_route_messages_click_sponsored_message');
        Schema::dropIfExists('tl_route_messages_clear_recent_stickers');
        Schema::dropIfExists('tl_route_messages_clear_recent_reactions');
        Schema::dropIfExists('tl_route_messages_clear_all_drafts');
        Schema::dropIfExists('tl_route_messages_check_url_auth_match_code');
        Schema::dropIfExists('tl_route_messages_check_quick_reply_shortcut');
        Schema::dropIfExists('tl_route_messages_check_history_import_peer');
        Schema::dropIfExists('tl_route_messages_check_history_import');
        Schema::dropIfExists('tl_route_messages_check_chat_invite');
        Schema::dropIfExists('tl_route_messages_append_todo_list');
        Schema::dropIfExists('tl_route_messages_add_poll_answer');
        Schema::dropIfExists('tl_route_messages_add_chat_user');
        Schema::dropIfExists('tl_route_messages_accept_url_auth');
        Schema::dropIfExists('tl_route_messages_accept_encryption');
        Schema::dropIfExists('tl_route_langpack_get_language');
        Schema::dropIfExists('tl_route_langpack_get_lang_pack');
        Schema::dropIfExists('tl_route_langpack_get_difference');
        Schema::dropIfExists('tl_route_help_set_bot_updates_status');
        Schema::dropIfExists('tl_route_help_save_app_log');
        Schema::dropIfExists('tl_route_help_hide_promo_data');
        Schema::dropIfExists('tl_route_help_get_user_info');
        Schema::dropIfExists('tl_route_help_get_timezones_list');
        Schema::dropIfExists('tl_route_help_get_terms_of_service_update');
        Schema::dropIfExists('tl_route_help_get_support_name');
        Schema::dropIfExists('tl_route_help_get_support');
        Schema::dropIfExists('tl_route_help_get_recent_me_urls');
        Schema::dropIfExists('tl_route_help_get_promo_data');
        Schema::dropIfExists('tl_route_help_get_premium_promo');
        Schema::dropIfExists('tl_route_help_get_peer_profile_colors');
        Schema::dropIfExists('tl_route_help_get_peer_colors');
        Schema::dropIfExists('tl_route_help_get_passport_config');
        Schema::dropIfExists('tl_route_help_get_nearest_dc');
        Schema::dropIfExists('tl_route_help_get_invite_text');
        Schema::dropIfExists('tl_route_help_get_deep_link_info');
        Schema::dropIfExists('tl_route_help_get_countries_list');
        Schema::dropIfExists('tl_route_help_get_config');
        Schema::dropIfExists('tl_route_help_get_cdn_config');
        Schema::dropIfExists('tl_route_help_get_app_update');
        Schema::dropIfExists('tl_route_help_get_app_config');
        Schema::dropIfExists('tl_route_help_edit_user_info');
        Schema::dropIfExists('tl_route_help_dismiss_suggestion');
        Schema::dropIfExists('tl_route_help_accept_terms_of_service');
        Schema::dropIfExists('tl_route_get_future_salts');
        Schema::dropIfExists('tl_route_fragment_get_collectible_info');
        Schema::dropIfExists('tl_route_folders_edit_peer_folders');
        Schema::dropIfExists('tl_route_destroy_session');
        Schema::dropIfExists('tl_route_contacts_update_contact_note');
        Schema::dropIfExists('tl_route_contacts_unblock');
        Schema::dropIfExists('tl_route_contacts_toggle_top_peers');
        Schema::dropIfExists('tl_route_contacts_set_blocked');
        Schema::dropIfExists('tl_route_contacts_search');
        Schema::dropIfExists('tl_route_contacts_resolve_username');
        Schema::dropIfExists('tl_route_contacts_resolve_phone');
        Schema::dropIfExists('tl_route_contacts_reset_top_peer_rating');
        Schema::dropIfExists('tl_route_contacts_reset_saved');
        Schema::dropIfExists('tl_route_contacts_import_contacts');
        Schema::dropIfExists('tl_route_contacts_import_contact_token');
        Schema::dropIfExists('tl_route_contacts_get_top_peers');
        Schema::dropIfExists('tl_route_contacts_get_sponsored_peers');
        Schema::dropIfExists('tl_route_contacts_get_located');
        Schema::dropIfExists('tl_route_contacts_get_contacts');
        Schema::dropIfExists('tl_route_contacts_get_blocked');
        Schema::dropIfExists('tl_route_contacts_get_birthdays');
        Schema::dropIfExists('tl_route_contacts_export_contact_token');
        Schema::dropIfExists('tl_route_contacts_edit_close_friends');
        Schema::dropIfExists('tl_route_contacts_delete_contacts');
        Schema::dropIfExists('tl_route_contacts_delete_by_phones');
        Schema::dropIfExists('tl_route_contacts_block_from_replies');
        Schema::dropIfExists('tl_route_contacts_block');
        Schema::dropIfExists('tl_route_contacts_add_contact');
        Schema::dropIfExists('tl_route_contacts_accept_contact');
        Schema::dropIfExists('tl_route_chatlists_leave_chatlist');
        Schema::dropIfExists('tl_route_chatlists_join_chatlist_updates');
        Schema::dropIfExists('tl_route_chatlists_join_chatlist_invite');
        Schema::dropIfExists('tl_route_chatlists_hide_chatlist_updates');
        Schema::dropIfExists('tl_route_chatlists_get_exported_invites');
        Schema::dropIfExists('tl_route_chatlists_get_chatlist_updates');
        Schema::dropIfExists('tl_route_chatlists_export_chatlist_invite');
        Schema::dropIfExists('tl_route_chatlists_edit_exported_invite');
        Schema::dropIfExists('tl_route_chatlists_delete_exported_invite');
        Schema::dropIfExists('tl_route_chatlists_check_chatlist_invite');
        Schema::dropIfExists('tl_route_channels_update_username');
        Schema::dropIfExists('tl_route_channels_update_paid_messages_price');
        Schema::dropIfExists('tl_route_channels_update_emoji_status');
        Schema::dropIfExists('tl_route_channels_update_color');
        Schema::dropIfExists('tl_route_channels_toggle_view_forum_as_messages');
        Schema::dropIfExists('tl_route_channels_toggle_username');
        Schema::dropIfExists('tl_route_channels_toggle_slow_mode');
        Schema::dropIfExists('tl_route_channels_toggle_signatures');
        Schema::dropIfExists('tl_route_channels_toggle_pre_history_hidden');
        Schema::dropIfExists('tl_route_channels_toggle_participants_hidden');
        Schema::dropIfExists('tl_route_channels_toggle_join_to_send');
        Schema::dropIfExists('tl_route_channels_toggle_join_request');
        Schema::dropIfExists('tl_route_channels_toggle_forum');
        Schema::dropIfExists('tl_route_channels_toggle_autotranslation');
        Schema::dropIfExists('tl_route_channels_toggle_anti_spam');
        Schema::dropIfExists('tl_route_channels_set_stickers');
        Schema::dropIfExists('tl_route_channels_set_main_profile_tab');
        Schema::dropIfExists('tl_route_channels_set_emoji_stickers');
        Schema::dropIfExists('tl_route_channels_set_discussion_group');
        Schema::dropIfExists('tl_route_channels_set_boosts_to_unblock_restrictions');
        Schema::dropIfExists('tl_route_channels_search_posts');
        Schema::dropIfExists('tl_route_channels_restrict_sponsored_messages');
        Schema::dropIfExists('tl_route_channels_report_spam');
        Schema::dropIfExists('tl_route_channels_report_anti_spam_false_positive');
        Schema::dropIfExists('tl_route_channels_reorder_usernames');
        Schema::dropIfExists('tl_route_channels_read_message_contents');
        Schema::dropIfExists('tl_route_channels_read_history');
        Schema::dropIfExists('tl_route_channels_leave_channel');
        Schema::dropIfExists('tl_route_channels_join_channel');
        Schema::dropIfExists('tl_route_channels_invite_to_channel');
        Schema::dropIfExists('tl_route_channels_get_send_as');
        Schema::dropIfExists('tl_route_channels_get_participants');
        Schema::dropIfExists('tl_route_channels_get_participant');
        Schema::dropIfExists('tl_route_channels_get_messages');
        Schema::dropIfExists('tl_route_channels_get_message_author');
        Schema::dropIfExists('tl_route_channels_get_left_channels');
        Schema::dropIfExists('tl_route_channels_get_inactive_channels');
        Schema::dropIfExists('tl_route_channels_get_groups_for_discussion');
        Schema::dropIfExists('tl_route_channels_get_full_channel');
        Schema::dropIfExists('tl_route_channels_get_channels');
        Schema::dropIfExists('tl_route_channels_get_channel_recommendations');
        Schema::dropIfExists('tl_route_channels_get_admined_public_channels');
        Schema::dropIfExists('tl_route_channels_get_admin_log');
        Schema::dropIfExists('tl_route_channels_export_message_link');
        Schema::dropIfExists('tl_route_channels_edit_title');
        Schema::dropIfExists('tl_route_channels_edit_photo');
        Schema::dropIfExists('tl_route_channels_edit_location');
        Schema::dropIfExists('tl_route_channels_edit_banned');
        Schema::dropIfExists('tl_route_channels_edit_admin');
        Schema::dropIfExists('tl_route_channels_delete_participant_history');
        Schema::dropIfExists('tl_route_channels_delete_messages');
        Schema::dropIfExists('tl_route_channels_delete_history');
        Schema::dropIfExists('tl_route_channels_delete_channel');
        Schema::dropIfExists('tl_route_channels_deactivate_all_usernames');
        Schema::dropIfExists('tl_route_channels_create_channel');
        Schema::dropIfExists('tl_route_channels_convert_to_gigagroup');
        Schema::dropIfExists('tl_route_channels_check_username');
        Schema::dropIfExists('tl_route_channels_check_search_posts_flood');
        Schema::dropIfExists('tl_route_bots_update_user_emoji_status');
        Schema::dropIfExists('tl_route_bots_update_star_ref_program');
        Schema::dropIfExists('tl_route_bots_toggle_username');
        Schema::dropIfExists('tl_route_bots_toggle_user_emoji_status_permission');
        Schema::dropIfExists('tl_route_bots_set_join_chat_results');
        Schema::dropIfExists('tl_route_bots_set_custom_verification');
        Schema::dropIfExists('tl_route_bots_set_bot_menu_button');
        Schema::dropIfExists('tl_route_bots_set_bot_info');
        Schema::dropIfExists('tl_route_bots_set_bot_group_default_admin_rights');
        Schema::dropIfExists('tl_route_bots_set_bot_commands');
        Schema::dropIfExists('tl_route_bots_set_bot_broadcast_default_admin_rights');
        Schema::dropIfExists('tl_route_bots_send_custom_request');
        Schema::dropIfExists('tl_route_bots_reset_bot_commands');
        Schema::dropIfExists('tl_route_bots_request_web_view_button');
        Schema::dropIfExists('tl_route_bots_reorder_usernames');
        Schema::dropIfExists('tl_route_bots_reorder_preview_medias');
        Schema::dropIfExists('tl_route_bots_invoke_web_view_custom_method');
        Schema::dropIfExists('tl_route_bots_get_requested_web_view_button');
        Schema::dropIfExists('tl_route_bots_get_preview_info');
        Schema::dropIfExists('tl_route_bots_get_popular_app_bots');
        Schema::dropIfExists('tl_route_bots_get_bot_recommendations');
        Schema::dropIfExists('tl_route_bots_get_bot_menu_button');
        Schema::dropIfExists('tl_route_bots_get_bot_info');
        Schema::dropIfExists('tl_route_bots_get_access_settings');
        Schema::dropIfExists('tl_route_bots_export_bot_token');
        Schema::dropIfExists('tl_route_bots_edit_preview_media');
        Schema::dropIfExists('tl_route_bots_edit_access_settings');
        Schema::dropIfExists('tl_route_bots_delete_preview_media');
        Schema::dropIfExists('tl_route_bots_create_bot');
        Schema::dropIfExists('tl_route_bots_check_username');
        Schema::dropIfExists('tl_route_bots_check_download_file_params');
        Schema::dropIfExists('tl_route_bots_can_send_message');
        Schema::dropIfExists('tl_route_bots_answer_webhook_j_s_o_n_query');
        Schema::dropIfExists('tl_route_bots_allow_send_message');
        Schema::dropIfExists('tl_route_bots_add_preview_media');
        Schema::dropIfExists('tl_route_auth_sign_up');
        Schema::dropIfExists('tl_route_auth_sign_in');
        Schema::dropIfExists('tl_route_auth_send_code');
        Schema::dropIfExists('tl_route_auth_reset_login_email');
        Schema::dropIfExists('tl_route_auth_reset_authorizations');
        Schema::dropIfExists('tl_route_auth_resend_code');
        Schema::dropIfExists('tl_route_auth_request_password_recovery');
        Schema::dropIfExists('tl_route_auth_request_firebase_sms');
        Schema::dropIfExists('tl_route_auth_report_missing_code');
        Schema::dropIfExists('tl_route_auth_recover_password');
        Schema::dropIfExists('tl_route_auth_log_out');
        Schema::dropIfExists('tl_route_auth_init_passkey_login');
        Schema::dropIfExists('tl_route_auth_import_web_token_authorization');
        Schema::dropIfExists('tl_route_auth_import_login_token');
        Schema::dropIfExists('tl_route_auth_import_bot_authorization');
        Schema::dropIfExists('tl_route_auth_import_authorization');
        Schema::dropIfExists('tl_route_auth_finish_passkey_login');
        Schema::dropIfExists('tl_route_auth_export_login_token');
        Schema::dropIfExists('tl_route_auth_export_authorization');
        Schema::dropIfExists('tl_route_auth_drop_temp_auth_keys');
        Schema::dropIfExists('tl_route_auth_check_recovery_password');
        Schema::dropIfExists('tl_route_auth_check_password');
        Schema::dropIfExists('tl_route_auth_check_paid_auth');
        Schema::dropIfExists('tl_route_auth_cancel_code');
        Schema::dropIfExists('tl_route_auth_bind_temp_auth_key');
        Schema::dropIfExists('tl_route_auth_accept_login_token');
        Schema::dropIfExists('tl_route_aicompose_update_tone');
        Schema::dropIfExists('tl_route_aicompose_save_tone');
        Schema::dropIfExists('tl_route_aicompose_get_tones');
        Schema::dropIfExists('tl_route_aicompose_get_tone_example');
        Schema::dropIfExists('tl_route_aicompose_get_tone');
        Schema::dropIfExists('tl_route_aicompose_delete_tone');
        Schema::dropIfExists('tl_route_aicompose_create_tone');
        Schema::dropIfExists('tl_route_account_verify_phone');
        Schema::dropIfExists('tl_route_account_verify_email');
        Schema::dropIfExists('tl_route_account_upload_wall_paper');
        Schema::dropIfExists('tl_route_account_upload_theme');
        Schema::dropIfExists('tl_route_account_upload_ringtone');
        Schema::dropIfExists('tl_route_account_update_web_browser_settings');
        Schema::dropIfExists('tl_route_account_update_username');
        Schema::dropIfExists('tl_route_account_update_theme');
        Schema::dropIfExists('tl_route_account_update_status');
        Schema::dropIfExists('tl_route_account_update_profile');
        Schema::dropIfExists('tl_route_account_update_personal_channel');
        Schema::dropIfExists('tl_route_account_update_password_settings');
        Schema::dropIfExists('tl_route_account_update_notify_settings');
        Schema::dropIfExists('tl_route_account_update_emoji_status');
        Schema::dropIfExists('tl_route_account_update_device_locked');
        Schema::dropIfExists('tl_route_account_update_connected_bot');
        Schema::dropIfExists('tl_route_account_update_color');
        Schema::dropIfExists('tl_route_account_update_business_work_hours');
        Schema::dropIfExists('tl_route_account_update_business_location');
        Schema::dropIfExists('tl_route_account_update_business_intro');
        Schema::dropIfExists('tl_route_account_update_business_greeting_message');
        Schema::dropIfExists('tl_route_account_update_business_away_message');
        Schema::dropIfExists('tl_route_account_update_birthday');
        Schema::dropIfExists('tl_route_account_unregister_device');
        Schema::dropIfExists('tl_route_account_toggle_web_browser_settings_exception');
        Schema::dropIfExists('tl_route_account_toggle_username');
        Schema::dropIfExists('tl_route_account_toggle_sponsored_messages');
        Schema::dropIfExists('tl_route_account_toggle_no_paid_messages_exception');
        Schema::dropIfExists('tl_route_account_toggle_connected_bot_paused');
        Schema::dropIfExists('tl_route_account_set_reactions_notify_settings');
        Schema::dropIfExists('tl_route_account_set_privacy');
        Schema::dropIfExists('tl_route_account_set_main_profile_tab');
        Schema::dropIfExists('tl_route_account_set_global_privacy_settings');
        Schema::dropIfExists('tl_route_account_set_content_settings');
        Schema::dropIfExists('tl_route_account_set_contact_sign_up_notification');
        Schema::dropIfExists('tl_route_account_set_authorization_t_t_l');
        Schema::dropIfExists('tl_route_account_set_account_t_t_l');
        Schema::dropIfExists('tl_route_account_send_verify_phone_code');
        Schema::dropIfExists('tl_route_account_send_verify_email_code');
        Schema::dropIfExists('tl_route_account_send_confirm_phone_code');
        Schema::dropIfExists('tl_route_account_send_change_phone_code');
        Schema::dropIfExists('tl_route_account_save_wall_paper');
        Schema::dropIfExists('tl_route_account_save_theme');
        Schema::dropIfExists('tl_route_account_save_secure_value');
        Schema::dropIfExists('tl_route_account_save_ringtone');
        Schema::dropIfExists('tl_route_account_save_music');
        Schema::dropIfExists('tl_route_account_save_auto_save_settings');
        Schema::dropIfExists('tl_route_account_save_auto_download_settings');
        Schema::dropIfExists('tl_route_account_resolve_business_chat_link');
        Schema::dropIfExists('tl_route_account_reset_web_authorizations');
        Schema::dropIfExists('tl_route_account_reset_web_authorization');
        Schema::dropIfExists('tl_route_account_reset_wall_papers');
        Schema::dropIfExists('tl_route_account_reset_password');
        Schema::dropIfExists('tl_route_account_reset_notify_settings');
        Schema::dropIfExists('tl_route_account_reset_authorization');
        Schema::dropIfExists('tl_route_account_resend_password_email');
        Schema::dropIfExists('tl_route_account_report_profile_photo');
        Schema::dropIfExists('tl_route_account_report_peer');
        Schema::dropIfExists('tl_route_account_reorder_usernames');
        Schema::dropIfExists('tl_route_account_register_passkey');
        Schema::dropIfExists('tl_route_account_register_device');
        Schema::dropIfExists('tl_route_account_invalidate_sign_in_codes');
        Schema::dropIfExists('tl_route_account_install_wall_paper');
        Schema::dropIfExists('tl_route_account_install_theme');
        Schema::dropIfExists('tl_route_account_init_takeout_session');
        Schema::dropIfExists('tl_route_account_init_passkey_registration');
        Schema::dropIfExists('tl_route_account_get_web_browser_settings');
        Schema::dropIfExists('tl_route_account_get_web_authorizations');
        Schema::dropIfExists('tl_route_account_get_wall_papers');
        Schema::dropIfExists('tl_route_account_get_wall_paper');
        Schema::dropIfExists('tl_route_account_get_unique_gift_chat_themes');
        Schema::dropIfExists('tl_route_account_get_tmp_password');
        Schema::dropIfExists('tl_route_account_get_themes');
        Schema::dropIfExists('tl_route_account_get_theme');
        Schema::dropIfExists('tl_route_account_get_saved_ringtones');
        Schema::dropIfExists('tl_route_account_get_saved_music_ids');
        Schema::dropIfExists('tl_route_account_get_recent_emoji_statuses');
        Schema::dropIfExists('tl_route_account_get_reactions_notify_settings');
        Schema::dropIfExists('tl_route_account_get_privacy');
        Schema::dropIfExists('tl_route_account_get_password_settings');
        Schema::dropIfExists('tl_route_account_get_password');
        Schema::dropIfExists('tl_route_account_get_passkeys');
        Schema::dropIfExists('tl_route_account_get_paid_messages_revenue');
        Schema::dropIfExists('tl_route_account_get_notify_settings');
        Schema::dropIfExists('tl_route_account_get_notify_exceptions');
        Schema::dropIfExists('tl_route_account_get_global_privacy_settings');
        Schema::dropIfExists('tl_route_account_get_default_profile_photo_emojis');
        Schema::dropIfExists('tl_route_account_get_default_group_photo_emojis');
        Schema::dropIfExists('tl_route_account_get_default_emoji_statuses');
        Schema::dropIfExists('tl_route_account_get_default_background_emojis');
        Schema::dropIfExists('tl_route_account_get_content_settings');
        Schema::dropIfExists('tl_route_account_get_contact_sign_up_notification');
        Schema::dropIfExists('tl_route_account_get_connected_bots');
        Schema::dropIfExists('tl_route_account_get_collectible_emoji_statuses');
        Schema::dropIfExists('tl_route_account_get_chat_themes');
        Schema::dropIfExists('tl_route_account_get_channel_restricted_status_emojis');
        Schema::dropIfExists('tl_route_account_get_channel_default_emoji_statuses');
        Schema::dropIfExists('tl_route_account_get_business_chat_links');
        Schema::dropIfExists('tl_route_account_get_bot_business_connection');
        Schema::dropIfExists('tl_route_account_get_auto_save_settings');
        Schema::dropIfExists('tl_route_account_get_auto_download_settings');
        Schema::dropIfExists('tl_route_account_get_authorizations');
        Schema::dropIfExists('tl_route_account_get_authorization_form');
        Schema::dropIfExists('tl_route_account_get_account_t_t_l');
        Schema::dropIfExists('tl_route_account_finish_takeout_session');
        Schema::dropIfExists('tl_route_account_edit_business_chat_link');
        Schema::dropIfExists('tl_route_account_disable_peer_connected_bot');
        Schema::dropIfExists('tl_route_account_delete_web_browser_settings_exceptions');
        Schema::dropIfExists('tl_route_account_delete_secure_value');
        Schema::dropIfExists('tl_route_account_delete_passkey');
        Schema::dropIfExists('tl_route_account_delete_business_chat_link');
        Schema::dropIfExists('tl_route_account_delete_auto_save_exceptions');
        Schema::dropIfExists('tl_route_account_delete_account');
        Schema::dropIfExists('tl_route_account_decline_password_reset');
        Schema::dropIfExists('tl_route_account_create_theme');
        Schema::dropIfExists('tl_route_account_create_business_chat_link');
        Schema::dropIfExists('tl_route_account_confirm_phone');
        Schema::dropIfExists('tl_route_account_confirm_password_email');
        Schema::dropIfExists('tl_route_account_confirm_bot_connection');
        Schema::dropIfExists('tl_route_account_clear_recent_emoji_statuses');
        Schema::dropIfExists('tl_route_account_check_username');
        Schema::dropIfExists('tl_route_account_change_phone');
        Schema::dropIfExists('tl_route_account_change_authorization_settings');
        Schema::dropIfExists('tl_route_account_cancel_password_email');
        Schema::dropIfExists('tl_route_account_accept_authorization');
    }
};
