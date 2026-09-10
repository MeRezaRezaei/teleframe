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
        Schema::create('tl_stories_stealth_mode_stories_stealth_mode', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('active_until_date')->nullable();
            $table->integer('cooldown_until_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3ae1dbc0e63a63e0363564fb');
            $table->index('account_id', 'ix_4498f77726f5d19c285db496');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stories_stealth_mode_stories_stealth_mode');
    }
};
