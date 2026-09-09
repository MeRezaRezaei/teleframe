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
        Schema::create('tl_bot_command', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_65df34dee5f37bc079c24873');
            $table->index('account_id', 'ix_594ef419db391f704de183ad');
        });
        Schema::create('tl_bot_command_bot_command', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_command')->cascadeOnDelete();
            $table->text('command');
            $table->text('description');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2b85c5febc275a0b67058063');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_command_bot_command');
        Schema::dropIfExists('tl_bot_command');
    }
};
