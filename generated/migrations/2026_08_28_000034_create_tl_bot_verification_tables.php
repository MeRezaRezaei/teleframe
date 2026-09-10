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
        Schema::create('tl_bot_verification_bot_verification', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('bot_id')->nullable();
            $table->index('bot_id', 'ix_37cf98ff58b66cafe719a5b9');
            $table->bigInteger('icon')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ccbc8b78f05d254d65f309c0');
            $table->index('account_id', 'ix_c32987fc9ab37c9793f2ccd0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_verification_bot_verification');
    }
};
