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
        Schema::create('tl_channel_admin_log_events_filter', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_channel_admin_log_events_filter_channel_ad_2d07b3f742d8', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_admin_log_events_filter')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('join')->default(false);
            $table->boolean('leave')->default(false);
            $table->boolean('invite')->default(false);
            $table->boolean('ban')->default(false);
            $table->boolean('unban')->default(false);
            $table->boolean('kick')->default(false);
            $table->boolean('unkick')->default(false);
            $table->boolean('promote')->default(false);
            $table->boolean('demote')->default(false);
            $table->boolean('info')->default(false);
            $table->boolean('settings')->default(false);
            $table->boolean('pinned')->default(false);
            $table->boolean('edit')->default(false);
            $table->boolean('delete')->default(false);
            $table->boolean('group_call')->default(false);
            $table->boolean('invites')->default(false);
            $table->boolean('send')->default(false);
            $table->boolean('forums')->default(false);
            $table->boolean('sub_extend')->default(false);
            $table->boolean('edit_rank')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channel_admin_log_events_filter_channel_ad_2d07b3f742d8');
        Schema::dropIfExists('tl_channel_admin_log_events_filter');
    }
};
