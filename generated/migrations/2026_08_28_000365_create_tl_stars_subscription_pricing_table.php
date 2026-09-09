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
        Schema::create('tl_stars_subscription_pricing', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c4352847a5b68e289b190700');
            $table->index('account_id', 'ix_4fa008d94e6eebab28d6f3b9');
        });
        Schema::create('tl_stars_subscription_pricing_stars_subscription_pricing', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_subscription_pricing')->cascadeOnDelete();
            $table->integer('period');
            $table->bigInteger('amount');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d80e09a82caf27a0ff90be58');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_subscription_pricing_stars_subscription_pricing');
        Schema::dropIfExists('tl_stars_subscription_pricing');
    }
};
