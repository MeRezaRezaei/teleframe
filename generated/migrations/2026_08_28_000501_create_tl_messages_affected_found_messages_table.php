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
        Schema::create('tl_messages_affected_found_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_affected_found_messages_affected__d0b5b58c5216', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_affected_found_messages')->cascadeOnDelete();
            $table->integer('pts');
            $table->integer('pts_count');
            $table->integer('tl_offset');
            $table->timestamps();
        });
        Schema::create('tl_messages_affected_found_messages_affected__84127c85d979', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_affected_found_messages_affected__d0b5b58c5216')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_7913711edc1702816a8d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_affected_found_messages_affected__84127c85d979');
        Schema::dropIfExists('tl_messages_affected_found_messages_affected__d0b5b58c5216');
        Schema::dropIfExists('tl_messages_affected_found_messages');
    }
};
