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
        Schema::create('tl_available_effect', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_05346095d21a42ed89383adb');
            $table->index('account_id', 'ix_d54742f53e73172dcbb74a29');
        });
        Schema::create('tl_available_effect_available_effect', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_available_effect')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('premium_required')->default(false);
            $table->bigInteger('tl_id');
            $table->text('emoticon');
            $table->bigInteger('static_icon_id')->nullable();
            $table->index('static_icon_id', 'ix_c038430467961980880317a4');
            $table->bigInteger('effect_sticker_id');
            $table->index('effect_sticker_id', 'ix_dda9b29b9e63e3dc46d7c1b5');
            $table->bigInteger('effect_animation_id')->nullable();
            $table->index('effect_animation_id', 'ix_8835730961b45d21238bf27e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_14c0a8d301df20d0378ab7b5');
            $table->unique(['account_id', 'tl_id'], 'ux_98f2b070b48bef01f1d3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_available_effect_available_effect');
        Schema::dropIfExists('tl_available_effect');
    }
};
