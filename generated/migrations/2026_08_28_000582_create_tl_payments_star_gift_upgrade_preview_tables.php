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
        Schema::create('tl_payments_star_gift_upgrade_preview_star_gi_2469e890a24d', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_31ff27bd72d6878a8b5df042');
            $table->index('account_id', 'ix_f6aa7db1f73814a4bae4c720');
        });
        Schema::create('tl_payments_star_gift_upgrade_preview_star_gi_d382bb48e929', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_ecc66a2b7776c315e83d5c89')->references('id')->on('tl_payments_star_gift_upgrade_preview_star_gi_2469e890a24d')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ac1776527465ac7a21c1');
            $table->index('account_id', 'ix_d116571b4371b94f6d8b1c11');
        });
        Schema::create('tl_payments_star_gift_upgrade_preview_star_gi_7020ccaa5d71', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_7f1fe53257be8cfcdcae68f4')->references('id')->on('tl_payments_star_gift_upgrade_preview_star_gi_2469e890a24d')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_29e63869420f5239412c');
            $table->index('account_id', 'ix_1cca9005fb7659620a87ceb0');
        });
        Schema::create('tl_payments_star_gift_upgrade_preview_star_gi_668a785fa9b7', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_555cc5861caf44c4040f4575')->references('id')->on('tl_payments_star_gift_upgrade_preview_star_gi_2469e890a24d')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_7f75b8c92551f2c7718d');
            $table->index('account_id', 'ix_919c499b86e424a9f163dbe0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_star_gift_upgrade_preview_star_gi_668a785fa9b7');
        Schema::dropIfExists('tl_payments_star_gift_upgrade_preview_star_gi_7020ccaa5d71');
        Schema::dropIfExists('tl_payments_star_gift_upgrade_preview_star_gi_d382bb48e929');
        Schema::dropIfExists('tl_payments_star_gift_upgrade_preview_star_gi_2469e890a24d');
    }
};
