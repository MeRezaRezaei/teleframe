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
        Schema::create('tl_message_reactor_message_reactor', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('top')->default(false);
            $table->boolean('my')->default(false);
            $table->boolean('anonymous')->default(false);
            $table->bigInteger('peer_id')->nullable();
            $table->index('peer_id', 'ix_f5a810c0fb6be281103ebf20');
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b62611b0e261cf9769e2894d');
            $table->index('account_id', 'ix_d02d15fb69b57d81ecda0424');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_reactor_message_reactor');
    }
};
