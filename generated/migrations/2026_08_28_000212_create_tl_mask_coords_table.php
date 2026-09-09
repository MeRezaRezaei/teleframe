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
        Schema::create('tl_mask_coords', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_3f08100e2b2c6c62e08eafd0');
            $table->index('account_id', 'ix_feb3ea9d1be01282cccd2d0d');
        });
        Schema::create('tl_mask_coords_mask_coords', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_mask_coords')->cascadeOnDelete();
            $table->integer('n');
            $table->double('x');
            $table->double('y');
            $table->double('zoom');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9e0c209b391c24024b85d24b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_mask_coords_mask_coords');
        Schema::dropIfExists('tl_mask_coords');
    }
};
