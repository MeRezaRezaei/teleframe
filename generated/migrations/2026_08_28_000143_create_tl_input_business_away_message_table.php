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
        Schema::create('tl_input_business_away_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_business_away_message_input_business_away_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_business_away_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('offline_only')->default(false);
            $table->integer('shortcut_id');
            $table->uuid('schedule');
            $table->uuid('recipients');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_business_away_message_input_business_away_message');
        Schema::dropIfExists('tl_input_business_away_message');
    }
};
