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
        Schema::create('tl_messages_sent_encrypted_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b304651306c7321ca9b7ff59');
            $table->index('account_id', 'ix_4f5ced8165dc550f1f722515');
        });
        Schema::create('tl_messages_sent_encrypted_message_sent_encrypted_file', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_sent_encrypted_message')->cascadeOnDelete();
            $table->integer('date');
            $table->uuid('file');
            $table->index('file', 'ix_2912ed65ebf0c9784a577a6d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b7bc25ca99b4c9b0729e5031');
        });
        Schema::create('tl_messages_sent_encrypted_message_sent_encrypted_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_sent_encrypted_message')->cascadeOnDelete();
            $table->integer('date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7764669141a2eb5b8264c138');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_sent_encrypted_message_sent_encrypted_message');
        Schema::dropIfExists('tl_messages_sent_encrypted_message_sent_encrypted_file');
        Schema::dropIfExists('tl_messages_sent_encrypted_message');
    }
};
