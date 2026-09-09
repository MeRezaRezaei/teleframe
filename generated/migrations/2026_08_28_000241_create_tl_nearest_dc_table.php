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
        Schema::create('tl_nearest_dc', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_4eaa61150ed266673bfa9aef');
            $table->index('account_id', 'ix_17edb5ed07dd5ce428d5338d');
        });
        Schema::create('tl_nearest_dc_nearest_dc', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_nearest_dc')->cascadeOnDelete();
            $table->text('country');
            $table->integer('this_dc');
            $table->integer('nearest_dc');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0007c62a6c390c7671d10e40');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_nearest_dc_nearest_dc');
        Schema::dropIfExists('tl_nearest_dc');
    }
};
