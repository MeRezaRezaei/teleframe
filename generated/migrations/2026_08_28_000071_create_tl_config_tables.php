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
        Schema::create('tl_config_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('default_p2p_contacts')->default(false);
            $table->boolean('preload_featured_stickers')->default(false);
            $table->boolean('revoke_pm_inbox')->default(false);
            $table->boolean('blocked_mode')->default(false);
            $table->boolean('force_try_ipv6')->default(false);
            $table->integer('date')->nullable();
            $table->integer('expires')->nullable();
            $table->bigInteger('test_mode')->nullable();
            $table->index('test_mode', 'ix_1cfa480ec460cdcceb41d0c0');
            $table->integer('this_dc')->nullable();
            $table->text('dc_txt_domain_name')->nullable();
            $table->integer('chat_size_max')->nullable();
            $table->integer('megagroup_size_max')->nullable();
            $table->integer('forwarded_count_max')->nullable();
            $table->integer('online_update_period_ms')->nullable();
            $table->integer('offline_blur_timeout_ms')->nullable();
            $table->integer('offline_idle_timeout_ms')->nullable();
            $table->integer('online_cloud_timeout_ms')->nullable();
            $table->integer('notify_cloud_delay_ms')->nullable();
            $table->integer('notify_default_delay_ms')->nullable();
            $table->integer('push_chat_period_ms')->nullable();
            $table->integer('push_chat_limit')->nullable();
            $table->integer('edit_time_limit')->nullable();
            $table->integer('revoke_time_limit')->nullable();
            $table->integer('revoke_pm_time_limit')->nullable();
            $table->integer('rating_e_decay')->nullable();
            $table->integer('stickers_recent_limit')->nullable();
            $table->integer('channels_read_media_period')->nullable();
            $table->integer('tmp_sessions')->nullable();
            $table->integer('call_receive_timeout_ms')->nullable();
            $table->integer('call_ring_timeout_ms')->nullable();
            $table->integer('call_connect_timeout_ms')->nullable();
            $table->integer('call_packet_timeout_ms')->nullable();
            $table->text('me_url_prefix')->nullable();
            $table->text('autoupdate_url_prefix')->nullable();
            $table->text('gif_search_username')->nullable();
            $table->text('venue_search_username')->nullable();
            $table->text('img_search_username')->nullable();
            $table->text('static_maps_provider')->nullable();
            $table->integer('caption_length_max')->nullable();
            $table->integer('message_length_max')->nullable();
            $table->integer('webfile_dc_id')->nullable();
            $table->text('suggested_lang_code')->nullable();
            $table->integer('lang_pack_version')->nullable();
            $table->integer('base_lang_pack_version')->nullable();
            $table->bigInteger('reactions_default')->nullable();
            $table->index('reactions_default', 'ix_03f7f4469a879f30b8699ce3');
            $table->text('autologin_token')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0129d2f5582de063d003cd22');
            $table->index('account_id', 'ix_5ab4c4655c20a912cba48c51');
        });
        Schema::create('tl_config_config__dc_options', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_config_config', 'id', 'fk_c390d30e9603ee3bc7ffaf2e')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_063d10839b8e85ab2915');
            $table->index('account_id', 'ix_68f56fe24da331ce8d1f61e6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_config_config__dc_options');
        Schema::dropIfExists('tl_config_config');
    }
};
