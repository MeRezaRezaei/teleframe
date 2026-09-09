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
        Schema::create('tl_channels_sponsored_message_report_result', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_9473ba67fee02c6f5bfe30c2');
            $table->index('account_id', 'ix_400f7809cf5d4e237c4cff59');
        });
        Schema::create('tl_channels_sponsored_message_report_result_s_a8bb878b93c6', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channels_sponsored_message_report_result')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_13250a9b9cf4c90400bbc062');
        });
        Schema::create('tl_channels_sponsored_message_report_result_s_90d28813b853', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channels_sponsored_message_report_result')->cascadeOnDelete();
            $table->text('title');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b89df695678723f8f0652b84');
        });
        Schema::create('tl_channels_sponsored_message_report_result_s_4f877f3b1319', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_channels_sponsored_message_report_result_s_90d28813b853')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4e2a8693c9a7059963bd');
            $table->index('account_id', 'ix_2ed5eda5a2a122c000d992bc');
        });
        Schema::create('tl_channels_sponsored_message_report_result_s_0c801a5ac42c', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channels_sponsored_message_report_result')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_566d24e21e5d680fa7184f0b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channels_sponsored_message_report_result_s_0c801a5ac42c');
        Schema::dropIfExists('tl_channels_sponsored_message_report_result_s_4f877f3b1319');
        Schema::dropIfExists('tl_channels_sponsored_message_report_result_s_90d28813b853');
        Schema::dropIfExists('tl_channels_sponsored_message_report_result_s_a8bb878b93c6');
        Schema::dropIfExists('tl_channels_sponsored_message_report_result');
    }
};
