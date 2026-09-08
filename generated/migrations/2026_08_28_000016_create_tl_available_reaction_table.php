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
        Schema::create('tl_available_reaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_available_reaction_available_reaction', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_available_reaction')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('inactive')->default(false);
            $table->boolean('premium')->default(false);
            $table->text('reaction');
            $table->text('title');
            $table->uuid('static_icon');
            $table->uuid('appear_animation');
            $table->uuid('select_animation');
            $table->uuid('activate_animation');
            $table->uuid('effect_animation');
            $table->uuid('around_animation')->nullable();
            $table->uuid('center_icon')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_available_reaction_available_reaction');
        Schema::dropIfExists('tl_available_reaction');
    }
};
