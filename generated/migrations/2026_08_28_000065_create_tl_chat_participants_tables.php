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
        Schema::create('tl_chat_participants_chat_participants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('chat_id')->nullable();
            $table->index('chat_id', 'ix_18e2db10adaf1b47acc98ff4');
            $table->integer('version')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_08835f5da85a153c5bc3d717');
            $table->index('account_id', 'ix_f25ed9d9390c5307bf9b54aa');
        });
        Schema::create('tl_chat_participants_chat_participants__participants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_chat_participants_chat_participants', 'id', 'fk_03d129482e51b62f58fb2f91')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_38a75218a587f72142dc');
            $table->index('account_id', 'ix_86f88478683cbdc4c422800b');
        });
        Schema::create('tl_chat_participants_chat_participants_forbidden', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('chat_id')->nullable();
            $table->index('chat_id', 'ix_ce132a5db14f6e17c65d89ea');
            $table->bigInteger('self_participant')->nullable();
            $table->index('self_participant', 'ix_9b094ab90e5639b2ec7df2d0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3351d630de0f8b7ec98a8bc3');
            $table->index('account_id', 'ix_2ad0778e6234170a73d90116');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_participants_chat_participants_forbidden');
        Schema::dropIfExists('tl_chat_participants_chat_participants__participants');
        Schema::dropIfExists('tl_chat_participants_chat_participants');
    }
};
