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
        Schema::create('tl_outbox_read_date', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_3b31ada2df50dee6e06f5479');
            $table->index('account_id', 'ix_4af0a9babc602f79f303b062');
        });
        Schema::create('tl_outbox_read_date_outbox_read_date', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_outbox_read_date')->cascadeOnDelete();
            $table->integer('date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9c426bc16f596b0ec91f1a4b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_outbox_read_date_outbox_read_date');
        Schema::dropIfExists('tl_outbox_read_date');
    }
};
