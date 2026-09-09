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
        Schema::create('tl_messages_search_results_calendar', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_80b07ded9e846e5411ccc699');
            $table->index('account_id', 'ix_588508186278574567c96829');
        });
        Schema::create('tl_messages_search_results_calendar_search_re_b1534ec0e56f', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_search_results_calendar')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('inexact')->default(false);
            $table->integer('count');
            $table->integer('min_date');
            $table->integer('min_msg_id');
            $table->integer('offset_id_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_143380b68a7118a959e72603');
        });
        Schema::create('tl_messages_search_results_calendar_search_re_7e53ac3da76d', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_search_results_calendar_search_re_b1534ec0e56f')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_df7fea27884baf011acf');
            $table->index('account_id', 'ix_3dc8eed5c052b0a6619afbb3');
        });
        Schema::create('tl_messages_search_results_calendar_search_re_d6395a6e2c2c', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_search_results_calendar_search_re_b1534ec0e56f')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ad225bf3e3138eafef60');
            $table->index('account_id', 'ix_3aadd302b76744919cec508e');
        });
        Schema::create('tl_messages_search_results_calendar_search_re_34cf508acbb5', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_search_results_calendar_search_re_b1534ec0e56f')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d056e203d2aa70026b58');
            $table->index('account_id', 'ix_a9fe9798f26888f692a65445');
        });
        Schema::create('tl_messages_search_results_calendar_search_re_571d121050fb', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_search_results_calendar_search_re_b1534ec0e56f')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ae236c9ab117ac8d37af');
            $table->index('account_id', 'ix_5ffef75e9babea0c1a76bd7b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_search_results_calendar_search_re_571d121050fb');
        Schema::dropIfExists('tl_messages_search_results_calendar_search_re_34cf508acbb5');
        Schema::dropIfExists('tl_messages_search_results_calendar_search_re_d6395a6e2c2c');
        Schema::dropIfExists('tl_messages_search_results_calendar_search_re_7e53ac3da76d');
        Schema::dropIfExists('tl_messages_search_results_calendar_search_re_b1534ec0e56f');
        Schema::dropIfExists('tl_messages_search_results_calendar');
    }
};
