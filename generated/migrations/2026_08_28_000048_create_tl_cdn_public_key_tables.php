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
        Schema::create('tl_cdn_public_key_cdn_public_key', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('dc_id')->nullable();
            $table->text('public_key')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e95b072b3383f926aed5e249');
            $table->index('account_id', 'ix_8bb557900aa421dfeb5d5a7a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_cdn_public_key_cdn_public_key');
    }
};
