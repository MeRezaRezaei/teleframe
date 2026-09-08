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
        Schema::create('tl_chat_banned_rights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_chat_banned_rights_chat_banned_rights', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_banned_rights')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
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
            $table->integer('until_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_banned_rights_chat_banned_rights');
        Schema::dropIfExists('tl_chat_banned_rights');
    }
};
