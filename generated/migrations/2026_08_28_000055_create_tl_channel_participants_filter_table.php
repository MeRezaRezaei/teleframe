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
        Schema::create('tl_channel_participants_filter', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_channel_participants_filter_channel_participants_admins', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_participants_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_channel_participants_filter_channel_participants_banned', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_participants_filter')->cascadeOnDelete();
            $table->text('q');
            $table->timestamps();
        });
        Schema::create('tl_channel_participants_filter_channel_participants_bots', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_participants_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_channel_participants_filter_channel_partic_c5e6fc6a843c', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_participants_filter')->cascadeOnDelete();
            $table->text('q');
            $table->timestamps();
        });
        Schema::create('tl_channel_participants_filter_channel_participants_kicked', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_participants_filter')->cascadeOnDelete();
            $table->text('q');
            $table->timestamps();
        });
        Schema::create('tl_channel_participants_filter_channel_partic_b9280c888c41', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_participants_filter')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('q')->nullable();
            $table->integer('top_msg_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_channel_participants_filter_channel_participants_recent', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_participants_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_channel_participants_filter_channel_participants_search', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_participants_filter')->cascadeOnDelete();
            $table->text('q');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channel_participants_filter_channel_participants_search');
        Schema::dropIfExists('tl_channel_participants_filter_channel_participants_recent');
        Schema::dropIfExists('tl_channel_participants_filter_channel_partic_b9280c888c41');
        Schema::dropIfExists('tl_channel_participants_filter_channel_participants_kicked');
        Schema::dropIfExists('tl_channel_participants_filter_channel_partic_c5e6fc6a843c');
        Schema::dropIfExists('tl_channel_participants_filter_channel_participants_bots');
        Schema::dropIfExists('tl_channel_participants_filter_channel_participants_banned');
        Schema::dropIfExists('tl_channel_participants_filter_channel_participants_admins');
        Schema::dropIfExists('tl_channel_participants_filter');
    }
};
