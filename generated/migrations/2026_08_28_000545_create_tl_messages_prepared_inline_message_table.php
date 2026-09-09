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
            $table->index('constructor_id', 'ix_cb61baae0da97b57523c2742');
            $table->index('account_id', 'ix_23f3aabfd9adc5ed1952007b');
        });
        Schema::create('tl_messages_prepared_inline_message_prepared__abbe0eee55f7', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_prepared_inline_message')->cascadeOnDelete();
            $table->bigInteger('query_id');
            $table->index('query_id', 'ix_1dbe9a89e91e2220c3f2a6c0');
            $table->uuid('result');
            $table->index('result', 'ix_04a3f4544498bb9b5a088216');
            $table->integer('cache_time');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b9a57da7d2e0bab9d7a0d971');
        });
        Schema::create('tl_messages_prepared_inline_message_prepared__42fa16a637ba', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_prepared_inline_message_prepared__abbe0eee55f7')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_3873cf06c7531a32ecc8');
            $table->index('account_id', 'ix_c6f837e313ff314c771e24ba');
        });
        Schema::create('tl_messages_prepared_inline_message_prepared__86dd012cf503', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_prepared_inline_message_prepared__abbe0eee55f7')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_2c938e6eadb42380ecdc');
            $table->index('account_id', 'ix_8cfc47cc1374a841d14f17af');
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
