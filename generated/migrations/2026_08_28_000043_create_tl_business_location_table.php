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
        Schema::create('tl_business_location', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_a6360194cc298d941e0549b5');
            $table->index('account_id', 'ix_630847b023cb744c937b4280');
        });
        Schema::create('tl_business_location_business_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_business_location')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('geo_point')->nullable();
            $table->index('geo_point', 'ix_ffece6d459d9f3f4aa60df57');
            $table->text('address');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8a8ce01557c1344517fb5a9f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_location_business_location');
        Schema::dropIfExists('tl_business_location');
    }
};
