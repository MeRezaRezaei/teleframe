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
        Schema::create('tl_account_auto_download_settings_auto_download_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('low')->nullable();
            $table->index('low', 'ix_429ed29e0be1441fd0a54154');
            $table->bigInteger('medium')->nullable();
            $table->index('medium', 'ix_b662f76db0bd56eeb04fb9b7');
            $table->bigInteger('high')->nullable();
            $table->index('high', 'ix_cabfca3664c9560bd0b3fa27');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_afbfef8cbcd936184ceae893');
            $table->index('account_id', 'ix_f8abca64ed9ebdf6dc5bfb9b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_auto_download_settings_auto_download_settings');
    }
};
