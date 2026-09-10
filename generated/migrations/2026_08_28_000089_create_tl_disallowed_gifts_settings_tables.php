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
        Schema::create('tl_disallowed_gifts_settings_disallowed_gifts_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('disallow_unlimited_stargifts')->default(false);
            $table->boolean('disallow_limited_stargifts')->default(false);
            $table->boolean('disallow_unique_stargifts')->default(false);
            $table->boolean('disallow_premium_gifts')->default(false);
            $table->boolean('disallow_stargifts_from_channels')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_17059a75346223724ed476e1');
            $table->index('account_id', 'ix_38a1bb807cbba45493ba3138');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_disallowed_gifts_settings_disallowed_gifts_settings');
    }
};
