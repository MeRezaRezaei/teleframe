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
        Schema::table('tf_attach_menu_bots_icons', function (Blueprint $table) {
        $table->foreign(['account_id', 'bot_id'])->references(['account_id', 'bot_id'])->on('tf_attach_menu_bots')->onDelete('cascade');
        });

        Schema::table('tf_documents_attributes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_documents')->onDelete('cascade');
        });

        Schema::table('tf_documents_attributes_mask_coords', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_documents_attributes')->onDelete('cascade');
        });

        Schema::table('tf_documents_thumbs', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_documents')->onDelete('cascade');
        });

        Schema::table('tf_documents_thumbs_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_documents_thumbs')->onDelete('cascade');
        });

        Schema::table('tf_documents_video_thumbs', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_documents')->onDelete('cascade');
        });

        Schema::table('tf_documents_video_thumbs_background_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_documents_video_thumbs')->onDelete('cascade');
        });

        Schema::table('tf_attach_menu_bots_icons_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'bot_id'])->references(['account_id', 'bot_id'])->on('tf_attach_menu_bots_icons')->onDelete('cascade');
        });

        Schema::table('tf_attach_menu_bots_peer_types', function (Blueprint $table) {
        $table->foreign(['account_id', 'bot_id'])->references(['account_id', 'bot_id'])->on('tf_attach_menu_bots')->onDelete('cascade');
        });

        Schema::table('tf_photos_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_photos')->onDelete('cascade');
        });

        Schema::table('tf_photos_sizes_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_photos_sizes')->onDelete('cascade');
        });

        Schema::table('tf_photos_video_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_photos')->onDelete('cascade');
        });

        Schema::table('tf_photos_video_sizes_background_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_photos_video_sizes')->onDelete('cascade');
        });

        Schema::table('tf_bot_infos_user_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'bot_info_id'])->references(['account_id', 'bot_info_id'])->on('tf_bot_infos')->onDelete('cascade');
        });

        Schema::table('tf_bot_infos_description', function (Blueprint $table) {
        $table->foreign(['account_id', 'bot_info_id'])->references(['account_id', 'bot_info_id'])->on('tf_bot_infos')->onDelete('cascade');
        });

        Schema::table('tf_bot_infos_commands', function (Blueprint $table) {
        $table->foreign(['account_id', 'bot_info_id'])->references(['account_id', 'bot_info_id'])->on('tf_bot_infos')->onDelete('cascade');
        });

        Schema::table('tf_bot_infos_menu_button', function (Blueprint $table) {
        $table->foreign(['account_id', 'bot_info_id'])->references(['account_id', 'bot_info_id'])->on('tf_bot_infos')->onDelete('cascade');
        });

        Schema::table('tf_bot_infos_privacy_policy_url', function (Blueprint $table) {
        $table->foreign(['account_id', 'bot_info_id'])->references(['account_id', 'bot_info_id'])->on('tf_bot_infos')->onDelete('cascade');
        });

        Schema::table('tf_bot_infos_app_settings', function (Blueprint $table) {
        $table->foreign(['account_id', 'bot_info_id'])->references(['account_id', 'bot_info_id'])->on('tf_bot_infos')->onDelete('cascade');
        });

        Schema::table('tf_bot_infos_verifier_settings', function (Blueprint $table) {
        $table->foreign(['account_id', 'bot_info_id'])->references(['account_id', 'bot_info_id'])->on('tf_bot_infos')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_entities', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_reply_markup', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_reply_markup_rows', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_reply_markup')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_reply_markup_rows_buttons', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_reply_markup_rows')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_reply_markup_rows_buttons_style', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_reply_markup_rows_buttons')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_types', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_reply_markup_rows_buttons')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_reply_markup_rows_buttons')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type_user_admin_rights', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type_bot_admin_rights', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_geo', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_photo', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_photo_attributes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_photo')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_photo_attributes_mask_coords', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_photo_attributes')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_text', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_author', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_items', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_items_text', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_blocks_items')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_caption', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_chats_photo', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_chats')->onDelete('cascade');
        });

        Schema::table('tf_chats_migrated_to', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_chats')->onDelete('cascade');
        });

        Schema::table('tf_chats_admin_rights', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_chats')->onDelete('cascade');
        });

        Schema::table('tf_chats_default_banned_rights', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_chats')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_title', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_rows', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_rows_cells', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_blocks_rows')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_rows_cells_text', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_blocks_rows_cells')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_articles', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_geo', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_photos', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_photos_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_photos')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_photos_sizes_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_photos_sizes')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_photos_video_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_photos')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_photos_video_sizes_background_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_photos_video_sizes')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_documents', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_documents_thumbs', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_documents')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_documents_thumbs_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_documents_thumbs')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_documents_video_thumbs', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_documents')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_documents_video_thumbs_background_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_documents_video_thumbs')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_documents_attributes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_documents')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_documents_attributes_mask_coords', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_send_message_rich_message_documents_attributes')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_title', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_description', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_url', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_thumb', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_thumb_attributes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_thumb')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_thumb_attributes_mask_coords', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_thumb_attributes')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_content', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_content_attributes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_content')->onDelete('cascade');
        });

        Schema::table('tf_bot_inline_results_content_attributes_mask_coords', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_bot_inline_results_content_attributes')->onDelete('cascade');
        });

        Schema::table('tf_business_chat_links_entities', function (Blueprint $table) {
        $table->foreign(['account_id', 'business_chat_link_id'])->references(['account_id', 'business_chat_link_id'])->on('tf_business_chat_links')->onDelete('cascade');
        });

        Schema::table('tf_business_chat_links_title', function (Blueprint $table) {
        $table->foreign(['account_id', 'business_chat_link_id'])->references(['account_id', 'business_chat_link_id'])->on('tf_business_chat_links')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events')->onDelete('cascade');
        });

        Schema::table('tf_messages_service_from_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_service')->onDelete('cascade');
        });

        Schema::table('tf_messages_service_saved_peer_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_service')->onDelete('cascade');
        });

        Schema::table('tf_messages_service_reply_to', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_service')->onDelete('cascade');
        });

        Schema::table('tf_messages_service_reply_to_reply_from', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_service_reply_to')->onDelete('cascade');
        });

        Schema::table('tf_message_medias_ttl_seconds', function (Blueprint $table) {
        $table->foreign(['account_id', 'message_media_id'])->references(['account_id', 'message_media_id'])->on('tf_message_medias')->onDelete('cascade');
        });

        Schema::table('tf_messages_service_reply_to_quote_entities', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_service_reply_to')->onDelete('cascade');
        });

        Schema::table('tf_messages_service_reactions', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_service')->onDelete('cascade');
        });

        Schema::table('tf_messages_service_reactions_results', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_service_reactions')->onDelete('cascade');
        });

        Schema::table('tf_messages_service_reactions_recent_reactions', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_service_reactions')->onDelete('cascade');
        });

        Schema::table('tf_messages_service_reactions_top_reactors', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_service_reactions')->onDelete('cascade');
        });

        Schema::table('tf_messages_service_ttl_period', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_service')->onDelete('cascade');
        });

        Schema::table('tf_message_actions_users', function (Blueprint $table) {
        $table->foreign(['account_id', 'message_action_id'])->references(['account_id', 'message_action_id'])->on('tf_message_actions')->onDelete('cascade');
        });

        Schema::table('tf_channel_participants_subscription_until_date', function (Blueprint $table) {
        $table->foreign(['account_id', 'user_id'])->references(['account_id', 'user_id'])->on('tf_channel_participants')->onDelete('cascade');
        });

        Schema::table('tf_channel_participants_rank', function (Blueprint $table) {
        $table->foreign(['account_id', 'user_id'])->references(['account_id', 'user_id'])->on('tf_channel_participants')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action_prev_banned_rights', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events_action')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action_new_banned_rights', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events_action')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action_prev_value', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events_action')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action_new_value', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events_action')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action_participant', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events_action')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action_participant_video', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events_action_participant')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action_participant_video_source_groups', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events_action_participant_video')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action_participant_video_source_groups_sources', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events_action_participant_video_source_groups')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action_participant_presentation', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events_action_participant')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action_participant_presentation_source_groups', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events_action_participant_presentation')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action_participant_presentation_source_groups_sources', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events_action_participant_presentation_source_groups')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action_invite', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events_action')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action_invite_subscription_pricing', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events_action_invite')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action_prev_invite', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events_action')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action_prev_invite_subscription_pricing', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events_action_prev_invite')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action_new_invite', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events_action')->onDelete('cascade');
        });

        Schema::table('tf_admin_log_events_action_new_invite_subscription_pricing', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_admin_log_events_action_new_invite')->onDelete('cascade');
        });

        Schema::table('tf_wallpapers_settings', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_wallpapers')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_notify_settings', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_notify_settings_ios_sound', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_notify_settings')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_notify_settings_android_sound', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_notify_settings')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_notify_settings_other_sound', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_notify_settings')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_notify_settings_stories_ios_sound', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_notify_settings')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_notify_settings_stories_android_sound', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_notify_settings')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_notify_settings_stories_other_sound', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_notify_settings')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_pts', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_entities', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_suggested_post', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_suggested_post_price', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_suggested_post')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_text', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_author', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_items', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_items_text', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_blocks_items')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_caption', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_title', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_rows', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_rows_cells', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_blocks_rows')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_rows_cells_text', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_blocks_rows_cells')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_articles', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_geo', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_photos', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_photos_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_photos')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_photos_sizes_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_photos_sizes')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_photos_video_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_photos')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_photos_video_sizes_background_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_photos_video_sizes')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_documents', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_documents_thumbs', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_documents')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_documents_thumbs_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_documents_thumbs')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_documents_video_thumbs', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_documents')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_documents_video_thumbs_background_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_documents_video_thumbs')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_documents_attributes', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_documents')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_draft_rich_message_documents_attributes_mask_coords', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs_draft_rich_message_documents_attributes')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_folder_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs')->onDelete('cascade');
        });

        Schema::table('tf_dialogs_ttl_period', function (Blueprint $table) {
        $table->foreign(['account_id', 'peer_type', 'peer_id'])->references(['account_id', 'peer_type', 'peer_id'])->on('tf_dialogs')->onDelete('cascade');
        });

        Schema::table('tf_dialog_filters_title', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_dialog_filters')->onDelete('cascade');
        });

        Schema::table('tf_dialog_filters_title_entities', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_dialog_filters_title')->onDelete('cascade');
        });

        Schema::table('tf_dialog_filters_pinned_peers', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_dialog_filters')->onDelete('cascade');
        });

        Schema::table('tf_dialog_filters_include_peers', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_dialog_filters')->onDelete('cascade');
        });

        Schema::table('tf_dialog_filters_exclude_peers', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_dialog_filters')->onDelete('cascade');
        });

        Schema::table('tf_dialog_filters_emoticon', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_dialog_filters')->onDelete('cascade');
        });

        Schema::table('tf_dialog_filters_color', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_dialog_filters')->onDelete('cascade');
        });

        Schema::table('tf_folders_photo', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_folders')->onDelete('cascade');
        });

        Schema::table('tf_messages_from_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_from_boosts_applied', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_from_rank', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_saved_peer_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_fwd_from', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_via_bot_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_via_business_bot_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_guestchat_via_from', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_reply_to', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_reply_to_reply_from', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_reply_to')->onDelete('cascade');
        });

        Schema::table('tf_messages_reply_to_quote_entities', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_reply_to')->onDelete('cascade');
        });

        Schema::table('tf_messages_reply_markup', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_reply_markup_rows', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_reply_markup')->onDelete('cascade');
        });

        Schema::table('tf_messages_reply_markup_rows_buttons', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_reply_markup_rows')->onDelete('cascade');
        });

        Schema::table('tf_messages_reply_markup_rows_buttons_style', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_reply_markup_rows_buttons')->onDelete('cascade');
        });

        Schema::table('tf_messages_reply_markup_rows_buttons_peer_types', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_reply_markup_rows_buttons')->onDelete('cascade');
        });

        Schema::table('tf_messages_reply_markup_rows_buttons_peer_type', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_reply_markup_rows_buttons')->onDelete('cascade');
        });

        Schema::table('tf_messages_reply_markup_rows_buttons_peer_type_user_admin_rights', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_reply_markup_rows_buttons_peer_type')->onDelete('cascade');
        });

        Schema::table('tf_messages_reply_markup_rows_buttons_peer_type_bot_admin_rights', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_reply_markup_rows_buttons_peer_type')->onDelete('cascade');
        });

        Schema::table('tf_messages_entities', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_views', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_forwards', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_replies', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_edit_date', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_post_author', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_grouped_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_reactions', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_reactions_results', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_reactions')->onDelete('cascade');
        });

        Schema::table('tf_messages_reactions_recent_reactions', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_reactions')->onDelete('cascade');
        });

        Schema::table('tf_messages_reactions_top_reactors', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_reactions')->onDelete('cascade');
        });

        Schema::table('tf_messages_restriction_reason', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_ttl_period', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_quick_reply_shortcut_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_effect', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_factcheck', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_factcheck_text', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_factcheck')->onDelete('cascade');
        });

        Schema::table('tf_messages_factcheck_text_entities', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_factcheck_text')->onDelete('cascade');
        });

        Schema::table('tf_messages_report_delivery_until_date', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_paid_message_stars', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_suggested_post', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_suggested_post_price', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_suggested_post')->onDelete('cascade');
        });

        Schema::table('tf_messages_schedule_repeat_period', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_summary_from_language', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_blocks', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_blocks_text', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_blocks_author', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_blocks_items', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_blocks_items_text', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_blocks_items')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_blocks_caption', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_blocks_title', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_blocks_rows', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_blocks_rows_cells', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_blocks_rows')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_blocks_rows_cells_text', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_blocks_rows_cells')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_blocks_articles', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_blocks_geo', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_blocks')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_photos', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_photos_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_photos')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_photos_sizes_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_photos_sizes')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_photos_video_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_photos')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_photos_video_sizes_background_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_photos_video_sizes')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_documents', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_documents_thumbs', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_documents')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_documents_thumbs_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_documents_thumbs')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_documents_video_thumbs', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_documents')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_documents_video_thumbs_background_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_documents_video_thumbs')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_documents_attributes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_documents')->onDelete('cascade');
        });

        Schema::table('tf_messages_rich_message_documents_attributes_mask_coords', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_messages_rich_message_documents_attributes')->onDelete('cascade');
        });

        Schema::table('tf_phone_calls_protocol', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_phone_calls')->onDelete('cascade');
        });

        Schema::table('tf_phone_calls_protocol_library_versions', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_phone_calls_protocol')->onDelete('cascade');
        });

        Schema::table('tf_phone_calls_receive_date', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_phone_calls')->onDelete('cascade');
        });

        Schema::table('tf_saved_reaction_tags_title', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_reaction_tag_id'])->references(['account_id', 'saved_reaction_tag_id'])->on('tf_saved_reaction_tags')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_gift', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_gift_background', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts_gift')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_gift_attributes', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts_gift')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_gift_attributes_rarity', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts_gift_attributes')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_gift_attributes_message', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts_gift_attributes')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_gift_attributes_message_entities', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts_gift_attributes_message')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_gift_resell_amount', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts_gift')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_gift_peer_color', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts_gift')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_gift_peer_color_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts_gift_peer_color')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_gift_peer_color_dark_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts_gift_peer_color')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_from_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_message', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_message_entities', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts_message')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_msg_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_saved_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_convert_stars', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_upgrade_stars', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_can_export_at', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_transfer_stars', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_can_transfer_at', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_can_resell_at', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_collection_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_prepaid_upgrade_hash', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_drop_original_details_stars', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_gift_num', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts')->onDelete('cascade');
        });

        Schema::table('tf_saved_star_gifts_can_craft_at', function (Blueprint $table) {
        $table->foreign(['account_id', 'saved_star_gift_id'])->references(['account_id', 'saved_star_gift_id'])->on('tf_saved_star_gifts')->onDelete('cascade');
        });

        Schema::table('tf_stars_subscriptions_pricing', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_subscriptions')->onDelete('cascade');
        });

        Schema::table('tf_stars_subscriptions_chat_invite_hash', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_subscriptions')->onDelete('cascade');
        });

        Schema::table('tf_stars_subscriptions_title', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_subscriptions')->onDelete('cascade');
        });

        Schema::table('tf_stars_subscriptions_photo', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_subscriptions')->onDelete('cascade');
        });

        Schema::table('tf_stars_subscriptions_photo_attributes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_subscriptions_photo')->onDelete('cascade');
        });

        Schema::table('tf_stars_subscriptions_photo_attributes_mask_coords', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_subscriptions_photo_attributes')->onDelete('cascade');
        });

        Schema::table('tf_stars_subscriptions_invoice_slug', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_subscriptions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_amount', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_peer', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_title', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_description', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_photo', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_photo_attributes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_photo')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_photo_attributes_mask_coords', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_photo_attributes')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_transaction_date', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_transaction_url', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_bot_payload', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_msg_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_geo', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_alt_documents', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_alt_documents_thumbs', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_alt_documents')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_alt_documents_thumbs_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_alt_documents_thumbs')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_alt_documents_video_thumbs', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_alt_documents')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_alt_documents_video_thumbs_background_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_alt_documents_video_thumbs')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_alt_documents_attributes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_alt_documents')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_alt_documents_attributes_mask_coords', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_alt_documents_attributes')->onDelete('cascade');
        });

        Schema::table('tf_web_pages_url', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_web_pages')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_game', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_photo', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_photo_attributes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_photo')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_photo_attributes_mask_coords', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_photo_attributes')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_extended_media', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_extended_media_thumb', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_extended_media')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_extended_media_thumb_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_extended_media_thumb')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_poll', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_poll_question', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_poll')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_poll_question_entities', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_poll_question')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_poll_answers', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_poll')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_poll_answers_text', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_poll_answers')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_poll_answers_text_entities', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_poll_answers_text')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_poll_countries_iso2', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_poll')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_results', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_results_results', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_results')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_results_solution_entities', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media_results')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_game_outcome', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_channels', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_countries_iso2', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_winners', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media')->onDelete('cascade');
        });

        Schema::table('tf_todo_lists_title', function (Blueprint $table) {
        $table->foreign(['account_id', 'todo_list_id'])->references(['account_id', 'todo_list_id'])->on('tf_todo_lists')->onDelete('cascade');
        });

        Schema::table('tf_todo_lists_title_entities', function (Blueprint $table) {
        $table->foreign(['account_id', 'todo_list_id'])->references(['account_id', 'todo_list_id'])->on('tf_todo_lists_title')->onDelete('cascade');
        });

        Schema::table('tf_todo_lists_list', function (Blueprint $table) {
        $table->foreign(['account_id', 'todo_list_id'])->references(['account_id', 'todo_list_id'])->on('tf_todo_lists')->onDelete('cascade');
        });

        Schema::table('tf_todo_lists_list_title', function (Blueprint $table) {
        $table->foreign(['account_id', 'todo_list_id'])->references(['account_id', 'todo_list_id'])->on('tf_todo_lists_list')->onDelete('cascade');
        });

        Schema::table('tf_todo_lists_list_title_entities', function (Blueprint $table) {
        $table->foreign(['account_id', 'todo_list_id'])->references(['account_id', 'todo_list_id'])->on('tf_todo_lists_list_title')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_extended_media_completions', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_extended_media')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_subscription_period', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_giveaway_post_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_stargift', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_stargift_background', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_stargift')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_stargift_attributes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_stargift')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_stargift_attributes_rarity', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_stargift_attributes')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_stargift_attributes_message', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_stargift_attributes')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_stargift_attributes_message_entities', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_stargift_attributes_message')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_stargift_resell_amount', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_stargift')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_stargift_peer_color', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_stargift')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_stargift_peer_color_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_stargift_peer_color')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_stargift_peer_color_dark_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions_stargift_peer_color')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_floodskip_number', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_starref_commission_permille', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_starref_peer', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_starref_amount', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_paid_messages', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_premium_gift_months', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_ads_proceeds_from_date', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_stars_transactions_ads_proceeds_to_date', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_stars_transactions')->onDelete('cascade');
        });

        Schema::table('tf_sticker_sets_installed_date', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_sticker_sets')->onDelete('cascade');
        });

        Schema::table('tf_sticker_sets_thumbs', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_sticker_sets')->onDelete('cascade');
        });

        Schema::table('tf_sticker_sets_thumbs_sizes', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_sticker_sets_thumbs')->onDelete('cascade');
        });

        Schema::table('tf_sticker_sets_thumb_dc_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_sticker_sets')->onDelete('cascade');
        });

        Schema::table('tf_sticker_sets_thumb_version', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_sticker_sets')->onDelete('cascade');
        });

        Schema::table('tf_sticker_sets_thumb_document_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_sticker_sets')->onDelete('cascade');
        });

        Schema::table('tf_themes_settings', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_themes')->onDelete('cascade');
        });

        Schema::table('tf_themes_settings_base_theme', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_themes_settings')->onDelete('cascade');
        });

        Schema::table('tf_themes_settings_message_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_themes_settings')->onDelete('cascade');
        });

        Schema::table('tf_themes_emoticon', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_themes')->onDelete('cascade');
        });

        Schema::table('tf_themes_installs_count', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_themes')->onDelete('cascade');
        });

        Schema::table('tf_todo_items_title', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_todo_items')->onDelete('cascade');
        });

        Schema::table('tf_todo_items_title_entities', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_todo_items_title')->onDelete('cascade');
        });

        Schema::table('tf_users_access_hash', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_first_name', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_last_name', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_username', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_phone', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_photo', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_status', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_bot_info_version', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_restriction_reason', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_bot_inline_placeholder', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_lang_code', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_emoji_status', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_usernames', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_stories_max_id', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_color', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_color_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users_color')->onDelete('cascade');
        });

        Schema::table('tf_users_color_dark_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users_color')->onDelete('cascade');
        });

        Schema::table('tf_users_profile_color', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_profile_color_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users_profile_color')->onDelete('cascade');
        });

        Schema::table('tf_users_profile_color_dark_colors', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users_profile_color')->onDelete('cascade');
        });

        Schema::table('tf_users_bot_active_users', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_bot_verification_icon', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

        Schema::table('tf_users_send_paid_messages_stars', function (Blueprint $table) {
        $table->foreign(['account_id', 'id'])->references(['account_id', 'id'])->on('tf_users')->onDelete('cascade');
        });

    }

    public function down(): void
    {
        Schema::table('tf_attach_menu_bots_icons', function (Blueprint $table) {
        $table->dropForeign('fk_tf_attach_menu_bots_icons_tf_attach_menu_bots_f3018accc3bab1d5');
        });

        Schema::table('tf_documents_attributes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_documents_attributes_tf_documents_ba0ac6d06352b02e');
        });

        Schema::table('tf_documents_attributes_mask_coords', function (Blueprint $table) {
        $table->dropForeign('fk_tf_documents_attributes_mask_coords_tf_documents_attributes_cf91a85c53108066');
        });

        Schema::table('tf_documents_thumbs', function (Blueprint $table) {
        $table->dropForeign('fk_tf_documents_thumbs_tf_documents_4e7ba76267a10080');
        });

        Schema::table('tf_documents_thumbs_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_documents_thumbs_sizes_tf_documents_thumbs_3d54871793961797');
        });

        Schema::table('tf_documents_video_thumbs', function (Blueprint $table) {
        $table->dropForeign('fk_tf_documents_video_thumbs_tf_documents_007d1b750dba49c8');
        });

        Schema::table('tf_documents_video_thumbs_background_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_documents_video_thumbs_background_colors_tf_documents_video_thumbs_937b560f41e060ab');
        });

        Schema::table('tf_attach_menu_bots_icons_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_attach_menu_bots_icons_colors_tf_attach_menu_bots_icons_026f99b15e87245c');
        });

        Schema::table('tf_attach_menu_bots_peer_types', function (Blueprint $table) {
        $table->dropForeign('fk_tf_attach_menu_bots_peer_types_tf_attach_menu_bots_4e09db5cbc0b5ba4');
        });

        Schema::table('tf_photos_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_photos_sizes_tf_photos_94ca1d28d9be9816');
        });

        Schema::table('tf_photos_sizes_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_photos_sizes_sizes_tf_photos_sizes_805533128e041b5d');
        });

        Schema::table('tf_photos_video_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_photos_video_sizes_tf_photos_7ede5eac49ead547');
        });

        Schema::table('tf_photos_video_sizes_background_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_photos_video_sizes_background_colors_tf_photos_video_sizes_d03622af564f3793');
        });

        Schema::table('tf_bot_infos_user_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_infos_user_id_tf_bot_infos_b11d9bf82f09721c');
        });

        Schema::table('tf_bot_infos_description', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_infos_description_tf_bot_infos_81dc91cb626d83d6');
        });

        Schema::table('tf_bot_infos_commands', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_infos_commands_tf_bot_infos_07303f8f19f71b73');
        });

        Schema::table('tf_bot_infos_menu_button', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_infos_menu_button_tf_bot_infos_69b3592ce2ab3c45');
        });

        Schema::table('tf_bot_infos_privacy_policy_url', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_infos_privacy_policy_url_tf_bot_infos_7a12d6e719fbbda0');
        });

        Schema::table('tf_bot_infos_app_settings', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_infos_app_settings_tf_bot_infos_be3067bbfa272744');
        });

        Schema::table('tf_bot_infos_verifier_settings', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_infos_verifier_settings_tf_bot_infos_1287ddfbb772050b');
        });

        Schema::table('tf_bot_inline_results_send_message', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_tf_bot_inline_results_02c7f10aa21c7327');
        });

        Schema::table('tf_bot_inline_results_send_message_entities', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_entities_tf_bot_inline_results_send_message_4f02903b8cc95365');
        });

        Schema::table('tf_bot_inline_results_send_message_reply_markup', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_reply_markup_tf_bot_inline_results_send_message_4b90df4b2ae8475c');
        });

        Schema::table('tf_bot_inline_results_send_message_reply_markup_rows', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_reply_markup_rows_tf_bot_inline_results_send_message_reply_markup_5bfa451c5801bb75');
        });

        Schema::table('tf_bot_inline_results_send_message_reply_markup_rows_buttons', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_reply_markup_rows_buttons_tf_bot_inline_results_send_message_reply_markup_rows_c93b1fddfd450940');
        });

        Schema::table('tf_bot_inline_results_send_message_reply_markup_rows_buttons_style', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_reply_markup_rows_buttons_style_tf_bot_inline_results_send_message_reply_markup_rows_buttons_946141a525f619aa');
        });

        Schema::table('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_types', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_types_tf_bot_inline_results_send_message_reply_markup_rows_buttons_9b5870e1cec956fb');
        });

        Schema::table('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type_tf_bot_inline_results_send_message_reply_markup_rows_buttons_00cf4ca071f9c670');
        });

        Schema::table('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type_user_admin_rights', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type_user_admin_rights_tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type_ec50b36047261811');
        });

        Schema::table('tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type_bot_admin_rights', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type_bot_admin_rights_tf_bot_inline_results_send_message_reply_markup_rows_buttons_peer_type_1c9b86d3d932900d');
        });

        Schema::table('tf_bot_inline_results_send_message_geo', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_geo_tf_bot_inline_results_send_message_93e1fefb614b758a');
        });

        Schema::table('tf_bot_inline_results_send_message_photo', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_photo_tf_bot_inline_results_send_message_37398cbdf7454036');
        });

        Schema::table('tf_bot_inline_results_send_message_photo_attributes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_photo_attributes_tf_bot_inline_results_send_message_photo_71889b72f2706e28');
        });

        Schema::table('tf_bot_inline_results_send_message_photo_attributes_mask_coords', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_photo_attributes_mask_coords_tf_bot_inline_results_send_message_photo_attributes_221859c78b83866c');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_tf_bot_inline_results_send_message_9311c2e1ef63bcf8');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_blocks_tf_bot_inline_results_send_message_rich_message_cc2a4ed07553dada');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_text', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_blocks_text_tf_bot_inline_results_send_message_rich_message_blocks_ab25785c9f0bd14f');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_author', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_blocks_author_tf_bot_inline_results_send_message_rich_message_blocks_81e41293208df2bd');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_items', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_blocks_items_tf_bot_inline_results_send_message_rich_message_blocks_418ca5a7955944a0');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_items_text', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_blocks_items_text_tf_bot_inline_results_send_message_rich_message_blocks_items_b6af038689d2e242');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_caption', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_blocks_caption_tf_bot_inline_results_send_message_rich_message_blocks_20063bdad3c6a09c');
        });

        Schema::table('tf_chats_photo', function (Blueprint $table) {
        $table->dropForeign('fk_tf_chats_photo_tf_chats_1b5a07b7b0961841');
        });

        Schema::table('tf_chats_migrated_to', function (Blueprint $table) {
        $table->dropForeign('fk_tf_chats_migrated_to_tf_chats_850a4b4c4dfc784b');
        });

        Schema::table('tf_chats_admin_rights', function (Blueprint $table) {
        $table->dropForeign('fk_tf_chats_admin_rights_tf_chats_ddbbfbc22958ab64');
        });

        Schema::table('tf_chats_default_banned_rights', function (Blueprint $table) {
        $table->dropForeign('fk_tf_chats_default_banned_rights_tf_chats_f4551240ddb450b8');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_title', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_blocks_title_tf_bot_inline_results_send_message_rich_message_blocks_050fb0b04c4345ab');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_rows', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_blocks_rows_tf_bot_inline_results_send_message_rich_message_blocks_789010b5123e9bef');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_rows_cells', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_blocks_rows_cells_tf_bot_inline_results_send_message_rich_message_blocks_rows_7f077cbc1d5456e9');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_rows_cells_text', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_blocks_rows_cells_text_tf_bot_inline_results_send_message_rich_message_blocks_rows_cells_62bc7d024c5492c9');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_articles', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_blocks_articles_tf_bot_inline_results_send_message_rich_message_blocks_0973b13248f6f6f0');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_blocks_geo', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_blocks_geo_tf_bot_inline_results_send_message_rich_message_blocks_84e0e39126721817');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_photos', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_photos_tf_bot_inline_results_send_message_rich_message_202c79f85fc088ff');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_photos_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_photos_sizes_tf_bot_inline_results_send_message_rich_message_photos_eb0853cb37d1e6bb');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_photos_sizes_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_photos_sizes_sizes_tf_bot_inline_results_send_message_rich_message_photos_sizes_10417a9fa6e99151');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_photos_video_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_photos_video_sizes_tf_bot_inline_results_send_message_rich_message_photos_27e5ff78441e8493');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_photos_video_sizes_background_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_photos_video_sizes_background_colors_tf_bot_inline_results_send_message_rich_message_photos_video_sizes_282cef324cc791c8');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_documents', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_documents_tf_bot_inline_results_send_message_rich_message_0b3cd328c7d0fc4a');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_documents_thumbs', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_documents_thumbs_tf_bot_inline_results_send_message_rich_message_documents_ce47ce22f8a8a34f');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_documents_thumbs_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_documents_thumbs_sizes_tf_bot_inline_results_send_message_rich_message_documents_thumbs_73a5fb1f249a7495');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_documents_video_thumbs', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_documents_video_thumbs_tf_bot_inline_results_send_message_rich_message_documents_9c3fff936031a512');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_documents_video_thumbs_background_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_documents_video_thumbs_background_colors_tf_bot_inline_results_send_message_rich_message_documents_video_thumbs_65703f5e0ce15107');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_documents_attributes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_documents_attributes_tf_bot_inline_results_send_message_rich_message_documents_bad2ef25274e2dea');
        });

        Schema::table('tf_bot_inline_results_send_message_rich_message_documents_attributes_mask_coords', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_send_message_rich_message_documents_attributes_mask_coords_tf_bot_inline_results_send_message_rich_message_documents_attributes_e8161955af35221d');
        });

        Schema::table('tf_bot_inline_results_title', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_title_tf_bot_inline_results_1f7e2d79f2c0a3dd');
        });

        Schema::table('tf_bot_inline_results_description', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_description_tf_bot_inline_results_bd62e752e6a2dc58');
        });

        Schema::table('tf_bot_inline_results_url', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_url_tf_bot_inline_results_dac0b96d04fb55f9');
        });

        Schema::table('tf_bot_inline_results_thumb', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_thumb_tf_bot_inline_results_3855acf3683064e8');
        });

        Schema::table('tf_bot_inline_results_thumb_attributes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_thumb_attributes_tf_bot_inline_results_thumb_17ee745612ee45f3');
        });

        Schema::table('tf_bot_inline_results_thumb_attributes_mask_coords', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_thumb_attributes_mask_coords_tf_bot_inline_results_thumb_attributes_25e6231b96519feb');
        });

        Schema::table('tf_bot_inline_results_content', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_content_tf_bot_inline_results_ccb839123d5f5076');
        });

        Schema::table('tf_bot_inline_results_content_attributes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_content_attributes_tf_bot_inline_results_content_0c608d6fe0f102f9');
        });

        Schema::table('tf_bot_inline_results_content_attributes_mask_coords', function (Blueprint $table) {
        $table->dropForeign('fk_tf_bot_inline_results_content_attributes_mask_coords_tf_bot_inline_results_content_attributes_696db524efc4d9d5');
        });

        Schema::table('tf_business_chat_links_entities', function (Blueprint $table) {
        $table->dropForeign('fk_tf_business_chat_links_entities_tf_business_chat_links_64335a10675aaf3c');
        });

        Schema::table('tf_business_chat_links_title', function (Blueprint $table) {
        $table->dropForeign('fk_tf_business_chat_links_title_tf_business_chat_links_a62ca78462ab3848');
        });

        Schema::table('tf_admin_log_events_action', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_tf_admin_log_events_aca407b4f091ee95');
        });

        Schema::table('tf_messages_service_from_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_service_from_id_tf_messages_service_7c8b6ee6306a75e1');
        });

        Schema::table('tf_messages_service_saved_peer_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_service_saved_peer_id_tf_messages_service_ce84eedb8190b41e');
        });

        Schema::table('tf_messages_service_reply_to', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_service_reply_to_tf_messages_service_7d5c28a2dca4f127');
        });

        Schema::table('tf_messages_service_reply_to_reply_from', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_service_reply_to_reply_from_tf_messages_service_reply_to_caa602421a331404');
        });

        Schema::table('tf_message_medias_ttl_seconds', function (Blueprint $table) {
        $table->dropForeign('fk_tf_message_medias_ttl_seconds_tf_message_medias_accead77df1be6ca');
        });

        Schema::table('tf_messages_service_reply_to_quote_entities', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_service_reply_to_quote_entities_tf_messages_service_reply_to_8657f1875d7a5639');
        });

        Schema::table('tf_messages_service_reactions', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_service_reactions_tf_messages_service_24266be36dc29537');
        });

        Schema::table('tf_messages_service_reactions_results', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_service_reactions_results_tf_messages_service_reactions_790361a62b673d2f');
        });

        Schema::table('tf_messages_service_reactions_recent_reactions', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_service_reactions_recent_reactions_tf_messages_service_reactions_f788f04aad46dc7c');
        });

        Schema::table('tf_messages_service_reactions_top_reactors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_service_reactions_top_reactors_tf_messages_service_reactions_85f584186a07ac47');
        });

        Schema::table('tf_messages_service_ttl_period', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_service_ttl_period_tf_messages_service_be32fc31746f9ba6');
        });

        Schema::table('tf_message_actions_users', function (Blueprint $table) {
        $table->dropForeign('fk_tf_message_actions_users_tf_message_actions_80f584078ec8cba1');
        });

        Schema::table('tf_channel_participants_subscription_until_date', function (Blueprint $table) {
        $table->dropForeign('fk_tf_channel_participants_subscription_until_date_tf_channel_participants_2d8c283954ecf45e');
        });

        Schema::table('tf_channel_participants_rank', function (Blueprint $table) {
        $table->dropForeign('fk_tf_channel_participants_rank_tf_channel_participants_22dbf7e821bcda27');
        });

        Schema::table('tf_admin_log_events_action_prev_banned_rights', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_prev_banned_rights_tf_admin_log_events_action_ee95155cb4f6330d');
        });

        Schema::table('tf_admin_log_events_action_new_banned_rights', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_new_banned_rights_tf_admin_log_events_action_05c4690e0033474a');
        });

        Schema::table('tf_admin_log_events_action_prev_value', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_prev_value_tf_admin_log_events_action_a71a0920eee72185');
        });

        Schema::table('tf_admin_log_events_action_new_value', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_new_value_tf_admin_log_events_action_08d7b29805a1adb3');
        });

        Schema::table('tf_admin_log_events_action_participant', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_participant_tf_admin_log_events_action_92a8998bd005284e');
        });

        Schema::table('tf_admin_log_events_action_participant_video', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_participant_video_tf_admin_log_events_action_participant_fa17ee9aee2b14d9');
        });

        Schema::table('tf_admin_log_events_action_participant_video_source_groups', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_participant_video_source_groups_tf_admin_log_events_action_participant_video_aa05308172c39ddf');
        });

        Schema::table('tf_admin_log_events_action_participant_video_source_groups_sources', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_participant_video_source_groups_sources_tf_admin_log_events_action_participant_video_source_groups_69c47c8c5a156e85');
        });

        Schema::table('tf_admin_log_events_action_participant_presentation', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_participant_presentation_tf_admin_log_events_action_participant_be138736e858ee93');
        });

        Schema::table('tf_admin_log_events_action_participant_presentation_source_groups', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_participant_presentation_source_groups_tf_admin_log_events_action_participant_presentation_63f37de252927c01');
        });

        Schema::table('tf_admin_log_events_action_participant_presentation_source_groups_sources', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_participant_presentation_source_groups_sources_tf_admin_log_events_action_participant_presentation_source_groups_43156603367f89ce');
        });

        Schema::table('tf_admin_log_events_action_invite', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_invite_tf_admin_log_events_action_70c9b5ebee5ede9c');
        });

        Schema::table('tf_admin_log_events_action_invite_subscription_pricing', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_invite_subscription_pricing_tf_admin_log_events_action_invite_da0677bd4e3aa878');
        });

        Schema::table('tf_admin_log_events_action_prev_invite', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_prev_invite_tf_admin_log_events_action_00554499c485c78e');
        });

        Schema::table('tf_admin_log_events_action_prev_invite_subscription_pricing', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_prev_invite_subscription_pricing_tf_admin_log_events_action_prev_invite_6922e7c0f45567f3');
        });

        Schema::table('tf_admin_log_events_action_new_invite', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_new_invite_tf_admin_log_events_action_b78b5ff0fcf0966f');
        });

        Schema::table('tf_admin_log_events_action_new_invite_subscription_pricing', function (Blueprint $table) {
        $table->dropForeign('fk_tf_admin_log_events_action_new_invite_subscription_pricing_tf_admin_log_events_action_new_invite_d54ba986cccd9e84');
        });

        Schema::table('tf_wallpapers_settings', function (Blueprint $table) {
        $table->dropForeign('fk_tf_wallpapers_settings_tf_wallpapers_87ed81619af39e73');
        });

        Schema::table('tf_dialogs_notify_settings', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_notify_settings_tf_dialogs_9f34282a612d0104');
        });

        Schema::table('tf_dialogs_notify_settings_ios_sound', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_notify_settings_ios_sound_tf_dialogs_notify_settings_2dd35c52b884c0db');
        });

        Schema::table('tf_dialogs_notify_settings_android_sound', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_notify_settings_android_sound_tf_dialogs_notify_settings_886c340ed03ac284');
        });

        Schema::table('tf_dialogs_notify_settings_other_sound', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_notify_settings_other_sound_tf_dialogs_notify_settings_e61b120f7b53bd5d');
        });

        Schema::table('tf_dialogs_notify_settings_stories_ios_sound', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_notify_settings_stories_ios_sound_tf_dialogs_notify_settings_e82e81884594a766');
        });

        Schema::table('tf_dialogs_notify_settings_stories_android_sound', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_notify_settings_stories_android_sound_tf_dialogs_notify_settings_ab3005eb89cb1bf5');
        });

        Schema::table('tf_dialogs_notify_settings_stories_other_sound', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_notify_settings_stories_other_sound_tf_dialogs_notify_settings_0b937bf5630d68d0');
        });

        Schema::table('tf_dialogs_pts', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_pts_tf_dialogs_3242589fb31e5d94');
        });

        Schema::table('tf_dialogs_draft', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_tf_dialogs_2ab6f17f68182fff');
        });

        Schema::table('tf_dialogs_draft_entities', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_entities_tf_dialogs_draft_db5953e747ef87a8');
        });

        Schema::table('tf_dialogs_draft_suggested_post', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_suggested_post_tf_dialogs_draft_7a7b1808c4b60862');
        });

        Schema::table('tf_dialogs_draft_suggested_post_price', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_suggested_post_price_tf_dialogs_draft_suggested_post_5802bcb461d2ef9f');
        });

        Schema::table('tf_dialogs_draft_rich_message', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_tf_dialogs_draft_aae21af5c76f3ca4');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_blocks_tf_dialogs_draft_rich_message_fad2fd886315f581');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_text', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_blocks_text_tf_dialogs_draft_rich_message_blocks_dcc6ceff48265c1e');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_author', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_blocks_author_tf_dialogs_draft_rich_message_blocks_5668b4ab43274bc8');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_items', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_blocks_items_tf_dialogs_draft_rich_message_blocks_337683dfe5461295');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_items_text', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_blocks_items_text_tf_dialogs_draft_rich_message_blocks_items_3360f421cf8e456a');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_caption', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_blocks_caption_tf_dialogs_draft_rich_message_blocks_8e90af1548ef6297');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_title', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_blocks_title_tf_dialogs_draft_rich_message_blocks_8f05968a2a89f1fe');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_rows', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_blocks_rows_tf_dialogs_draft_rich_message_blocks_391e24589d194699');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_rows_cells', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_blocks_rows_cells_tf_dialogs_draft_rich_message_blocks_rows_e3e3361b5e9d0c58');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_rows_cells_text', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_blocks_rows_cells_text_tf_dialogs_draft_rich_message_blocks_rows_cells_385aeb3e574f2cd6');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_articles', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_blocks_articles_tf_dialogs_draft_rich_message_blocks_0834c042ef53d3f2');
        });

        Schema::table('tf_dialogs_draft_rich_message_blocks_geo', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_blocks_geo_tf_dialogs_draft_rich_message_blocks_8c456ae62cc8d2f2');
        });

        Schema::table('tf_dialogs_draft_rich_message_photos', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_photos_tf_dialogs_draft_rich_message_faf038b85418e8af');
        });

        Schema::table('tf_dialogs_draft_rich_message_photos_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_photos_sizes_tf_dialogs_draft_rich_message_photos_07b827ace6f15826');
        });

        Schema::table('tf_dialogs_draft_rich_message_photos_sizes_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_photos_sizes_sizes_tf_dialogs_draft_rich_message_photos_sizes_9b91e11ccc7c5248');
        });

        Schema::table('tf_dialogs_draft_rich_message_photos_video_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_photos_video_sizes_tf_dialogs_draft_rich_message_photos_89046ee3b07c31da');
        });

        Schema::table('tf_dialogs_draft_rich_message_photos_video_sizes_background_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_photos_video_sizes_background_colors_tf_dialogs_draft_rich_message_photos_video_sizes_c48a2c6754fcf54b');
        });

        Schema::table('tf_dialogs_draft_rich_message_documents', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_documents_tf_dialogs_draft_rich_message_d1714758867b80dd');
        });

        Schema::table('tf_dialogs_draft_rich_message_documents_thumbs', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_documents_thumbs_tf_dialogs_draft_rich_message_documents_00560a50b750b8f7');
        });

        Schema::table('tf_dialogs_draft_rich_message_documents_thumbs_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_documents_thumbs_sizes_tf_dialogs_draft_rich_message_documents_thumbs_08dfb3cd6721b6f4');
        });

        Schema::table('tf_dialogs_draft_rich_message_documents_video_thumbs', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_documents_video_thumbs_tf_dialogs_draft_rich_message_documents_ddc823f65bfb58af');
        });

        Schema::table('tf_dialogs_draft_rich_message_documents_video_thumbs_background_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_documents_video_thumbs_background_colors_tf_dialogs_draft_rich_message_documents_video_thumbs_072f58f8031356fd');
        });

        Schema::table('tf_dialogs_draft_rich_message_documents_attributes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_documents_attributes_tf_dialogs_draft_rich_message_documents_0f6e31d2442d3a56');
        });

        Schema::table('tf_dialogs_draft_rich_message_documents_attributes_mask_coords', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_draft_rich_message_documents_attributes_mask_coords_tf_dialogs_draft_rich_message_documents_attributes_828fe992772d1f4e');
        });

        Schema::table('tf_dialogs_folder_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_folder_id_tf_dialogs_07b625d7f7e95c0c');
        });

        Schema::table('tf_dialogs_ttl_period', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialogs_ttl_period_tf_dialogs_f454d6c807ae2080');
        });

        Schema::table('tf_dialog_filters_title', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialog_filters_title_tf_dialog_filters_1c8b99afa017f7ca');
        });

        Schema::table('tf_dialog_filters_title_entities', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialog_filters_title_entities_tf_dialog_filters_title_546ea726692bb842');
        });

        Schema::table('tf_dialog_filters_pinned_peers', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialog_filters_pinned_peers_tf_dialog_filters_c07b0a8f13b071ff');
        });

        Schema::table('tf_dialog_filters_include_peers', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialog_filters_include_peers_tf_dialog_filters_6cd59c21ddc6ac18');
        });

        Schema::table('tf_dialog_filters_exclude_peers', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialog_filters_exclude_peers_tf_dialog_filters_675720be5a4810d2');
        });

        Schema::table('tf_dialog_filters_emoticon', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialog_filters_emoticon_tf_dialog_filters_79dfe47dd75d6bba');
        });

        Schema::table('tf_dialog_filters_color', function (Blueprint $table) {
        $table->dropForeign('fk_tf_dialog_filters_color_tf_dialog_filters_1825604e2a14dd11');
        });

        Schema::table('tf_folders_photo', function (Blueprint $table) {
        $table->dropForeign('fk_tf_folders_photo_tf_folders_7058c7a588060453');
        });

        Schema::table('tf_messages_from_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_from_id_tf_messages_c1ec32247f8629bf');
        });

        Schema::table('tf_messages_from_boosts_applied', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_from_boosts_applied_tf_messages_9b20e24328c225d1');
        });

        Schema::table('tf_messages_from_rank', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_from_rank_tf_messages_bed97c842dc5d29a');
        });

        Schema::table('tf_messages_saved_peer_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_saved_peer_id_tf_messages_c570d8e935118ef1');
        });

        Schema::table('tf_messages_fwd_from', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_fwd_from_tf_messages_12ba5dcc6520d5cb');
        });

        Schema::table('tf_messages_via_bot_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_via_bot_id_tf_messages_50df048fea6650a4');
        });

        Schema::table('tf_messages_via_business_bot_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_via_business_bot_id_tf_messages_54aee91adae1691d');
        });

        Schema::table('tf_messages_guestchat_via_from', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_guestchat_via_from_tf_messages_f1b7e793c7698b3f');
        });

        Schema::table('tf_messages_reply_to', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_reply_to_tf_messages_7765c88e40773a64');
        });

        Schema::table('tf_messages_reply_to_reply_from', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_reply_to_reply_from_tf_messages_reply_to_c7eaaede13e583b0');
        });

        Schema::table('tf_messages_reply_to_quote_entities', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_reply_to_quote_entities_tf_messages_reply_to_78869ead485f269d');
        });

        Schema::table('tf_messages_reply_markup', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_reply_markup_tf_messages_bbf5c560c3682766');
        });

        Schema::table('tf_messages_reply_markup_rows', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_reply_markup_rows_tf_messages_reply_markup_c8e474cae601ade2');
        });

        Schema::table('tf_messages_reply_markup_rows_buttons', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_reply_markup_rows_buttons_tf_messages_reply_markup_rows_035b80c36ef7eacc');
        });

        Schema::table('tf_messages_reply_markup_rows_buttons_style', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_reply_markup_rows_buttons_style_tf_messages_reply_markup_rows_buttons_df6ab3e977c54cd3');
        });

        Schema::table('tf_messages_reply_markup_rows_buttons_peer_types', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_reply_markup_rows_buttons_peer_types_tf_messages_reply_markup_rows_buttons_cf313148f1de8c43');
        });

        Schema::table('tf_messages_reply_markup_rows_buttons_peer_type', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_reply_markup_rows_buttons_peer_type_tf_messages_reply_markup_rows_buttons_58192953b04eca12');
        });

        Schema::table('tf_messages_reply_markup_rows_buttons_peer_type_user_admin_rights', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_reply_markup_rows_buttons_peer_type_user_admin_rights_tf_messages_reply_markup_rows_buttons_peer_type_c21c328c1cb401f3');
        });

        Schema::table('tf_messages_reply_markup_rows_buttons_peer_type_bot_admin_rights', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_reply_markup_rows_buttons_peer_type_bot_admin_rights_tf_messages_reply_markup_rows_buttons_peer_type_7a7b43771be15505');
        });

        Schema::table('tf_messages_entities', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_entities_tf_messages_2d4d9397da8a01b0');
        });

        Schema::table('tf_messages_views', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_views_tf_messages_86fd2cab51afa112');
        });

        Schema::table('tf_messages_forwards', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_forwards_tf_messages_9607354dd6a7c88e');
        });

        Schema::table('tf_messages_replies', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_replies_tf_messages_ec726ad1a8c81b5d');
        });

        Schema::table('tf_messages_edit_date', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_edit_date_tf_messages_b6b5b850480d2269');
        });

        Schema::table('tf_messages_post_author', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_post_author_tf_messages_3b6e27a6d77ea569');
        });

        Schema::table('tf_messages_grouped_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_grouped_id_tf_messages_652b9bc1ed311ce0');
        });

        Schema::table('tf_messages_reactions', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_reactions_tf_messages_f1c1c439d8cb6fc2');
        });

        Schema::table('tf_messages_reactions_results', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_reactions_results_tf_messages_reactions_0ef77acad221aedc');
        });

        Schema::table('tf_messages_reactions_recent_reactions', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_reactions_recent_reactions_tf_messages_reactions_baf080c5453164ea');
        });

        Schema::table('tf_messages_reactions_top_reactors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_reactions_top_reactors_tf_messages_reactions_b8dadf0b299eed84');
        });

        Schema::table('tf_messages_restriction_reason', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_restriction_reason_tf_messages_140121ec2ca59615');
        });

        Schema::table('tf_messages_ttl_period', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_ttl_period_tf_messages_5a1a38b9c734e712');
        });

        Schema::table('tf_messages_quick_reply_shortcut_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_quick_reply_shortcut_id_tf_messages_3a6edfde99135c10');
        });

        Schema::table('tf_messages_effect', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_effect_tf_messages_ac910d37079d08e9');
        });

        Schema::table('tf_messages_factcheck', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_factcheck_tf_messages_fa6bd2da1ae8607b');
        });

        Schema::table('tf_messages_factcheck_text', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_factcheck_text_tf_messages_factcheck_7681bef7c836b863');
        });

        Schema::table('tf_messages_factcheck_text_entities', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_factcheck_text_entities_tf_messages_factcheck_text_52461f6c0e0e5de7');
        });

        Schema::table('tf_messages_report_delivery_until_date', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_report_delivery_until_date_tf_messages_c7cf7405b3e16642');
        });

        Schema::table('tf_messages_paid_message_stars', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_paid_message_stars_tf_messages_0388c0b35b82b380');
        });

        Schema::table('tf_messages_suggested_post', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_suggested_post_tf_messages_747834f8d62359ec');
        });

        Schema::table('tf_messages_suggested_post_price', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_suggested_post_price_tf_messages_suggested_post_eb904d012e065012');
        });

        Schema::table('tf_messages_schedule_repeat_period', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_schedule_repeat_period_tf_messages_6ab0bc28a6b0b2df');
        });

        Schema::table('tf_messages_summary_from_language', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_summary_from_language_tf_messages_53e488a18a63959a');
        });

        Schema::table('tf_messages_rich_message', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_tf_messages_fd33fac457a43913');
        });

        Schema::table('tf_messages_rich_message_blocks', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_blocks_tf_messages_rich_message_940cad89905e3d9f');
        });

        Schema::table('tf_messages_rich_message_blocks_text', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_blocks_text_tf_messages_rich_message_blocks_9dc2ade5078b9b14');
        });

        Schema::table('tf_messages_rich_message_blocks_author', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_blocks_author_tf_messages_rich_message_blocks_5766920157941b70');
        });

        Schema::table('tf_messages_rich_message_blocks_items', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_blocks_items_tf_messages_rich_message_blocks_47f059e9597a5376');
        });

        Schema::table('tf_messages_rich_message_blocks_items_text', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_blocks_items_text_tf_messages_rich_message_blocks_items_f19312de277a9fbb');
        });

        Schema::table('tf_messages_rich_message_blocks_caption', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_blocks_caption_tf_messages_rich_message_blocks_c740179f22b90317');
        });

        Schema::table('tf_messages_rich_message_blocks_title', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_blocks_title_tf_messages_rich_message_blocks_f45184efeb7cb6d8');
        });

        Schema::table('tf_messages_rich_message_blocks_rows', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_blocks_rows_tf_messages_rich_message_blocks_57a9ca980013a171');
        });

        Schema::table('tf_messages_rich_message_blocks_rows_cells', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_blocks_rows_cells_tf_messages_rich_message_blocks_rows_c184d12c9b99dc2f');
        });

        Schema::table('tf_messages_rich_message_blocks_rows_cells_text', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_blocks_rows_cells_text_tf_messages_rich_message_blocks_rows_cells_edc4eafdc2312570');
        });

        Schema::table('tf_messages_rich_message_blocks_articles', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_blocks_articles_tf_messages_rich_message_blocks_aa69d54fb7da0373');
        });

        Schema::table('tf_messages_rich_message_blocks_geo', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_blocks_geo_tf_messages_rich_message_blocks_0e41c18bf2541146');
        });

        Schema::table('tf_messages_rich_message_photos', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_photos_tf_messages_rich_message_a5d843c0eb52b5a0');
        });

        Schema::table('tf_messages_rich_message_photos_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_photos_sizes_tf_messages_rich_message_photos_351aba4aef353e13');
        });

        Schema::table('tf_messages_rich_message_photos_sizes_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_photos_sizes_sizes_tf_messages_rich_message_photos_sizes_dfb486897617c196');
        });

        Schema::table('tf_messages_rich_message_photos_video_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_photos_video_sizes_tf_messages_rich_message_photos_9149453a18143956');
        });

        Schema::table('tf_messages_rich_message_photos_video_sizes_background_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_photos_video_sizes_background_colors_tf_messages_rich_message_photos_video_sizes_a62d25149ae13630');
        });

        Schema::table('tf_messages_rich_message_documents', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_documents_tf_messages_rich_message_a147ecf7f29f7b03');
        });

        Schema::table('tf_messages_rich_message_documents_thumbs', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_documents_thumbs_tf_messages_rich_message_documents_9b6f36979186077b');
        });

        Schema::table('tf_messages_rich_message_documents_thumbs_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_documents_thumbs_sizes_tf_messages_rich_message_documents_thumbs_5d74fd70ba8e0a9c');
        });

        Schema::table('tf_messages_rich_message_documents_video_thumbs', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_documents_video_thumbs_tf_messages_rich_message_documents_86cd6e8397da2ece');
        });

        Schema::table('tf_messages_rich_message_documents_video_thumbs_background_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_documents_video_thumbs_background_colors_tf_messages_rich_message_documents_video_thumbs_3c6e4c2067a0557f');
        });

        Schema::table('tf_messages_rich_message_documents_attributes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_documents_attributes_tf_messages_rich_message_documents_ec7d860f56ac3ea3');
        });

        Schema::table('tf_messages_rich_message_documents_attributes_mask_coords', function (Blueprint $table) {
        $table->dropForeign('fk_tf_messages_rich_message_documents_attributes_mask_coords_tf_messages_rich_message_documents_attributes_c33fdd146a6f58c6');
        });

        Schema::table('tf_phone_calls_protocol', function (Blueprint $table) {
        $table->dropForeign('fk_tf_phone_calls_protocol_tf_phone_calls_be7d122a7f6eb7d5');
        });

        Schema::table('tf_phone_calls_protocol_library_versions', function (Blueprint $table) {
        $table->dropForeign('fk_tf_phone_calls_protocol_library_versions_tf_phone_calls_protocol_d0c0f017ef100323');
        });

        Schema::table('tf_phone_calls_receive_date', function (Blueprint $table) {
        $table->dropForeign('fk_tf_phone_calls_receive_date_tf_phone_calls_7c8a750e5cabdec7');
        });

        Schema::table('tf_saved_reaction_tags_title', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_reaction_tags_title_tf_saved_reaction_tags_400839b2aa81f0a0');
        });

        Schema::table('tf_saved_star_gifts_gift', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_gift_tf_saved_star_gifts_e6e0ce08dcd59089');
        });

        Schema::table('tf_saved_star_gifts_gift_background', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_gift_background_tf_saved_star_gifts_gift_8c4a00afc52fc7a7');
        });

        Schema::table('tf_saved_star_gifts_gift_attributes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_gift_attributes_tf_saved_star_gifts_gift_ebe2b2b5ca75ab3b');
        });

        Schema::table('tf_saved_star_gifts_gift_attributes_rarity', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_gift_attributes_rarity_tf_saved_star_gifts_gift_attributes_fc5dc8b4adaa041c');
        });

        Schema::table('tf_saved_star_gifts_gift_attributes_message', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_gift_attributes_message_tf_saved_star_gifts_gift_attributes_770a3d88476769e2');
        });

        Schema::table('tf_saved_star_gifts_gift_attributes_message_entities', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_gift_attributes_message_entities_tf_saved_star_gifts_gift_attributes_message_bf74070f6072ec11');
        });

        Schema::table('tf_saved_star_gifts_gift_resell_amount', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_gift_resell_amount_tf_saved_star_gifts_gift_885edb5ac8778507');
        });

        Schema::table('tf_saved_star_gifts_gift_peer_color', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_gift_peer_color_tf_saved_star_gifts_gift_108a9df7dc2c793b');
        });

        Schema::table('tf_saved_star_gifts_gift_peer_color_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_gift_peer_color_colors_tf_saved_star_gifts_gift_peer_color_fade8c9b11f947fb');
        });

        Schema::table('tf_saved_star_gifts_gift_peer_color_dark_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_gift_peer_color_dark_colors_tf_saved_star_gifts_gift_peer_color_b6443b546c0503df');
        });

        Schema::table('tf_saved_star_gifts_from_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_from_id_tf_saved_star_gifts_85865c266b168c23');
        });

        Schema::table('tf_saved_star_gifts_message', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_message_tf_saved_star_gifts_7db450ecc66bb257');
        });

        Schema::table('tf_saved_star_gifts_message_entities', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_message_entities_tf_saved_star_gifts_message_e9cdc82b65ed5db8');
        });

        Schema::table('tf_saved_star_gifts_msg_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_msg_id_tf_saved_star_gifts_bd66711d2057cdfd');
        });

        Schema::table('tf_saved_star_gifts_saved_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_saved_id_tf_saved_star_gifts_316f7b2f081f8d4b');
        });

        Schema::table('tf_saved_star_gifts_convert_stars', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_convert_stars_tf_saved_star_gifts_7148241aaf492659');
        });

        Schema::table('tf_saved_star_gifts_upgrade_stars', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_upgrade_stars_tf_saved_star_gifts_9acf6b0caf87d031');
        });

        Schema::table('tf_saved_star_gifts_can_export_at', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_can_export_at_tf_saved_star_gifts_df3b05d430062bbd');
        });

        Schema::table('tf_saved_star_gifts_transfer_stars', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_transfer_stars_tf_saved_star_gifts_cfe03b4b476c5add');
        });

        Schema::table('tf_saved_star_gifts_can_transfer_at', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_can_transfer_at_tf_saved_star_gifts_4f232c12aabd7fec');
        });

        Schema::table('tf_saved_star_gifts_can_resell_at', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_can_resell_at_tf_saved_star_gifts_4e98ff26a5ee12c0');
        });

        Schema::table('tf_saved_star_gifts_collection_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_collection_id_tf_saved_star_gifts_159eb5af50dbeef6');
        });

        Schema::table('tf_saved_star_gifts_prepaid_upgrade_hash', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_prepaid_upgrade_hash_tf_saved_star_gifts_2c6935276f6ab2ef');
        });

        Schema::table('tf_saved_star_gifts_drop_original_details_stars', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_drop_original_details_stars_tf_saved_star_gifts_72a657aef68669c6');
        });

        Schema::table('tf_saved_star_gifts_gift_num', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_gift_num_tf_saved_star_gifts_67df26958d8dde42');
        });

        Schema::table('tf_saved_star_gifts_can_craft_at', function (Blueprint $table) {
        $table->dropForeign('fk_tf_saved_star_gifts_can_craft_at_tf_saved_star_gifts_cf914569d82bc63b');
        });

        Schema::table('tf_stars_subscriptions_pricing', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_subscriptions_pricing_tf_stars_subscriptions_4c799420794f9233');
        });

        Schema::table('tf_stars_subscriptions_chat_invite_hash', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_subscriptions_chat_invite_hash_tf_stars_subscriptions_21971d9da1d5b64e');
        });

        Schema::table('tf_stars_subscriptions_title', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_subscriptions_title_tf_stars_subscriptions_3a98f64ccefd2725');
        });

        Schema::table('tf_stars_subscriptions_photo', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_subscriptions_photo_tf_stars_subscriptions_721277287ba3c30c');
        });

        Schema::table('tf_stars_subscriptions_photo_attributes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_subscriptions_photo_attributes_tf_stars_subscriptions_photo_4eea995457622714');
        });

        Schema::table('tf_stars_subscriptions_photo_attributes_mask_coords', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_subscriptions_photo_attributes_mask_coords_tf_stars_subscriptions_photo_attributes_5f75a81435f4f2a6');
        });

        Schema::table('tf_stars_subscriptions_invoice_slug', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_subscriptions_invoice_slug_tf_stars_subscriptions_f5b90dc16caa7d58');
        });

        Schema::table('tf_stars_transactions_amount', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_amount_tf_stars_transactions_52a1e08b993d34e0');
        });

        Schema::table('tf_stars_transactions_peer', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_peer_tf_stars_transactions_71362f66f3dc1df0');
        });

        Schema::table('tf_stars_transactions_title', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_title_tf_stars_transactions_f4339556589c2e06');
        });

        Schema::table('tf_stars_transactions_description', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_description_tf_stars_transactions_c612116a65c4c03b');
        });

        Schema::table('tf_stars_transactions_photo', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_photo_tf_stars_transactions_12f515bc3e00576c');
        });

        Schema::table('tf_stars_transactions_photo_attributes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_photo_attributes_tf_stars_transactions_photo_2f503059d8a20df2');
        });

        Schema::table('tf_stars_transactions_photo_attributes_mask_coords', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_photo_attributes_mask_coords_tf_stars_transactions_photo_attributes_208a730d43b430a9');
        });

        Schema::table('tf_stars_transactions_transaction_date', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_transaction_date_tf_stars_transactions_1e47c629153895ca');
        });

        Schema::table('tf_stars_transactions_transaction_url', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_transaction_url_tf_stars_transactions_61027a6a37eb2e22');
        });

        Schema::table('tf_stars_transactions_bot_payload', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_bot_payload_tf_stars_transactions_f6b26018378080c6');
        });

        Schema::table('tf_stars_transactions_msg_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_msg_id_tf_stars_transactions_190b57b309a5962d');
        });

        Schema::table('tf_stars_transactions_extended_media', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_tf_stars_transactions_79543af05a7dcaf3');
        });

        Schema::table('tf_stars_transactions_extended_media_geo', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_geo_tf_stars_transactions_extended_media_2ea5a36e0063f554');
        });

        Schema::table('tf_stars_transactions_extended_media_alt_documents', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_alt_documents_tf_stars_transactions_extended_media_798892477963eb38');
        });

        Schema::table('tf_stars_transactions_extended_media_alt_documents_thumbs', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_alt_documents_thumbs_tf_stars_transactions_extended_media_alt_documents_049e35807398405c');
        });

        Schema::table('tf_stars_transactions_extended_media_alt_documents_thumbs_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_alt_documents_thumbs_sizes_tf_stars_transactions_extended_media_alt_documents_thumbs_5533cafe457d5f7b');
        });

        Schema::table('tf_stars_transactions_extended_media_alt_documents_video_thumbs', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_alt_documents_video_thumbs_tf_stars_transactions_extended_media_alt_documents_9dbf08764ddb748b');
        });

        Schema::table('tf_stars_transactions_extended_media_alt_documents_video_thumbs_background_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_alt_documents_video_thumbs_background_colors_tf_stars_transactions_extended_media_alt_documents_video_thumbs_f4061c6f0c5a7b58');
        });

        Schema::table('tf_stars_transactions_extended_media_alt_documents_attributes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_alt_documents_attributes_tf_stars_transactions_extended_media_alt_documents_1d6a98049098e396');
        });

        Schema::table('tf_stars_transactions_extended_media_alt_documents_attributes_mask_coords', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_alt_documents_attributes_mask_coords_tf_stars_transactions_extended_media_alt_documents_attributes_01c404763bde4fa8');
        });

        Schema::table('tf_web_pages_url', function (Blueprint $table) {
        $table->dropForeign('fk_tf_web_pages_url_tf_web_pages_212b7eff1728dbf8');
        });

        Schema::table('tf_stars_transactions_extended_media_game', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_game_tf_stars_transactions_extended_media_b89db926665c70ee');
        });

        Schema::table('tf_stars_transactions_extended_media_photo', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_photo_tf_stars_transactions_extended_media_e62d3aa8971f0570');
        });

        Schema::table('tf_stars_transactions_extended_media_photo_attributes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_photo_attributes_tf_stars_transactions_extended_media_photo_9f9254b5f6eee07f');
        });

        Schema::table('tf_stars_transactions_extended_media_photo_attributes_mask_coords', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_photo_attributes_mask_coords_tf_stars_transactions_extended_media_photo_attributes_024395f20b17ed87');
        });

        Schema::table('tf_stars_transactions_extended_media_extended_media', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_extended_media_tf_stars_transactions_extended_media_b85d9d8c2d6b8e99');
        });

        Schema::table('tf_stars_transactions_extended_media_extended_media_thumb', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_extended_media_thumb_tf_stars_transactions_extended_media_extended_media_6465bd17e704c036');
        });

        Schema::table('tf_stars_transactions_extended_media_extended_media_thumb_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_extended_media_thumb_sizes_tf_stars_transactions_extended_media_extended_media_thumb_8d7299ed1299278a');
        });

        Schema::table('tf_stars_transactions_extended_media_poll', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_poll_tf_stars_transactions_extended_media_62cbf30c4e601e7f');
        });

        Schema::table('tf_stars_transactions_extended_media_poll_question', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_poll_question_tf_stars_transactions_extended_media_poll_dac67c14e4745540');
        });

        Schema::table('tf_stars_transactions_extended_media_poll_question_entities', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_poll_question_entities_tf_stars_transactions_extended_media_poll_question_a63ef4bbf6b06080');
        });

        Schema::table('tf_stars_transactions_extended_media_poll_answers', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_poll_answers_tf_stars_transactions_extended_media_poll_dc2059ff735ca47d');
        });

        Schema::table('tf_stars_transactions_extended_media_poll_answers_text', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_poll_answers_text_tf_stars_transactions_extended_media_poll_answers_09c47075c85477f9');
        });

        Schema::table('tf_stars_transactions_extended_media_poll_answers_text_entities', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_poll_answers_text_entities_tf_stars_transactions_extended_media_poll_answers_text_aa291e20faaf43f5');
        });

        Schema::table('tf_stars_transactions_extended_media_poll_countries_iso2', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_poll_countries_iso2_tf_stars_transactions_extended_media_poll_535dccf8e63b7236');
        });

        Schema::table('tf_stars_transactions_extended_media_results', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_results_tf_stars_transactions_extended_media_3aea87d88d16ae15');
        });

        Schema::table('tf_stars_transactions_extended_media_results_results', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_results_results_tf_stars_transactions_extended_media_results_1fb715143e71e6ed');
        });

        Schema::table('tf_stars_transactions_extended_media_results_solution_entities', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_results_solution_entities_tf_stars_transactions_extended_media_results_9fb255ac7bc0143b');
        });

        Schema::table('tf_stars_transactions_extended_media_game_outcome', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_game_outcome_tf_stars_transactions_extended_media_0f6412d36ac7367d');
        });

        Schema::table('tf_stars_transactions_extended_media_channels', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_channels_tf_stars_transactions_extended_media_bee09945360f3c7b');
        });

        Schema::table('tf_stars_transactions_extended_media_countries_iso2', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_countries_iso2_tf_stars_transactions_extended_media_397d4df13d614365');
        });

        Schema::table('tf_stars_transactions_extended_media_winners', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_winners_tf_stars_transactions_extended_media_a5d999c649d58d3c');
        });

        Schema::table('tf_todo_lists_title', function (Blueprint $table) {
        $table->dropForeign('fk_tf_todo_lists_title_tf_todo_lists_6bc7b75421b2511c');
        });

        Schema::table('tf_todo_lists_title_entities', function (Blueprint $table) {
        $table->dropForeign('fk_tf_todo_lists_title_entities_tf_todo_lists_title_5b26537deb87e9b9');
        });

        Schema::table('tf_todo_lists_list', function (Blueprint $table) {
        $table->dropForeign('fk_tf_todo_lists_list_tf_todo_lists_a916d63b258042fb');
        });

        Schema::table('tf_todo_lists_list_title', function (Blueprint $table) {
        $table->dropForeign('fk_tf_todo_lists_list_title_tf_todo_lists_list_a53371bb48fd781f');
        });

        Schema::table('tf_todo_lists_list_title_entities', function (Blueprint $table) {
        $table->dropForeign('fk_tf_todo_lists_list_title_entities_tf_todo_lists_list_title_863cdb9d330fa981');
        });

        Schema::table('tf_stars_transactions_extended_media_completions', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_extended_media_completions_tf_stars_transactions_extended_media_77e0a4dae2b79fc9');
        });

        Schema::table('tf_stars_transactions_subscription_period', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_subscription_period_tf_stars_transactions_0f83c67e8725928c');
        });

        Schema::table('tf_stars_transactions_giveaway_post_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_giveaway_post_id_tf_stars_transactions_9f73dceea793010d');
        });

        Schema::table('tf_stars_transactions_stargift', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_stargift_tf_stars_transactions_b526c3f5b3ad02fe');
        });

        Schema::table('tf_stars_transactions_stargift_background', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_stargift_background_tf_stars_transactions_stargift_769aa82ac7756ea4');
        });

        Schema::table('tf_stars_transactions_stargift_attributes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_stargift_attributes_tf_stars_transactions_stargift_073db374a8e6dd7b');
        });

        Schema::table('tf_stars_transactions_stargift_attributes_rarity', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_stargift_attributes_rarity_tf_stars_transactions_stargift_attributes_155d09b798e65850');
        });

        Schema::table('tf_stars_transactions_stargift_attributes_message', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_stargift_attributes_message_tf_stars_transactions_stargift_attributes_48d020f2abaaa05c');
        });

        Schema::table('tf_stars_transactions_stargift_attributes_message_entities', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_stargift_attributes_message_entities_tf_stars_transactions_stargift_attributes_message_74d02d82f6a3d197');
        });

        Schema::table('tf_stars_transactions_stargift_resell_amount', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_stargift_resell_amount_tf_stars_transactions_stargift_379244c1eb38571d');
        });

        Schema::table('tf_stars_transactions_stargift_peer_color', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_stargift_peer_color_tf_stars_transactions_stargift_c5e078baec4869de');
        });

        Schema::table('tf_stars_transactions_stargift_peer_color_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_stargift_peer_color_colors_tf_stars_transactions_stargift_peer_color_49eeb5cd7b7daf3e');
        });

        Schema::table('tf_stars_transactions_stargift_peer_color_dark_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_stargift_peer_color_dark_colors_tf_stars_transactions_stargift_peer_color_819ae03b6c554b1f');
        });

        Schema::table('tf_stars_transactions_floodskip_number', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_floodskip_number_tf_stars_transactions_80cd65d004a56600');
        });

        Schema::table('tf_stars_transactions_starref_commission_permille', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_starref_commission_permille_tf_stars_transactions_a24c93e65904b58d');
        });

        Schema::table('tf_stars_transactions_starref_peer', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_starref_peer_tf_stars_transactions_64d9330c44c897b1');
        });

        Schema::table('tf_stars_transactions_starref_amount', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_starref_amount_tf_stars_transactions_59a91e731ffd042a');
        });

        Schema::table('tf_stars_transactions_paid_messages', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_paid_messages_tf_stars_transactions_147e49bc439fde00');
        });

        Schema::table('tf_stars_transactions_premium_gift_months', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_premium_gift_months_tf_stars_transactions_4842d2ab8f9227e1');
        });

        Schema::table('tf_stars_transactions_ads_proceeds_from_date', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_ads_proceeds_from_date_tf_stars_transactions_14931241cc60c47d');
        });

        Schema::table('tf_stars_transactions_ads_proceeds_to_date', function (Blueprint $table) {
        $table->dropForeign('fk_tf_stars_transactions_ads_proceeds_to_date_tf_stars_transactions_02b3b8ea4e7dc04e');
        });

        Schema::table('tf_sticker_sets_installed_date', function (Blueprint $table) {
        $table->dropForeign('fk_tf_sticker_sets_installed_date_tf_sticker_sets_8f3b2a20063cd185');
        });

        Schema::table('tf_sticker_sets_thumbs', function (Blueprint $table) {
        $table->dropForeign('fk_tf_sticker_sets_thumbs_tf_sticker_sets_772d67ac20be26d5');
        });

        Schema::table('tf_sticker_sets_thumbs_sizes', function (Blueprint $table) {
        $table->dropForeign('fk_tf_sticker_sets_thumbs_sizes_tf_sticker_sets_thumbs_7a60bc079f76722c');
        });

        Schema::table('tf_sticker_sets_thumb_dc_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_sticker_sets_thumb_dc_id_tf_sticker_sets_b2471d21c6702ca8');
        });

        Schema::table('tf_sticker_sets_thumb_version', function (Blueprint $table) {
        $table->dropForeign('fk_tf_sticker_sets_thumb_version_tf_sticker_sets_d8192876e20062a0');
        });

        Schema::table('tf_sticker_sets_thumb_document_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_sticker_sets_thumb_document_id_tf_sticker_sets_c9199df64d94a4e9');
        });

        Schema::table('tf_themes_settings', function (Blueprint $table) {
        $table->dropForeign('fk_tf_themes_settings_tf_themes_11bf76f41b63bb7b');
        });

        Schema::table('tf_themes_settings_base_theme', function (Blueprint $table) {
        $table->dropForeign('fk_tf_themes_settings_base_theme_tf_themes_settings_8ad63c738b7588ef');
        });

        Schema::table('tf_themes_settings_message_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_themes_settings_message_colors_tf_themes_settings_27c4baa6c17c47b5');
        });

        Schema::table('tf_themes_emoticon', function (Blueprint $table) {
        $table->dropForeign('fk_tf_themes_emoticon_tf_themes_3174caf685a82f35');
        });

        Schema::table('tf_themes_installs_count', function (Blueprint $table) {
        $table->dropForeign('fk_tf_themes_installs_count_tf_themes_9d421f64e7f20a52');
        });

        Schema::table('tf_todo_items_title', function (Blueprint $table) {
        $table->dropForeign('fk_tf_todo_items_title_tf_todo_items_8475c855d0fdf582');
        });

        Schema::table('tf_todo_items_title_entities', function (Blueprint $table) {
        $table->dropForeign('fk_tf_todo_items_title_entities_tf_todo_items_title_2d64060ceac7e8cb');
        });

        Schema::table('tf_users_access_hash', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_access_hash_tf_users_34d643a609c14165');
        });

        Schema::table('tf_users_first_name', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_first_name_tf_users_08f44348754bec7d');
        });

        Schema::table('tf_users_last_name', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_last_name_tf_users_31aafa557810c057');
        });

        Schema::table('tf_users_username', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_username_tf_users_bb032e11447c2a79');
        });

        Schema::table('tf_users_phone', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_phone_tf_users_ef86d6bdab6d72fa');
        });

        Schema::table('tf_users_photo', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_photo_tf_users_a31f82b014e6bdcf');
        });

        Schema::table('tf_users_status', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_status_tf_users_7d283ea0ef0f349c');
        });

        Schema::table('tf_users_bot_info_version', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_bot_info_version_tf_users_b036eaab68a1c65b');
        });

        Schema::table('tf_users_restriction_reason', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_restriction_reason_tf_users_4935d34b0364d612');
        });

        Schema::table('tf_users_bot_inline_placeholder', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_bot_inline_placeholder_tf_users_b88565120279c912');
        });

        Schema::table('tf_users_lang_code', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_lang_code_tf_users_e8f403531433897c');
        });

        Schema::table('tf_users_emoji_status', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_emoji_status_tf_users_15d775573c02efef');
        });

        Schema::table('tf_users_usernames', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_usernames_tf_users_aeac5ab6171c6024');
        });

        Schema::table('tf_users_stories_max_id', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_stories_max_id_tf_users_fec7b3c80ed4895a');
        });

        Schema::table('tf_users_color', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_color_tf_users_682bfa4ee2091cf9');
        });

        Schema::table('tf_users_color_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_color_colors_tf_users_color_806884c4d5989371');
        });

        Schema::table('tf_users_color_dark_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_color_dark_colors_tf_users_color_38f4a97e6f225889');
        });

        Schema::table('tf_users_profile_color', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_profile_color_tf_users_2f57cee59043e244');
        });

        Schema::table('tf_users_profile_color_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_profile_color_colors_tf_users_profile_color_d4653545c9faae00');
        });

        Schema::table('tf_users_profile_color_dark_colors', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_profile_color_dark_colors_tf_users_profile_color_e18b0260ebf6d9dc');
        });

        Schema::table('tf_users_bot_active_users', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_bot_active_users_tf_users_a4c1aa2985af2a3d');
        });

        Schema::table('tf_users_bot_verification_icon', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_bot_verification_icon_tf_users_d44a268491da6383');
        });

        Schema::table('tf_users_send_paid_messages_stars', function (Blueprint $table) {
        $table->dropForeign('fk_tf_users_send_paid_messages_stars_tf_users_95b18b94eab19463');
        });

    }
};
