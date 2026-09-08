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
        Schema::create('tl_decrypted_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_decrypted_message_decrypted_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('no_webpage')->default(false);
            $table->boolean('silent')->default(false);
            $table->bigInteger('random_id');
            $table->integer('ttl');
            $table->text('message');
            $table->uuid('media')->nullable();
            $table->text('via_bot_name')->nullable();
            $table->bigInteger('reply_to_random_id')->nullable();
            $table->bigInteger('grouped_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_decrypted_message_decrypted_message__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_decrypted_message_decrypted_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_9fcd2c13e68aea8bcd7d');
        });
        Schema::create('tl_decrypted_message_decrypted_message_service', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_decrypted_message')->cascadeOnDelete();
            $table->bigInteger('random_id');
            $table->uuid('action');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_decrypted_message_decrypted_message_service');
        Schema::dropIfExists('tl_decrypted_message_decrypted_message__entities');
        Schema::dropIfExists('tl_decrypted_message_decrypted_message');
        Schema::dropIfExists('tl_decrypted_message');
    }
};
