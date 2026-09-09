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
        Schema::create('tl_stars_rating', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_87caed112d4c3b3438ecd338');
            $table->index('account_id', 'ix_d916b212d25ca87d2ee7a774');
        });
        Schema::create('tl_stars_rating_stars_rating', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_rating')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('level');
            $table->bigInteger('current_level_stars');
            $table->bigInteger('stars');
            $table->bigInteger('next_level_stars')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_994a48c4641874e2f8c2c3bf');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_rating_stars_rating');
        Schema::dropIfExists('tl_stars_rating');
    }
};
