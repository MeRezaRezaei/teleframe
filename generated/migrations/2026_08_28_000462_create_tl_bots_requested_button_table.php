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
        Schema::create('tl_bots_requested_button', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_9364a75294dc7029d5476bb2');
            $table->index('account_id', 'ix_cb31017ba2ec9766ca492da0');
        });
        Schema::create('tl_bots_requested_button_requested_button', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bots_requested_button')->cascadeOnDelete();
            $table->text('webapp_req_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_680be1e963cc890c9d44ecb6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bots_requested_button_requested_button');
        Schema::dropIfExists('tl_bots_requested_button');
    }
};
