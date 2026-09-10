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
        Schema::create('tl_bot_verifier_settings_bot_verifier_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('can_modify_custom_description')->default(false);
            $table->bigInteger('icon')->nullable();
            $table->text('company')->nullable();
            $table->text('custom_description')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f18af0add0f51e3978a35281');
            $table->index('account_id', 'ix_a5b1faf62a270cadbe1dbff4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_verifier_settings_bot_verifier_settings');
    }
};
