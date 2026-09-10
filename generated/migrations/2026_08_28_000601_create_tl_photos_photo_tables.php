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
        Schema::create('tl_photos_photo_photo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_97e6dc595bf58bca365abc0b');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_909315deca6f907a41980311');
            $table->index('account_id', 'ix_f14d44b46cf97a14aa074cef');
        });
        Schema::create('tl_photos_photo_photo__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_783c7de683fe769ef9e44c62')->references('id')->on('tl_photos_photo_photo')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_cbae3c4749d3aabc2c28');
            $table->index('account_id', 'ix_870271f0019439e82381b5fa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_photos_photo_photo__users');
        Schema::dropIfExists('tl_photos_photo_photo');
    }
};
