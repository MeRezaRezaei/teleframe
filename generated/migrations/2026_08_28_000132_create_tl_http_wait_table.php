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
        Schema::create('tl_http_wait', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_19278e834cbb13b974f45b02');
            $table->index('account_id', 'ix_587c6ab5fbacc2a5d1a55683');
        });
        Schema::create('tl_http_wait_http_wait', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_http_wait')->cascadeOnDelete();
            $table->integer('max_delay');
            $table->integer('wait_after');
            $table->integer('max_wait');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2fba1a9bdf8eeecf4a41de87');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_http_wait_http_wait');
        Schema::dropIfExists('tl_http_wait');
    }
};
