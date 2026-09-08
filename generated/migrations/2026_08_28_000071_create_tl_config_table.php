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
        Schema::create('tl_config', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_config_config', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_config')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('default_p2p_contacts')->default(false);
            $table->boolean('preload_featured_stickers')->default(false);
            $table->boolean('revoke_pm_inbox')->default(false);
            $table->boolean('blocked_mode')->default(false);
            $table->boolean('force_try_ipv6')->default(false);
            $table->integer('date');
            $table->integer('expires');
            $table->uuid('test_mode');
            $table->integer('this_dc');
            $table->text('dc_txt_domain_name');
            $table->integer('chat_size_max');
            $table->integer('megagroup_size_max');
            $table->integer('forwarded_count_max');
            $table->integer('online_update_period_ms');
            $table->integer('offline_blur_timeout_ms');
            $table->integer('offline_idle_timeout_ms');
            $table->integer('online_cloud_timeout_ms');
            $table->integer('notify_cloud_delay_ms');
            $table->integer('notify_default_delay_ms');
            $table->integer('push_chat_period_ms');
            $table->integer('push_chat_limit');
            $table->integer('edit_time_limit');
            $table->integer('revoke_time_limit');
            $table->integer('revoke_pm_time_limit');
            $table->integer('rating_e_decay');
            $table->integer('stickers_recent_limit');
            $table->integer('channels_read_media_period');
            $table->integer('tmp_sessions')->nullable();
            $table->integer('call_receive_timeout_ms');
            $table->integer('call_ring_timeout_ms');
            $table->integer('call_connect_timeout_ms');
            $table->integer('call_packet_timeout_ms');
            $table->text('me_url_prefix');
            $table->text('autoupdate_url_prefix')->nullable();
            $table->text('gif_search_username')->nullable();
            $table->text('venue_search_username')->nullable();
            $table->text('img_search_username')->nullable();
            $table->text('static_maps_provider')->nullable();
            $table->integer('caption_length_max');
            $table->integer('message_length_max');
            $table->integer('webfile_dc_id');
            $table->text('suggested_lang_code')->nullable();
            $table->integer('lang_pack_version')->nullable();
            $table->integer('base_lang_pack_version')->nullable();
            $table->uuid('reactions_default')->nullable();
            $table->text('autologin_token')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_config_config__dc_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_config_config')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_063d10839b8e85ab2915');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_config_config__dc_options');
        Schema::dropIfExists('tl_config_config');
        Schema::dropIfExists('tl_config');
    }
};
