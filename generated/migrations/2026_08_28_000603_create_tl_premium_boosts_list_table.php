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
        Schema::create('tl_premium_boosts_list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_premium_boosts_list_boosts_list', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_premium_boosts_list')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('count');
            $table->text('next_offset')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_premium_boosts_list_boosts_list__boosts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_premium_boosts_list_boosts_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8b3c171ce41af469fba5');
        });
        Schema::create('tl_premium_boosts_list_boosts_list__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_premium_boosts_list_boosts_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_bc7f8eab808f1896e4ac');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_premium_boosts_list_boosts_list__users');
        Schema::dropIfExists('tl_premium_boosts_list_boosts_list__boosts');
        Schema::dropIfExists('tl_premium_boosts_list_boosts_list');
        Schema::dropIfExists('tl_premium_boosts_list');
    }
};
