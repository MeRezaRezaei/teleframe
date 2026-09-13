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
        Schema::create('tf_admin_log_events', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('date')->unsigned();
        $table->bigInteger('user_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_admin_log_events_action', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->text('prev_value');
        $table->text('new_value');
        $table->boolean('join_muted')->default(false);
        $table->boolean('via_chatlist')->default(false);
        $table->bigInteger('approved_by')->unsigned();
        $table->bigInteger('user_id')->unsigned();
        $table->text('prev_rank');
        $table->text('new_rank');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_service', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->integer('id')->unsigned();
        $table->unsignedTinyInteger('peer_id_type');
        $table->bigInteger('peer_id_id')->unsigned();
        $table->integer('date')->unsigned();
        $table->boolean('out')->default(false);
        $table->boolean('mentioned')->default(false);
        $table->boolean('media_unread')->default(false);
        $table->boolean('reactions_are_possible')->default(false);
        $table->boolean('silent')->default(false);
        $table->boolean('post')->default(false);
        $table->boolean('legacy')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_service_from_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('from_id_type');
        $table->bigInteger('from_id_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_service_saved_peer_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('saved_peer_id_type');
        $table->bigInteger('saved_peer_id_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_service_reply_to', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->boolean('reply_to_scheduled')->default(false);
        $table->boolean('forum_topic')->default(false);
        $table->boolean('quote')->default(false);
        $table->boolean('reply_to_ephemeral')->default(false);
        $table->integer('reply_to_msg_id')->unsigned();
        $table->unsignedTinyInteger('reply_to_peer_id_type');
        $table->bigInteger('reply_to_peer_id_id')->unsigned();
        $table->integer('reply_to_top_id')->unsigned();
        $table->text('quote_text');
        $table->integer('quote_offset')->unsigned();
        $table->integer('todo_item_id')->unsigned();
        $table->text('poll_option');
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->integer('story_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_service_reply_to_reply_from', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('imported')->default(false);
        $table->boolean('saved_out')->default(false);
        $table->unsignedTinyInteger('from_id_type');
        $table->bigInteger('from_id_id')->unsigned();
        $table->text('from_name');
        $table->integer('date')->unsigned();
        $table->integer('channel_post')->unsigned();
        $table->text('post_author');
        $table->unsignedTinyInteger('saved_from_peer_type');
        $table->bigInteger('saved_from_peer_id')->unsigned();
        $table->integer('saved_from_msg_id')->unsigned();
        $table->unsignedTinyInteger('saved_from_id_type');
        $table->bigInteger('saved_from_id_id')->unsigned();
        $table->text('saved_from_name');
        $table->integer('saved_date')->unsigned();
        $table->text('psa_type');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_message_medias', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('message_media_id')->unsigned();
        $table->text('constructor');
        $table->boolean('spoiler')->default(false);
        $table->boolean('live_photo')->default(false);
        $table->primary(['account_id', 'message_media_id']);
        });

        Schema::create('tf_message_medias_ttl_seconds', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('message_media_id')->unsigned();
        $table->integer('ttl_seconds')->unsigned();
        $table->primary(['account_id', 'message_media_id']);
        });

        Schema::create('tf_messages_service_reply_to_quote_entities', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->integer('offset')->unsigned();
        $table->integer('length')->unsigned();
        $table->text('language');
        $table->text('url');
        $table->bigInteger('user_id')->unsigned();
        $table->bigInteger('document_id')->unsigned();
        $table->boolean('collapsed')->default(false);
        $table->boolean('relative')->default(false);
        $table->boolean('short_time')->default(false);
        $table->boolean('long_time')->default(false);
        $table->boolean('short_date')->default(false);
        $table->boolean('long_date')->default(false);
        $table->boolean('day_of_week')->default(false);
        $table->integer('date')->unsigned();
        $table->text('old_text');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_service_reactions', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('min')->default(false);
        $table->boolean('can_see_list')->default(false);
        $table->boolean('reactions_as_tags')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_service_reactions_results', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('chosen_order')->unsigned();
        $table->integer('count')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_reactions', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('reaction_id')->unsigned();
        $table->text('constructor');
        $table->text('emoticon');
        $table->primary(['account_id', 'reaction_id']);
        });

        Schema::create('tf_messages_service_reactions_recent_reactions', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->boolean('big')->default(false);
        $table->boolean('unread')->default(false);
        $table->boolean('my')->default(false);
        $table->unsignedTinyInteger('peer_id_type');
        $table->bigInteger('peer_id_id')->unsigned();
        $table->integer('date')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_service_reactions_top_reactors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->boolean('top')->default(false);
        $table->boolean('my')->default(false);
        $table->boolean('anonymous')->default(false);
        $table->unsignedTinyInteger('peer_id_type');
        $table->bigInteger('peer_id_id')->unsigned();
        $table->integer('count')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_service_ttl_period', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('ttl_period')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_message_actions', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('message_action_id')->unsigned();
        $table->text('constructor');
        $table->text('title');
        $table->primary(['account_id', 'message_action_id']);
        });

        Schema::create('tf_message_actions_users', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('message_action_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->bigInteger('value')->unsigned();
        $table->primary(['account_id', 'message_action_id', 'position']);
        });

        Schema::create('tf_channel_participants', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('user_id')->unsigned();
        $table->text('constructor');
        $table->integer('date')->unsigned();
        $table->primary(['account_id', 'user_id']);
        });

        Schema::create('tf_channel_participants_subscription_until_date', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('user_id')->unsigned();
        $table->integer('subscription_until_date')->unsigned();
        $table->primary(['account_id', 'user_id']);
        });

        Schema::create('tf_channel_participants_rank', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('user_id')->unsigned();
        $table->text('rank');
        $table->primary(['account_id', 'user_id']);
        });

        Schema::create('tf_admin_log_events_action_prev_banned_rights', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('view_messages')->default(false);
        $table->boolean('send_messages')->default(false);
        $table->boolean('send_media')->default(false);
        $table->boolean('send_stickers')->default(false);
        $table->boolean('send_gifs')->default(false);
        $table->boolean('send_games')->default(false);
        $table->boolean('send_inline')->default(false);
        $table->boolean('embed_links')->default(false);
        $table->boolean('send_polls')->default(false);
        $table->boolean('change_info')->default(false);
        $table->boolean('invite_users')->default(false);
        $table->boolean('pin_messages')->default(false);
        $table->boolean('manage_topics')->default(false);
        $table->boolean('send_photos')->default(false);
        $table->boolean('send_videos')->default(false);
        $table->boolean('send_roundvideos')->default(false);
        $table->boolean('send_audios')->default(false);
        $table->boolean('send_voices')->default(false);
        $table->boolean('send_docs')->default(false);
        $table->boolean('send_plain')->default(false);
        $table->boolean('edit_rank')->default(false);
        $table->boolean('send_reactions')->default(false);
        $table->integer('until_date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_admin_log_events_action_new_banned_rights', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('view_messages')->default(false);
        $table->boolean('send_messages')->default(false);
        $table->boolean('send_media')->default(false);
        $table->boolean('send_stickers')->default(false);
        $table->boolean('send_gifs')->default(false);
        $table->boolean('send_games')->default(false);
        $table->boolean('send_inline')->default(false);
        $table->boolean('embed_links')->default(false);
        $table->boolean('send_polls')->default(false);
        $table->boolean('change_info')->default(false);
        $table->boolean('invite_users')->default(false);
        $table->boolean('pin_messages')->default(false);
        $table->boolean('manage_topics')->default(false);
        $table->boolean('send_photos')->default(false);
        $table->boolean('send_videos')->default(false);
        $table->boolean('send_roundvideos')->default(false);
        $table->boolean('send_audios')->default(false);
        $table->boolean('send_voices')->default(false);
        $table->boolean('send_docs')->default(false);
        $table->boolean('send_plain')->default(false);
        $table->boolean('edit_rank')->default(false);
        $table->boolean('send_reactions')->default(false);
        $table->integer('until_date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_admin_log_events_action_prev_value', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('value');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_admin_log_events_action_new_value', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('value');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_admin_log_events_action_participant', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('muted')->default(false);
        $table->boolean('left')->default(false);
        $table->boolean('can_self_unmute')->default(false);
        $table->boolean('just_joined')->default(false);
        $table->boolean('versioned')->default(false);
        $table->boolean('min')->default(false);
        $table->boolean('muted_by_you')->default(false);
        $table->boolean('volume_by_admin')->default(false);
        $table->boolean('self')->default(false);
        $table->boolean('video_joined')->default(false);
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->integer('date')->unsigned();
        $table->integer('active_date')->unsigned();
        $table->integer('source')->unsigned();
        $table->integer('volume')->unsigned();
        $table->text('about');
        $table->bigInteger('raise_hand_rating')->unsigned();
        $table->bigInteger('paid_stars_total')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_admin_log_events_action_participant_video', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('paused')->default(false);
        $table->text('endpoint');
        $table->integer('audio_source')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_admin_log_events_action_participant_video_source_groups', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('semantics');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_admin_log_events_action_participant_video_source_groups_sources', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_admin_log_events_action_participant_presentation', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('paused')->default(false);
        $table->text('endpoint');
        $table->integer('audio_source')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_admin_log_events_action_participant_presentation_source_groups', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('semantics');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_admin_log_events_action_participant_presentation_source_groups_sources', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_admin_log_events_action_invite', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->boolean('revoked')->default(false);
        $table->boolean('permanent')->default(false);
        $table->boolean('request_needed')->default(false);
        $table->text('link');
        $table->bigInteger('admin_id')->unsigned();
        $table->integer('date')->unsigned();
        $table->integer('start_date')->unsigned();
        $table->integer('expire_date')->unsigned();
        $table->integer('usage_limit')->unsigned();
        $table->integer('usage')->unsigned();
        $table->integer('requested')->unsigned();
        $table->integer('subscription_expired')->unsigned();
        $table->text('title');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_admin_log_events_action_invite_subscription_pricing', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('period')->unsigned();
        $table->bigInteger('amount')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_admin_log_events_action_prev_invite', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->boolean('revoked')->default(false);
        $table->boolean('permanent')->default(false);
        $table->boolean('request_needed')->default(false);
        $table->text('link');
        $table->bigInteger('admin_id')->unsigned();
        $table->integer('date')->unsigned();
        $table->integer('start_date')->unsigned();
        $table->integer('expire_date')->unsigned();
        $table->integer('usage_limit')->unsigned();
        $table->integer('usage')->unsigned();
        $table->integer('requested')->unsigned();
        $table->integer('subscription_expired')->unsigned();
        $table->text('title');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_admin_log_events_action_prev_invite_subscription_pricing', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('period')->unsigned();
        $table->bigInteger('amount')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_admin_log_events_action_new_invite', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->boolean('revoked')->default(false);
        $table->boolean('permanent')->default(false);
        $table->boolean('request_needed')->default(false);
        $table->text('link');
        $table->bigInteger('admin_id')->unsigned();
        $table->integer('date')->unsigned();
        $table->integer('start_date')->unsigned();
        $table->integer('expire_date')->unsigned();
        $table->integer('usage_limit')->unsigned();
        $table->integer('usage')->unsigned();
        $table->integer('requested')->unsigned();
        $table->integer('subscription_expired')->unsigned();
        $table->text('title');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_admin_log_events_action_new_invite_subscription_pricing', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('period')->unsigned();
        $table->bigInteger('amount')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_forum_topics', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->integer('id')->unsigned();
        $table->text('constructor');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_wallpapers', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->bigInteger('access_hash')->unsigned();
        $table->text('slug');
        $table->boolean('creator')->default(false);
        $table->boolean('default')->default(false);
        $table->boolean('pattern')->default(false);
        $table->boolean('dark')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_wallpapers_settings', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('blur')->default(false);
        $table->boolean('motion')->default(false);
        $table->integer('background_color')->unsigned();
        $table->integer('second_background_color')->unsigned();
        $table->integer('third_background_color')->unsigned();
        $table->integer('fourth_background_color')->unsigned();
        $table->integer('intensity')->unsigned();
        $table->integer('rotation')->unsigned();
        $table->text('emoticon');
        $table->primary(['account_id', 'id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_admin_log_events');

        Schema::dropIfExists('tf_admin_log_events_action');

        Schema::dropIfExists('tf_messages_service');

        Schema::dropIfExists('tf_messages_service_from_id');

        Schema::dropIfExists('tf_messages_service_saved_peer_id');

        Schema::dropIfExists('tf_messages_service_reply_to');

        Schema::dropIfExists('tf_messages_service_reply_to_reply_from');

        Schema::dropIfExists('tf_message_medias');

        Schema::dropIfExists('tf_message_medias_ttl_seconds');

        Schema::dropIfExists('tf_messages_service_reply_to_quote_entities');

        Schema::dropIfExists('tf_messages_service_reactions');

        Schema::dropIfExists('tf_messages_service_reactions_results');

        Schema::dropIfExists('tf_reactions');

        Schema::dropIfExists('tf_messages_service_reactions_recent_reactions');

        Schema::dropIfExists('tf_messages_service_reactions_top_reactors');

        Schema::dropIfExists('tf_messages_service_ttl_period');

        Schema::dropIfExists('tf_message_actions');

        Schema::dropIfExists('tf_message_actions_users');

        Schema::dropIfExists('tf_channel_participants');

        Schema::dropIfExists('tf_channel_participants_subscription_until_date');

        Schema::dropIfExists('tf_channel_participants_rank');

        Schema::dropIfExists('tf_admin_log_events_action_prev_banned_rights');

        Schema::dropIfExists('tf_admin_log_events_action_new_banned_rights');

        Schema::dropIfExists('tf_admin_log_events_action_prev_value');

        Schema::dropIfExists('tf_admin_log_events_action_new_value');

        Schema::dropIfExists('tf_admin_log_events_action_participant');

        Schema::dropIfExists('tf_admin_log_events_action_participant_video');

        Schema::dropIfExists('tf_admin_log_events_action_participant_video_source_groups');

        Schema::dropIfExists('tf_admin_log_events_action_participant_video_source_groups_sources');

        Schema::dropIfExists('tf_admin_log_events_action_participant_presentation');

        Schema::dropIfExists('tf_admin_log_events_action_participant_presentation_source_groups');

        Schema::dropIfExists('tf_admin_log_events_action_participant_presentation_source_groups_sources');

        Schema::dropIfExists('tf_admin_log_events_action_invite');

        Schema::dropIfExists('tf_admin_log_events_action_invite_subscription_pricing');

        Schema::dropIfExists('tf_admin_log_events_action_prev_invite');

        Schema::dropIfExists('tf_admin_log_events_action_prev_invite_subscription_pricing');

        Schema::dropIfExists('tf_admin_log_events_action_new_invite');

        Schema::dropIfExists('tf_admin_log_events_action_new_invite_subscription_pricing');

        Schema::dropIfExists('tf_forum_topics');

        Schema::dropIfExists('tf_wallpapers');

        Schema::dropIfExists('tf_wallpapers_settings');

        Schema::dropIfExists('tf_wallpapers_settings');

        Schema::dropIfExists('tf_wallpapers');

        Schema::dropIfExists('tf_forum_topics');

        Schema::dropIfExists('tf_admin_log_events_action_new_invite_subscription_pricing');

        Schema::dropIfExists('tf_admin_log_events_action_new_invite');

        Schema::dropIfExists('tf_admin_log_events_action_prev_invite_subscription_pricing');

        Schema::dropIfExists('tf_admin_log_events_action_prev_invite');

        Schema::dropIfExists('tf_admin_log_events_action_invite_subscription_pricing');

        Schema::dropIfExists('tf_admin_log_events_action_invite');

        Schema::dropIfExists('tf_admin_log_events_action_participant_presentation_source_groups_sources');

        Schema::dropIfExists('tf_admin_log_events_action_participant_presentation_source_groups');

        Schema::dropIfExists('tf_admin_log_events_action_participant_presentation');

        Schema::dropIfExists('tf_admin_log_events_action_participant_video_source_groups_sources');

        Schema::dropIfExists('tf_admin_log_events_action_participant_video_source_groups');

        Schema::dropIfExists('tf_admin_log_events_action_participant_video');

        Schema::dropIfExists('tf_admin_log_events_action_participant');

        Schema::dropIfExists('tf_admin_log_events_action_new_value');

        Schema::dropIfExists('tf_admin_log_events_action_prev_value');

        Schema::dropIfExists('tf_admin_log_events_action_new_banned_rights');

        Schema::dropIfExists('tf_admin_log_events_action_prev_banned_rights');

        Schema::dropIfExists('tf_channel_participants_rank');

        Schema::dropIfExists('tf_channel_participants_subscription_until_date');

        Schema::dropIfExists('tf_channel_participants');

        Schema::dropIfExists('tf_message_actions_users');

        Schema::dropIfExists('tf_message_actions');

        Schema::dropIfExists('tf_messages_service_ttl_period');

        Schema::dropIfExists('tf_messages_service_reactions_top_reactors');

        Schema::dropIfExists('tf_messages_service_reactions_recent_reactions');

        Schema::dropIfExists('tf_reactions');

        Schema::dropIfExists('tf_messages_service_reactions_results');

        Schema::dropIfExists('tf_messages_service_reactions');

        Schema::dropIfExists('tf_messages_service_reply_to_quote_entities');

        Schema::dropIfExists('tf_message_medias_ttl_seconds');

        Schema::dropIfExists('tf_message_medias');

        Schema::dropIfExists('tf_messages_service_reply_to_reply_from');

        Schema::dropIfExists('tf_messages_service_reply_to');

        Schema::dropIfExists('tf_messages_service_saved_peer_id');

        Schema::dropIfExists('tf_messages_service_from_id');

        Schema::dropIfExists('tf_messages_service');

        Schema::dropIfExists('tf_admin_log_events_action');

        Schema::dropIfExists('tf_admin_log_events');

    }
};
