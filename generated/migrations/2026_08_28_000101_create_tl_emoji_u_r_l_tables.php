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
        Schema::create('tl_emoji_u_r_l_emoji_u_r_l', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d81cf7906e44f9b19a0c27b3');
            $table->index('account_id', 'ix_10f0733ebef4712f96b41c6f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_emoji_u_r_l_emoji_u_r_l');
    }
};
