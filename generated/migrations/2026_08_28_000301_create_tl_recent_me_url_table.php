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
        Schema::create('tl_recent_me_url', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_recent_me_url_recent_me_url_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_recent_me_url')->cascadeOnDelete();
            $table->text('url');
            $table->bigInteger('chat_id');
            $table->timestamps();
        });
        Schema::create('tl_recent_me_url_recent_me_url_chat_invite', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_recent_me_url')->cascadeOnDelete();
            $table->text('url');
            $table->uuid('chat_invite');
            $table->timestamps();
        });
        Schema::create('tl_recent_me_url_recent_me_url_sticker_set', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_recent_me_url')->cascadeOnDelete();
            $table->text('url');
            $table->uuid('set');
            $table->timestamps();
        });
        Schema::create('tl_recent_me_url_recent_me_url_unknown', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_recent_me_url')->cascadeOnDelete();
            $table->text('url');
            $table->timestamps();
        });
        Schema::create('tl_recent_me_url_recent_me_url_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_recent_me_url')->cascadeOnDelete();
            $table->text('url');
            $table->bigInteger('user_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_recent_me_url_recent_me_url_user');
        Schema::dropIfExists('tl_recent_me_url_recent_me_url_unknown');
        Schema::dropIfExists('tl_recent_me_url_recent_me_url_sticker_set');
        Schema::dropIfExists('tl_recent_me_url_recent_me_url_chat_invite');
        Schema::dropIfExists('tl_recent_me_url_recent_me_url_chat');
        Schema::dropIfExists('tl_recent_me_url');
    }
};
