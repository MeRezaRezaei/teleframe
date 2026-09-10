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
        Schema::create('tl_messages_bot_results_bot_results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('gallery')->default(false);
            $table->bigInteger('query_id')->nullable();
            $table->index('query_id', 'ix_e40c2979d885fe60581cc64e');
            $table->text('next_offset')->nullable();
            $table->bigInteger('switch_pm')->nullable();
            $table->index('switch_pm', 'ix_99f2b04f915a902966c4d28d');
            $table->bigInteger('switch_webview')->nullable();
            $table->index('switch_webview', 'ix_3d6a7461b5ba990095dd7fa2');
            $table->integer('cache_time')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ba68cec54567f92867fac56f');
            $table->index('account_id', 'ix_f9e512e5fa5e305bbc252fcc');
        });
        Schema::create('tl_messages_bot_results_bot_results__results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_bot_results_bot_results', 'id', 'fk_c747c16160ca9ce92a8cebd8')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_59aa67dc147faeaf732c');
            $table->index('account_id', 'ix_13e37ae0b290ad238c7eef85');
        });
        Schema::create('tl_messages_bot_results_bot_results__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_bot_results_bot_results', 'id', 'fk_bd37637eef57b5c20fc9347d')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
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
    }
};
