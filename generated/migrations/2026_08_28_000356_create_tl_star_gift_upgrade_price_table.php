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
        Schema::create('tl_star_gift_upgrade_price', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_d29b4a378ac796f3fcb575b3');
            $table->index('account_id', 'ix_f9165c103e4ab95483bb0a23');
        });
        Schema::create('tl_star_gift_upgrade_price_star_gift_upgrade_price', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_upgrade_price')->cascadeOnDelete();
            $table->integer('date');
            $table->bigInteger('upgrade_stars');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fa91aad1bbfaf4c30f41283e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_upgrade_price_star_gift_upgrade_price');
        Schema::dropIfExists('tl_star_gift_upgrade_price');
    }
};
