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
        Schema::create('tl_stars_giveaway_winners_option', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_50d54e216bc249db7574359c');
            $table->index('account_id', 'ix_b3243e6e29cc847f813b1ae8');
        });
        Schema::create('tl_stars_giveaway_winners_option_stars_giveaw_0f7ecce3eb88', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_giveaway_winners_option')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('tl_default')->default(false);
            $table->integer('users');
            $table->bigInteger('per_user_stars');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9f62a046a1177c410061f836');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_giveaway_winners_option_stars_giveaw_0f7ecce3eb88');
        Schema::dropIfExists('tl_stars_giveaway_winners_option');
    }
};
