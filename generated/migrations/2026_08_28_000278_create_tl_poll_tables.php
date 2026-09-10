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
        Schema::create('tl_poll_poll', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('flags')->nullable();
            $table->boolean('closed')->default(false);
            $table->boolean('public_voters')->default(false);
            $table->boolean('multiple_choice')->default(false);
            $table->boolean('quiz')->default(false);
            $table->boolean('open_answers')->default(false);
            $table->boolean('revoting_disabled')->default(false);
            $table->boolean('shuffle_answers')->default(false);
            $table->boolean('hide_results_until_close')->default(false);
            $table->boolean('creator')->default(false);
            $table->boolean('subscribers_only')->default(false);
            $table->bigInteger('question')->nullable();
            $table->index('question', 'ix_37474c8b9a76fa817a8afb73');
            $table->integer('close_period')->nullable();
            $table->integer('close_date')->nullable();
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_484d859e5e55b663c4929f81');
            $table->index('account_id', 'ix_1af152a40132e3cd31ab4f9f');
        });
        Schema::create('tl_poll_poll__answers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_4883bac870c92b34d3e89a0d')->references('id')->on('tl_poll_poll')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_07eccdaff6d04c9d4309');
            $table->index('account_id', 'ix_e11426d2860a34756c0a7b9a');
        });
        Schema::create('tl_poll_poll__countries_iso2', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_9242247b0e370bccaf35f287')->references('id')->on('tl_poll_poll')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1fe8b3de7c2aea019aec');
            $table->index('account_id', 'ix_3a02de926e8ab01865d1e8c3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_poll_poll__countries_iso2');
        Schema::dropIfExists('tl_poll_poll__answers');
        Schema::dropIfExists('tl_poll_poll');
    }
};
