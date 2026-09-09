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
        Schema::create('tl_messages_bot_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b6bcfc2fbbd6466cb2e8c091');
            $table->index('account_id', 'ix_114f010dbbe5d1e3a0bc8004');
        });
        Schema::create('tl_messages_bot_results_bot_results', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_bot_results')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('gallery')->default(false);
            $table->bigInteger('query_id');
            $table->index('query_id', 'ix_e40c2979d885fe60581cc64e');
            $table->text('next_offset')->nullable();
            $table->uuid('switch_pm')->nullable();
            $table->index('switch_pm', 'ix_99f2b04f915a902966c4d28d');
            $table->uuid('switch_webview')->nullable();
            $table->index('switch_webview', 'ix_3d6a7461b5ba990095dd7fa2');
            $table->integer('cache_time');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f9e512e5fa5e305bbc252fcc');
        });
        Schema::create('tl_messages_bot_results_bot_results__results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_bot_results_bot_results')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_59aa67dc147faeaf732c');
            $table->index('account_id', 'ix_13e37ae0b290ad238c7eef85');
        });
        Schema::create('tl_messages_bot_results_bot_results__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_bot_results_bot_results')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_2b05d44d078f3ff9262f');
            $table->index('account_id', 'ix_8392b6398c98c4cae756b4f6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_bot_results_bot_results__users');
        Schema::dropIfExists('tl_messages_bot_results_bot_results__results');
        Schema::dropIfExists('tl_messages_bot_results_bot_results');
        Schema::dropIfExists('tl_messages_bot_results');
    }
};
