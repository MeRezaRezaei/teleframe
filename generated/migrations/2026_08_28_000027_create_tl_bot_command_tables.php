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
        Schema::create('tl_bot_command_bot_command', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('command')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_953b385ce646ba72719d5140');
            $table->index('account_id', 'ix_2b85c5febc275a0b67058063');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_command_bot_command');
    }
};
