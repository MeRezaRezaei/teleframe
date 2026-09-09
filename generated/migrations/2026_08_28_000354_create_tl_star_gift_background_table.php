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
        Schema::create('tl_star_gift_background', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_5d9283cecf6e384a02d911cf');
            $table->index('account_id', 'ix_4fdea7055e55b86272347af9');
        });
        Schema::create('tl_star_gift_background_star_gift_background', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_background')->cascadeOnDelete();
            $table->integer('center_color');
            $table->integer('edge_color');
            $table->integer('text_color');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_eba9d7ce584159eaea19499e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_background_star_gift_background');
        Schema::dropIfExists('tl_star_gift_background');
    }
};
