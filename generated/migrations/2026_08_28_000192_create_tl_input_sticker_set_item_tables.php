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
        Schema::create('tl_input_sticker_set_item_input_sticker_set_item', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('document')->nullable();
            $table->index('document', 'ix_cb9c9caf87bc8861bd7d9c50');
            $table->text('emoji')->nullable();
            $table->bigInteger('mask_coords')->nullable();
            $table->index('mask_coords', 'ix_ee7cbccd1d454584bd027741');
            $table->text('keywords')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fe3914b8491307e993748ceb');
            $table->index('account_id', 'ix_32d339a88d65dfb0d1e9cc61');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_sticker_set_item_input_sticker_set_item');
    }
};
