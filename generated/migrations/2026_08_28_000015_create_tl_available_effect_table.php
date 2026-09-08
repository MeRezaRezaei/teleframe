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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_available_effect_available_effect', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_available_effect')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('premium_required')->default(false);
            $table->bigInteger('tl_id');
            $table->text('emoticon');
            $table->bigInteger('static_icon_id')->nullable();
            $table->bigInteger('effect_sticker_id');
            $table->bigInteger('effect_animation_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_available_effect_available_effect');
        Schema::dropIfExists('tl_available_effect');
    }
};
