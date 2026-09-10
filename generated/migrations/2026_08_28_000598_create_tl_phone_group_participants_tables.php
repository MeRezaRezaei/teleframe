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
        Schema::create('tl_phone_group_participants_group_participants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('count')->nullable();
            $table->text('next_offset')->nullable();
            $table->integer('version')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f7277169f25c1301f2eaeee8');
            $table->index('account_id', 'ix_12c60758d31f14698bd04f57');
        });
        Schema::create('tl_phone_group_participants_group_participant_aa4634f2fc16', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_phone_group_participants_group_participants', 'id', 'fk_66b205b5a60660c8aca8bbe9')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_bb9801fd6bddc7818111');
            $table->index('account_id', 'ix_7c0644b4f62fe77fa5631664');
        });
        Schema::create('tl_phone_group_participants_group_participants__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_phone_group_participants_group_participants', 'id', 'fk_cec342c6b01697b226aed730')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0dca57df727b6f70b02a');
            $table->index('account_id', 'ix_1a1b31b2a1e1135d617664d3');
        });
        Schema::create('tl_phone_group_participants_group_participants__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_phone_group_participants_group_participants', 'id', 'fk_fffa466fd2938f9eb0c81514')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c7412bfb795ef7d7bf8a');
            $table->index('account_id', 'ix_ed748ede7dae5561a405d8bb');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_group_participants_group_participants__users');
        Schema::dropIfExists('tl_phone_group_participants_group_participants__chats');
        Schema::dropIfExists('tl_phone_group_participants_group_participant_aa4634f2fc16');
        Schema::dropIfExists('tl_phone_group_participants_group_participants');
    }
};
