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
        Schema::create('tl_stars_amount', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c7dcaa2c93ac33ef92479ff5');
            $table->index('account_id', 'ix_6dcf71303367ac1fd957baa6');
        });
        Schema::create('tl_stars_amount_stars_amount', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_amount')->cascadeOnDelete();
            $table->bigInteger('amount');
            $table->integer('nanos');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_45a9795ad3b3a0708ad64c20');
        });
        Schema::create('tl_stars_amount_stars_ton_amount', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_amount')->cascadeOnDelete();
            $table->bigInteger('amount');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_39b708fc2f0368f400479c6e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_amount_stars_ton_amount');
        Schema::dropIfExists('tl_stars_amount_stars_amount');
        Schema::dropIfExists('tl_stars_amount');
    }
};
