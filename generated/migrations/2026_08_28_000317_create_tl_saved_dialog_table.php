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
            $table->index('constructor_id', 'ix_571bd89b09ccf3faffe06ffb');
            $table->index('account_id', 'ix_c8ae2a9391915342410f5ba5');
        });
        Schema::create('tl_saved_dialog_mono_forum_dialog', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_saved_dialog')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('unread_mark')->default(false);
            $table->boolean('nopaid_messages_exception')->default(false);
            $table->bigInteger('peer');
            $table->index('peer', 'ix_085ac455c636962e9147969e');
            $table->integer('top_message');
            $table->integer('read_inbox_max_id');
            $table->integer('read_outbox_max_id');
            $table->integer('unread_count');
            $table->integer('unread_reactions_count');
            $table->uuid('draft')->nullable();
            $table->index('draft', 'ix_818f0fe659640f8dd561b6ff');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e620597d0f952a7bc381a458');
        });
        Schema::create('tl_saved_dialog_saved_dialog', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_saved_dialog')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('pinned')->default(false);
            $table->bigInteger('peer');
            $table->index('peer', 'ix_059838e591000e50d81e1a95');
            $table->integer('top_message');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_230126b07ff531a407a0bdd2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_saved_dialog_saved_dialog');
        Schema::dropIfExists('tl_saved_dialog_mono_forum_dialog');
        Schema::dropIfExists('tl_saved_dialog');
    }
};
