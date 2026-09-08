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
        Schema::create('tl_disallowed_gifts_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_disallowed_gifts_settings_disallowed_gifts_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_disallowed_gifts_settings')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('disallow_unlimited_stargifts')->default(false);
            $table->boolean('disallow_limited_stargifts')->default(false);
            $table->boolean('disallow_unique_stargifts')->default(false);
            $table->boolean('disallow_premium_gifts')->default(false);
            $table->boolean('disallow_stargifts_from_channels')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_disallowed_gifts_settings_disallowed_gifts_settings');
        Schema::dropIfExists('tl_disallowed_gifts_settings');
    }
};
