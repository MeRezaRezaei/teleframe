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
        Schema::create('tl_mask_coords_mask_coords', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('n')->nullable();
            $table->double('x')->nullable();
            $table->double('y')->nullable();
            $table->double('zoom')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_60f65d6dc9e622d65f87f228');
            $table->index('account_id', 'ix_9e0c209b391c24024b85d24b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_mask_coords_mask_coords');
    }
};
