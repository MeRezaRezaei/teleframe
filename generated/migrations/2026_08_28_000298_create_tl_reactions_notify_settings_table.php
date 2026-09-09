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
        Schema::create('tl_reactions_notify_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_67102e139b8666cd5f68e69d');
            $table->index('account_id', 'ix_9a7e813a6fc6beef9a2e332e');
        });
        Schema::create('tl_reactions_notify_settings_reactions_notify_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_reactions_notify_settings')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('messages_notify_from')->nullable();
            $table->index('messages_notify_from', 'ix_0d8b4bb5e56b4cbaa77bd71d');
            $table->uuid('stories_notify_from')->nullable();
            $table->index('stories_notify_from', 'ix_47274fab3b357cff37c6a246');
            $table->uuid('poll_votes_notify_from')->nullable();
            $table->index('poll_votes_notify_from', 'ix_fd8c1910a815767440c8c570');
            $table->uuid('sound');
            $table->index('sound', 'ix_bfb6d701f5ade104452d9682');
            $table->uuid('show_previews');
            $table->index('show_previews', 'ix_aa250187856019f2f2419ed0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_927165e67e7d598e061efbb5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_reactions_notify_settings_reactions_notify_settings');
        Schema::dropIfExists('tl_reactions_notify_settings');
    }
};
