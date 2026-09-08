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
        Schema::create('tl_input_message_read_metric', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_message_read_metric_input_message_read_metric', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_message_read_metric')->cascadeOnDelete();
            $table->integer('msg_id');
            $table->bigInteger('view_id');
            $table->integer('time_in_view_ms');
            $table->integer('active_time_in_view_ms');
            $table->integer('height_to_viewport_ratio_permille');
            $table->integer('seen_range_ratio_permille');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_message_read_metric_input_message_read_metric');
        Schema::dropIfExists('tl_input_message_read_metric');
    }
};
