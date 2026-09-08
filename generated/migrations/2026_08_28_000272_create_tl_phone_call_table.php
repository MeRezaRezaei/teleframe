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
        Schema::create('tl_phone_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_phone_call_phone_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('p2p_allowed')->default(false);
            $table->boolean('video')->default(false);
            $table->boolean('conference_supported')->default(false);
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->integer('date');
            $table->bigInteger('admin_id');
            $table->bigInteger('participant_id');
            $table->binary('g_a_or_b');
            $table->bigInteger('key_fingerprint');
            $table->uuid('protocol');
            $table->integer('start_date');
            $table->uuid('custom_parameters')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_phone_call_phone_call__connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_call_phone_call')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_4916b96e04170d7891eb');
        });
        Schema::create('tl_phone_call_phone_call_accepted', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('video')->default(false);
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->integer('date');
            $table->bigInteger('admin_id');
            $table->bigInteger('participant_id');
            $table->binary('g_b');
            $table->uuid('protocol');
            $table->timestamps();
        });
        Schema::create('tl_phone_call_phone_call_discarded', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('need_rating')->default(false);
            $table->boolean('need_debug')->default(false);
            $table->boolean('video')->default(false);
            $table->bigInteger('tl_id');
            $table->uuid('reason')->nullable();
            $table->integer('duration')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_phone_call_phone_call_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->timestamps();
        });
        Schema::create('tl_phone_call_phone_call_requested', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('video')->default(false);
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->integer('date');
            $table->bigInteger('admin_id');
            $table->bigInteger('participant_id');
            $table->binary('g_a_hash');
            $table->uuid('protocol');
            $table->timestamps();
        });
        Schema::create('tl_phone_call_phone_call_waiting', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('video')->default(false);
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->integer('date');
            $table->bigInteger('admin_id');
            $table->bigInteger('participant_id');
            $table->uuid('protocol');
            $table->integer('receive_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_call_phone_call_waiting');
        Schema::dropIfExists('tl_phone_call_phone_call_requested');
        Schema::dropIfExists('tl_phone_call_phone_call_empty');
        Schema::dropIfExists('tl_phone_call_phone_call_discarded');
        Schema::dropIfExists('tl_phone_call_phone_call_accepted');
        Schema::dropIfExists('tl_phone_call_phone_call__connections');
        Schema::dropIfExists('tl_phone_call_phone_call');
        Schema::dropIfExists('tl_phone_call');
    }
};
