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
        Schema::create('tl_messages_search_counter', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_0d7393626fdb86f3c116eeb3');
            $table->index('account_id', 'ix_8b480dc762d8cb1305cfad01');
        });
        Schema::create('tl_messages_search_counter_search_counter', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_search_counter')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('inexact')->default(false);
            $table->uuid('filter');
            $table->index('filter', 'ix_f2841075d96bf41459874d1c');
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_83b7d2d1fa8ec3090d7c21e2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_search_counter_search_counter');
        Schema::dropIfExists('tl_messages_search_counter');
    }
};
