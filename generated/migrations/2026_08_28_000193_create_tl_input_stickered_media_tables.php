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
        Schema::create('tl_input_stickered_media_input_stickered_media_document', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->index('tl_id', 'ix_0637c134ab05cd238c76b3c8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bfd7525516d8676215398b19');
            $table->index('account_id', 'ix_b5a805291041f7dcb0dd9bf8');
        });
        Schema::create('tl_input_stickered_media_input_stickered_media_photo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->index('tl_id', 'ix_8260217fbbf522e77c4b9b7a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7fdbd603b82ebeec0b3d7e3d');
            $table->index('account_id', 'ix_8b770a1787ac8682c20e1c49');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_stickered_media_input_stickered_media_photo');
        Schema::dropIfExists('tl_input_stickered_media_input_stickered_media_document');
    }
};
