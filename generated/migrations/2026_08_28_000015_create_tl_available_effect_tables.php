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
        Schema::create('tl_available_effect_available_effect', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('premium_required')->default(false);
            $table->bigInteger('tl_id')->nullable();
            $table->text('emoticon')->nullable();
            $table->bigInteger('static_icon_id')->nullable();
            $table->index('static_icon_id', 'ix_c038430467961980880317a4');
            $table->bigInteger('effect_sticker_id')->nullable();
            $table->index('effect_sticker_id', 'ix_dda9b29b9e63e3dc46d7c1b5');
            $table->bigInteger('effect_animation_id')->nullable();
            $table->index('effect_animation_id', 'ix_8835730961b45d21238bf27e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0d635eb483d5eb40522ab4d5');
            $table->index('account_id', 'ix_14c0a8d301df20d0378ab7b5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_available_effect_available_effect');
    }
};
