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
        Schema::create('tl_messages_message_reactions_list_message_reactions_list', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('count');
            $table->text('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bd099a0534108d0b80f1a602');
            $table->index('account_id', 'ix_9f07fe20beb3579f4f1caab7');
        });
        Schema::create('tl_messages_message_reactions_list_message_re_c1f5baa534c1', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_message_reactions_list_message_reactions_list', 'id', 'fk_7eccdd77d91adf7c01ace86c')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e5e56f1fedb1c9460a83');
            $table->index('account_id', 'ix_854ff6a7a9cf094cea47a8fc');
        });
        Schema::create('tl_messages_message_reactions_list_message_re_d7d3d8f71641', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_message_reactions_list_message_reactions_list', 'id', 'fk_f11d4e44377b840ee9b4a32d')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_2dfc9e1db51a098041d3');
            $table->index('account_id', 'ix_0f5509e531a622af5a7ca2f5');
        });
        Schema::create('tl_messages_message_reactions_list_message_re_ca57fe0405a5', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_message_reactions_list_message_reactions_list', 'id', 'fk_6ed213f9137a54c2cdce6c51')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9706089f15ad8ec92788');
            $table->index('account_id', 'ix_5986418f746765cc1a7669c4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_message_reactions_list_message_re_ca57fe0405a5');
        Schema::dropIfExists('tl_messages_message_reactions_list_message_re_d7d3d8f71641');
        Schema::dropIfExists('tl_messages_message_reactions_list_message_re_c1f5baa534c1');
        Schema::dropIfExists('tl_messages_message_reactions_list_message_reactions_list');
    }
};
