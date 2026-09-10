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
        Schema::create('tl_bots_requested_button_requested_button', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('webapp_req_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bae9488327ed3393622f3bc3');
            $table->index('account_id', 'ix_680be1e963cc890c9d44ecb6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bots_requested_button_requested_button');
    }
};
