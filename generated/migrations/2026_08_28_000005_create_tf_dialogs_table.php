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
        Schema::create('tf_dialogs', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->primary('id');
            $table->bigInteger('peer_id');
            $table->text('peer_type');
            $table->bigInteger('account_id');
            $table->integer('top_message_id')->nullable();
            $table->integer('unread_count')->default(0);
            $table->integer('unread_mentions')->default(0);
            $table->boolean('is_pinned')->default(false);
            $table->integer('folder_id')->default(0);
            $table->integer('pts')->nullable();
            $table->jsonb('tl_data');
            $table->timestamps();
            $table->unique(['peer_id', 'account_id'], 'ux_tf_dialogs_scope');
            $table->index('account_id', 'ix_tf_dialogs_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_dialogs');
    }
};
