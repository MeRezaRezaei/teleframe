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
        Schema::create('tl_input_app_event', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_e0aca272bec67c78de85da10');
            $table->index('account_id', 'ix_cdc3979bede257afe2ef4619');
        });
        Schema::create('tl_input_app_event_input_app_event', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_app_event')->cascadeOnDelete();
            $table->double('time');
            $table->text('tl_type');
            $table->bigInteger('peer');
            $table->uuid('data');
            $table->index('data', 'ix_3d8ce4e9e5adc7d9e5d43eff');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5099741446bd0184e494ea72');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_app_event_input_app_event');
        Schema::dropIfExists('tl_input_app_event');
    }
};
