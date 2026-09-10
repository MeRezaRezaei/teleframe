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
        Schema::create('tl_email_verification_email_verification_apple', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('token')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dc7982022bd27d7d3b36e139');
            $table->index('account_id', 'ix_2eaeeae231f97049f3a86d86');
        });
        Schema::create('tl_email_verification_email_verification_code', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('code')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_51977344e2f8a35b43007930');
            $table->index('account_id', 'ix_41749a172b68a0a5ec0e3e19');
        });
        Schema::create('tl_email_verification_email_verification_google', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('token')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_92c11169d81609190a58afe1');
            $table->index('account_id', 'ix_4b016a498583b240eb35d235');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_email_verification_email_verification_google');
        Schema::dropIfExists('tl_email_verification_email_verification_code');
        Schema::dropIfExists('tl_email_verification_email_verification_apple');
    }
};
