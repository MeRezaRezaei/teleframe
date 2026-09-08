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
        Schema::create('tl_chat_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_chat_participants_chat_participants', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_participants')->cascadeOnDelete();
            $table->bigInteger('chat_id');
            $table->integer('version');
            $table->timestamps();
        });
        Schema::create('tl_chat_participants_chat_participants__participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chat_participants_chat_participants')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_38a75218a587f72142dc');
        });
        Schema::create('tl_chat_participants_chat_participants_forbidden', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_participants')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('chat_id');
            $table->uuid('self_participant')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_participants_chat_participants_forbidden');
        Schema::dropIfExists('tl_chat_participants_chat_participants__participants');
        Schema::dropIfExists('tl_chat_participants_chat_participants');
        Schema::dropIfExists('tl_chat_participants');
    }
};
