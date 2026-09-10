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
        Schema::create('tl_input_contact_input_phone_contact', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('client_id')->nullable();
            $table->index('client_id', 'ix_da35da78a7977dea4def049c');
            $table->text('phone')->nullable();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->bigInteger('note')->nullable();
            $table->index('note', 'ix_8ba7039588624a831864316c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b8207a72afaaa44fd185e46a');
            $table->index('account_id', 'ix_9c6e7707b0b6b29fdc18992b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_contact_input_phone_contact');
    }
};
