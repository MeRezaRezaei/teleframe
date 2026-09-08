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
        Schema::create('tl_messages_message_reactions_list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_message_reactions_list_message_reactions_list', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_message_reactions_list')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('count');
            $table->text('next_offset')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_messages_message_reactions_list_message_re_c1f5baa534c1', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_message_reactions_list_message_reactions_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e5e56f1fedb1c9460a83');
        });
        Schema::create('tl_messages_message_reactions_list_message_re_d7d3d8f71641', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_message_reactions_list_message_reactions_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_2dfc9e1db51a098041d3');
        });
        Schema::create('tl_messages_message_reactions_list_message_re_ca57fe0405a5', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_message_reactions_list_message_reactions_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_9706089f15ad8ec92788');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_message_reactions_list_message_re_ca57fe0405a5');
        Schema::dropIfExists('tl_messages_message_reactions_list_message_re_d7d3d8f71641');
        Schema::dropIfExists('tl_messages_message_reactions_list_message_re_c1f5baa534c1');
        Schema::dropIfExists('tl_messages_message_reactions_list_message_reactions_list');
        Schema::dropIfExists('tl_messages_message_reactions_list');
    }
};
