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
        Schema::create('tl_input_photo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_2c26611222e7fd908685cfbe');
            $table->index('account_id', 'ix_a75e78b4f2e224fca3bc6959');
        });
        Schema::create('tl_input_photo_input_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_photo')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->binary('file_reference');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9345b0f204d4c994ff3f7039');
            $table->unique(['account_id', 'tl_id'], 'ux_b9f500a017ef0f9b90a1');
        });
        Schema::create('tl_input_photo_input_photo_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_photo')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6cf82f9b6e6dcdafe36fbee3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_photo_input_photo_empty');
        Schema::dropIfExists('tl_input_photo_input_photo');
        Schema::dropIfExists('tl_input_photo');
    }
};
