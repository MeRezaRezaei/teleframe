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
        Schema::create('tl_input_bot_inline_message_i_d_input_bot_inl_65be0b9b7598', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('dc_id')->nullable();
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6bdab83cf5574157931083c2');
            $table->index('account_id', 'ix_43fc592b7828395cd2201ea7');
        });
        Schema::create('tl_input_bot_inline_message_i_d_input_bot_inl_d3b2ec5fd706', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('dc_id')->nullable();
            $table->bigInteger('owner_id')->nullable();
            $table->index('owner_id', 'ix_f42b9edd4a439fefc1562fdb');
            $table->integer('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_59d0e2d99233b0839f77eb32');
            $table->index('account_id', 'ix_217bf9955bbea4ab0dc23735');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_bot_inline_message_i_d_input_bot_inl_d3b2ec5fd706');
        Schema::dropIfExists('tl_input_bot_inline_message_i_d_input_bot_inl_65be0b9b7598');
    }
};
