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
        Schema::create('tl_sponsored_peer_sponsored_peer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->binary('random_id')->nullable();
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_6e8f437f7bfd598ce1da93e7');
            $table->text('sponsor_info')->nullable();
            $table->text('additional_info')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3d204da30bee65ca2b5534da');
            $table->index('account_id', 'ix_5faee33186478a33fbfc02e8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_sponsored_peer_sponsored_peer');
    }
};
