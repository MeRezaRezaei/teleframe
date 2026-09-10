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
        Schema::create('tl_reactions_notify_settings_reactions_notify_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('messages_notify_from')->nullable();
            $table->index('messages_notify_from', 'ix_0d8b4bb5e56b4cbaa77bd71d');
            $table->bigInteger('stories_notify_from')->nullable();
            $table->index('stories_notify_from', 'ix_47274fab3b357cff37c6a246');
            $table->bigInteger('poll_votes_notify_from')->nullable();
            $table->index('poll_votes_notify_from', 'ix_fd8c1910a815767440c8c570');
            $table->bigInteger('sound')->nullable();
            $table->index('sound', 'ix_bfb6d701f5ade104452d9682');
            $table->bigInteger('show_previews')->nullable();
            $table->index('show_previews', 'ix_aa250187856019f2f2419ed0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e5edffc093f9acb68fed6032');
            $table->index('account_id', 'ix_927165e67e7d598e061efbb5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_reactions_notify_settings_reactions_notify_settings');
    }
};
