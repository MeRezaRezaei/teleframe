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
        Schema::create('tl_input_collectible', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_e449758057a18b10ec15abc5');
            $table->index('account_id', 'ix_67c4aa8d3bcd06aa89403c8b');
        });
        Schema::create('tl_input_collectible_input_collectible_phone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_collectible')->cascadeOnDelete();
            $table->text('phone');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5569697f8f4954d96f4e163e');
        });
        Schema::create('tl_input_collectible_input_collectible_username', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_collectible')->cascadeOnDelete();
            $table->text('username');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d8aa142a1441a11a0ff427ef');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_collectible_input_collectible_username');
        Schema::dropIfExists('tl_input_collectible_input_collectible_phone');
        Schema::dropIfExists('tl_input_collectible');
    }
};
