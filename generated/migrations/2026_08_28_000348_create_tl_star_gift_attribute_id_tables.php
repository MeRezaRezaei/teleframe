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
        Schema::create('tl_star_gift_attribute_id_star_gift_attribute_id_backdrop', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('backdrop_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bcaa64f0d60dc24589cbc5e4');
            $table->index('account_id', 'ix_0ce8c34b2ecb15a76fae3878');
        });
        Schema::create('tl_star_gift_attribute_id_star_gift_attribute_id_model', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('document_id')->nullable();
            $table->index('document_id', 'ix_08bd0346a51ecd3d9999f864');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dcaf6dc735b0b19b3841b314');
            $table->index('account_id', 'ix_2fab6afdb573fb3fb6f6effa');
        });
        Schema::create('tl_star_gift_attribute_id_star_gift_attribute_id_pattern', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('document_id')->nullable();
            $table->index('document_id', 'ix_f5d21aef90c51ce88509eb3d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bc44f33ca12e75ed467caf9e');
            $table->index('account_id', 'ix_f874fb6ba8885441041ee738');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_attribute_id_star_gift_attribute_id_pattern');
        Schema::dropIfExists('tl_star_gift_attribute_id_star_gift_attribute_id_model');
        Schema::dropIfExists('tl_star_gift_attribute_id_star_gift_attribute_id_backdrop');
    }
};
