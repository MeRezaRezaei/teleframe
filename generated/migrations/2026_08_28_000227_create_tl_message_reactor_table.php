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
        Schema::create('tl_message_reactor', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_3ab94c07961695114776187d');
            $table->index('account_id', 'ix_3396f6ce5014ef7406565d5d');
        });
        Schema::create('tl_message_reactor_message_reactor', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_reactor')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('top')->default(false);
            $table->boolean('my')->default(false);
            $table->boolean('anonymous')->default(false);
            $table->bigInteger('peer_id')->nullable();
            $table->index('peer_id', 'ix_f5a810c0fb6be281103ebf20');
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d02d15fb69b57d81ecda0424');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_reactor_message_reactor');
        Schema::dropIfExists('tl_message_reactor');
    }
};
