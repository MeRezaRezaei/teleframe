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
        Schema::create('tl_dialog_dialog', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('pinned')->default(false);
            $table->boolean('unread_mark')->default(false);
            $table->boolean('view_forum_as_messages')->default(false);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_4bd99aa633deb03936601855');
            $table->integer('top_message')->nullable();
            $table->integer('read_inbox_max_id')->nullable();
            $table->integer('read_outbox_max_id')->nullable();
            $table->integer('unread_count')->nullable();
            $table->integer('unread_mentions_count')->nullable();
            $table->integer('unread_reactions_count')->nullable();
            $table->integer('unread_poll_votes_count')->nullable();
            $table->bigInteger('notify_settings')->nullable();
            $table->index('notify_settings', 'ix_f46dc50931b8fa9a3750a5af');
            $table->integer('pts')->nullable();
            $table->bigInteger('draft')->nullable();
            $table->index('draft', 'ix_4dadc7f2bf9b8cb6e0b836f5');
            $table->integer('folder_id')->nullable();
            $table->integer('ttl_period')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c36fc77682cf4511846423c9');
            $table->index('account_id', 'ix_ca4f6f6bce065d4acf4b8c93');
        });
        Schema::create('tl_dialog_dialog_folder', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('pinned')->default(false);
            $table->bigInteger('folder')->nullable();
            $table->index('folder', 'ix_460e00f778f5d0b29934fb5d');
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_58fcf0e30fccd34f0978387c');
            $table->integer('top_message')->nullable();
            $table->integer('unread_muted_peers_count')->nullable();
            $table->integer('unread_unmuted_peers_count')->nullable();
            $table->integer('unread_muted_messages_count')->nullable();
            $table->integer('unread_unmuted_messages_count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c895b345f44570b8d85716b4');
            $table->index('account_id', 'ix_6ac6b7b180e40010e165edbe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_dialog_dialog_folder');
        Schema::dropIfExists('tl_dialog_dialog');
    }
};
