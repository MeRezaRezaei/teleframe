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
        Schema::create('tl_chat_photo_chat_photo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_video')->default(false);
            $table->bigInteger('photo_id')->nullable();
            $table->index('photo_id', 'ix_fc8c6b9a347ccec7212d2c07');
            $table->binary('stripped_thumb')->nullable();
            $table->integer('dc_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c54aa7ce5c37bea2b9f75030');
            $table->index('account_id', 'ix_5f7d4ef2deeba541444100e0');
        });
        Schema::create('tl_chat_photo_chat_photo_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c58f45b84583dc7bf3ff5b75');
            $table->index('account_id', 'ix_23d81dfa8c7ac4e4f48ccc67');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_photo_chat_photo_empty');
        Schema::dropIfExists('tl_chat_photo_chat_photo');
    }
};
