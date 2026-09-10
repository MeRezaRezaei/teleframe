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
        Schema::create('tl_input_business_intro_input_business_intro', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('sticker')->nullable();
            $table->index('sticker', 'ix_db109a1b631c73a26abb59f7');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_322a89393a65d68d6ff9c197');
            $table->index('account_id', 'ix_bf6f815ec9c286c35dfbb936');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_business_intro_input_business_intro');
    }
};
