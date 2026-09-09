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
        Schema::create('tl_input_sticker_set_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_265e439d890b162364981357');
            $table->index('account_id', 'ix_005a6bf1ffb9f5248ff5d5da');
        });
        Schema::create('tl_input_sticker_set_item_input_sticker_set_item', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_sticker_set_item')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('document');
            $table->index('document', 'ix_cb9c9caf87bc8861bd7d9c50');
            $table->text('emoji');
            $table->uuid('mask_coords')->nullable();
            $table->index('mask_coords', 'ix_ee7cbccd1d454584bd027741');
            $table->text('keywords')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_32d339a88d65dfb0d1e9cc61');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_sticker_set_item_input_sticker_set_item');
        Schema::dropIfExists('tl_input_sticker_set_item');
    }
};
