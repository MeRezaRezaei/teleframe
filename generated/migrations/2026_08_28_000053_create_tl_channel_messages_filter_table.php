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
        Schema::create('tl_channel_messages_filter', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_e1502d4109b299630f06bb95');
            $table->index('account_id', 'ix_7a3b26559dd843abf3a06a54');
        });
        Schema::create('tl_channel_messages_filter_channel_messages_filter', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_messages_filter')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('exclude_new_messages')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_28e6befebfd2bb99532c0b4d');
        });
        Schema::create('tl_channel_messages_filter_channel_messages_filter__ranges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_channel_messages_filter_channel_messages_filter')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_80385ac620c4cf41e1c1');
            $table->index('account_id', 'ix_bf07e5f3889d85fa804a8138');
        });
        Schema::create('tl_channel_messages_filter_channel_messages_filter_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_messages_filter')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_87832db235e43194adc36d49');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channel_messages_filter_channel_messages_filter_empty');
        Schema::dropIfExists('tl_channel_messages_filter_channel_messages_filter__ranges');
        Schema::dropIfExists('tl_channel_messages_filter_channel_messages_filter');
        Schema::dropIfExists('tl_channel_messages_filter');
    }
};
