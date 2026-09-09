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
        Schema::create('tl_stickers_suggested_short_name', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_be805f7d288017be88690fe4');
            $table->index('account_id', 'ix_d3113f70854a1c1fcc390a3f');
        });
        Schema::create('tl_stickers_suggested_short_name_suggested_short_name', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stickers_suggested_short_name')->cascadeOnDelete();
            $table->text('short_name');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d125f7ed12a199053580eca3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stickers_suggested_short_name_suggested_short_name');
        Schema::dropIfExists('tl_stickers_suggested_short_name');
    }
};
