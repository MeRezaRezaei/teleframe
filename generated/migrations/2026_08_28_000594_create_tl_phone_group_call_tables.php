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
        Schema::create('tl_phone_group_call_group_call', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('call')->nullable();
            $table->index('call', 'ix_1682bf2ff2244634f6208663');
            $table->text('participants_next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dac3be845ae89cd798c0ef1b');
            $table->index('account_id', 'ix_6aad5ec4bf19bca5601a97d6');
        });
        Schema::create('tl_phone_group_call_group_call__participants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_aa18cd39ad1af15588db0e74')->references('id')->on('tl_phone_group_call_group_call')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_eeaf741b291bf7667c2f');
            $table->index('account_id', 'ix_68a2e77ea4407300b95c68c5');
        });
        Schema::create('tl_phone_group_call_group_call__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_278b6f49ebda54a4ded95b99')->references('id')->on('tl_phone_group_call_group_call')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d52653beff16aecfd1f8');
            $table->index('account_id', 'ix_bb52711e2df1942fa545d0bf');
        });
        Schema::create('tl_phone_group_call_group_call__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_5a5d5df7c55576b5ae8f00ce')->references('id')->on('tl_phone_group_call_group_call')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_956b6c7c7ee38b4aefdf');
            $table->index('account_id', 'ix_c2ef2b1a6510279de17da507');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_group_call_group_call__users');
        Schema::dropIfExists('tl_phone_group_call_group_call__chats');
        Schema::dropIfExists('tl_phone_group_call_group_call__participants');
        Schema::dropIfExists('tl_phone_group_call_group_call');
    }
};
