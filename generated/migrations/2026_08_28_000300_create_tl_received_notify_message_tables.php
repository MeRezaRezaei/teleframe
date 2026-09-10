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
        Schema::create('tl_received_notify_message_received_notify_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_id')->nullable();
            $table->integer('flags')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_651437223d7e0085f9f373e7');
            $table->index('account_id', 'ix_cc90925f96d68c91dbf7b0e8');
            $table->unique(['account_id'], 'ux_1702d65029e4d17547c4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_received_notify_message_received_notify_message');
    }
};
