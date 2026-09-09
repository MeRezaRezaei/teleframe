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
        Schema::create('tl_phone_group_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_1b4188f56143355fda8fdb56');
            $table->index('account_id', 'ix_e6031624059626d63bee5756');
        });
        Schema::create('tl_phone_group_call_group_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_group_call')->cascadeOnDelete();
            $table->uuid('call');
            $table->index('call', 'ix_1682bf2ff2244634f6208663');
            $table->text('participants_next_offset');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6aad5ec4bf19bca5601a97d6');
        });
        Schema::create('tl_phone_group_call_group_call__participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_group_call_group_call')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_eeaf741b291bf7667c2f');
            $table->index('account_id', 'ix_68a2e77ea4407300b95c68c5');
        });
        Schema::create('tl_phone_group_call_group_call__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_group_call_group_call')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d52653beff16aecfd1f8');
            $table->index('account_id', 'ix_bb52711e2df1942fa545d0bf');
        });
        Schema::create('tl_phone_group_call_group_call__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_group_call_group_call')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
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
        Schema::dropIfExists('tl_phone_group_call');
    }
};
