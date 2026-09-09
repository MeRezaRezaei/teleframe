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
            $table->index('constructor_id', 'ix_b08f2a9126d12d432de55ae1');
            $table->index('account_id', 'ix_f409ef38aa3041fc180a33e0');
        });
        Schema::create('tl_input_message_read_metric_input_message_read_metric', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_message_read_metric')->cascadeOnDelete();
            $table->integer('msg_id');
            $table->bigInteger('view_id');
            $table->index('view_id', 'ix_a2556ce80657f3518feff345');
            $table->integer('time_in_view_ms');
            $table->integer('active_time_in_view_ms');
            $table->integer('height_to_viewport_ratio_permille');
            $table->integer('seen_range_ratio_permille');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_088f79a071a06a35d3deddd4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_message_read_metric_input_message_read_metric');
        Schema::dropIfExists('tl_input_message_read_metric');
    }
};
