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
        Schema::create('tl_messages_prepared_inline_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_prepared_inline_message_prepared__abbe0eee55f7', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_prepared_inline_message')->cascadeOnDelete();
            $table->bigInteger('query_id');
            $table->uuid('result');
            $table->integer('cache_time');
            $table->timestamps();
        });
        Schema::create('tl_messages_prepared_inline_message_prepared__42fa16a637ba', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_prepared_inline_message_prepared__abbe0eee55f7')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_3873cf06c7531a32ecc8');
        });
        Schema::create('tl_messages_prepared_inline_message_prepared__86dd012cf503', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_prepared_inline_message_prepared__abbe0eee55f7')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_2c938e6eadb42380ecdc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_prepared_inline_message_prepared__86dd012cf503');
        Schema::dropIfExists('tl_messages_prepared_inline_message_prepared__42fa16a637ba');
        Schema::dropIfExists('tl_messages_prepared_inline_message_prepared__abbe0eee55f7');
        Schema::dropIfExists('tl_messages_prepared_inline_message');
    }
};
