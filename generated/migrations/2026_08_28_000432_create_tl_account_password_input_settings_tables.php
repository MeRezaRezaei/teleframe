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
        Schema::create('tl_account_password_input_settings_password_input_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('new_algo')->nullable();
            $table->index('new_algo', 'ix_10a424a09f32fa5270af286f');
            $table->binary('new_password_hash')->nullable();
            $table->text('hint')->nullable();
            $table->text('email')->nullable();
            $table->bigInteger('new_secure_settings')->nullable();
            $table->index('new_secure_settings', 'ix_a7ac54e5e7cd99988d12ae31');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fb0762de0aa2e8495a8902d4');
            $table->index('account_id', 'ix_f8013a25143dbfcb0d0136ab');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_password_input_settings_password_input_settings');
    }
};
