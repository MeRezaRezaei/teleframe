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
        Schema::create('tl_saved_dialog_mono_forum_dialog', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('unread_mark')->default(false);
            $table->boolean('nopaid_messages_exception')->default(false);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_085ac455c636962e9147969e');
            $table->integer('top_message')->nullable();
            $table->integer('read_inbox_max_id')->nullable();
            $table->integer('read_outbox_max_id')->nullable();
            $table->integer('unread_count')->nullable();
            $table->integer('unread_reactions_count')->nullable();
            $table->bigInteger('draft')->nullable();
            $table->index('draft', 'ix_818f0fe659640f8dd561b6ff');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_67b707d2df5f1ccbc7d1105e');
            $table->index('account_id', 'ix_e620597d0f952a7bc381a458');
        });
        Schema::create('tl_saved_dialog_saved_dialog', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('pinned')->default(false);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_059838e591000e50d81e1a95');
            $table->integer('top_message')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fa8269a8ef2ebe441364e455');
            $table->index('account_id', 'ix_230126b07ff531a407a0bdd2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_saved_dialog_saved_dialog');
        Schema::dropIfExists('tl_saved_dialog_mono_forum_dialog');
    }
};
