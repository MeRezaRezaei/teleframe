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
        Schema::create('tl_poll', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_poll_poll', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_poll')->cascadeOnDelete();
            $table->bigInteger('tl_id');
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
            $table->uuid('question');
            $table->integer('close_period')->nullable();
            $table->integer('close_date')->nullable();
            $table->bigInteger('hash');
            $table->timestamps();
        });
        Schema::create('tl_poll_poll__answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_poll_poll')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_07eccdaff6d04c9d4309');
        });
        Schema::create('tl_poll_poll__countries_iso2', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_poll_poll')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_1fe8b3de7c2aea019aec');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_poll_poll__countries_iso2');
        Schema::dropIfExists('tl_poll_poll__answers');
        Schema::dropIfExists('tl_poll_poll');
        Schema::dropIfExists('tl_poll');
    }
};
