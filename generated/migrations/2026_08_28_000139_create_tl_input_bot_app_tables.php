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
        Schema::create('tl_input_bot_app_input_bot_app_i_d', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_92ed544be708081bde51fada');
            $table->index('account_id', 'ix_5a036861ef31d4c57e643a22');
        });
        Schema::create('tl_input_bot_app_input_bot_app_short_name', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('bot_id')->nullable();
            $table->index('bot_id', 'ix_52ad598ee67ec36a514ec5ae');
            $table->text('short_name')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_116af9ac4c6611c228f4f7e8');
            $table->index('account_id', 'ix_0bb6147d502b7fd71a47bdbc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_bot_app_input_bot_app_short_name');
        Schema::dropIfExists('tl_input_bot_app_input_bot_app_i_d');
    }
};
