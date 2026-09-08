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
        Schema::create('tl_saved_dialog', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_saved_dialog_mono_forum_dialog', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_saved_dialog')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('unread_mark')->default(false);
            $table->boolean('nopaid_messages_exception')->default(false);
            $table->uuid('peer');
            $table->integer('top_message');
            $table->integer('read_inbox_max_id');
            $table->integer('read_outbox_max_id');
            $table->integer('unread_count');
            $table->integer('unread_reactions_count');
            $table->uuid('draft')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_saved_dialog_saved_dialog', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_saved_dialog')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('pinned')->default(false);
            $table->uuid('peer');
            $table->integer('top_message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_saved_dialog_saved_dialog');
        Schema::dropIfExists('tl_saved_dialog_mono_forum_dialog');
        Schema::dropIfExists('tl_saved_dialog');
    }
};
