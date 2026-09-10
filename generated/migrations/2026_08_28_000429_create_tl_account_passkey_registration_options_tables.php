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
        Schema::create('tl_account_passkey_registration_options_passk_47a076e67747', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('options')->nullable();
            $table->index('options', 'ix_bb56eb9b66e04adf8344dd43');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e30bfd359efa8d4c295a8940');
            $table->index('account_id', 'ix_298f0cb6a266484d4bf611f3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_passkey_registration_options_passk_47a076e67747');
    }
};
