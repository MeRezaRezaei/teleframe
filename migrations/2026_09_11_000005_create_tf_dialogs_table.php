<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_dialogs', function (Blueprint $table) {
            $table->bigInteger('peer_id');
            $table->string('peer_type', 32);
            $table->bigInteger('account_id');
            $table->integer('top_message_id')->nullable();
            $table->integer('read_inbox_max_id')->nullable();
            $table->integer('read_outbox_max_id')->nullable();
            $table->integer('unread_count')->default(0);
            $table->integer('unread_mentions_count')->default(0);
            $table->integer('unread_reactions_count')->default(0);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_unread_mark')->default(false);
            $table->integer('folder_id')->nullable();
            $table->integer('pts')->nullable();
            $table->jsonb('tl_data');
            $table->timestamps();

            $table->primary(['peer_id', 'peer_type', 'account_id']);
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_dialogs');
    }
};
