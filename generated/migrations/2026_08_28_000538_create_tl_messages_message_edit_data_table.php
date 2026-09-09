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
        Schema::create('tl_messages_message_edit_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_4dbd4c69d96b80ba30be54ed');
            $table->index('account_id', 'ix_a0952cc077e32f6c70192fe9');
        });
        Schema::create('tl_messages_message_edit_data_message_edit_data', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_message_edit_data')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('caption')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_828f8f5b6017c4539d65e3be');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_message_edit_data_message_edit_data');
        Schema::dropIfExists('tl_messages_message_edit_data');
    }
};
