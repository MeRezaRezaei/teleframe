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
        Schema::create('tl_contact_birthday_contact_birthday', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('contact_id')->nullable();
            $table->index('contact_id', 'ix_209bc14dcb3530c851e9c3c5');
            $table->bigInteger('birthday')->nullable();
            $table->index('birthday', 'ix_523b36a8d1baa6f74b5b1d9b');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6da22ee892ec0462d7b5406c');
            $table->index('account_id', 'ix_e9915faa415f4fc743e42718');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contact_birthday_contact_birthday');
    }
};
