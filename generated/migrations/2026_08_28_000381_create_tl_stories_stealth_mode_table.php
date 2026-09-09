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
        Schema::create('tl_stories_stealth_mode', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_e96e967f90f63e9ba694339d');
            $table->index('account_id', 'ix_b059f532d3be499f92e2033b');
        });
        Schema::create('tl_stories_stealth_mode_stories_stealth_mode', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stories_stealth_mode')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('active_until_date')->nullable();
            $table->integer('cooldown_until_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4498f77726f5d19c285db496');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stories_stealth_mode_stories_stealth_mode');
        Schema::dropIfExists('tl_stories_stealth_mode');
    }
};
