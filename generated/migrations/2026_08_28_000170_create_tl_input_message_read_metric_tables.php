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
        Schema::create('tl_input_message_read_metric_input_message_read_metric', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('msg_id')->nullable();
            $table->bigInteger('view_id')->nullable();
            $table->index('view_id', 'ix_a2556ce80657f3518feff345');
            $table->integer('time_in_view_ms')->nullable();
            $table->integer('active_time_in_view_ms')->nullable();
            $table->integer('height_to_viewport_ratio_permille')->nullable();
            $table->integer('seen_range_ratio_permille')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4008d4760151135ffcae0363');
            $table->index('account_id', 'ix_088f79a071a06a35d3deddd4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_message_read_metric_input_message_read_metric');
    }
};
