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
        Schema::create('tl_messages_prepared_inline_message_prepared__abbe0eee55f7', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('query_id');
            $table->index('query_id', 'ix_1dbe9a89e91e2220c3f2a6c0');
            $table->bigInteger('result');
            $table->index('result', 'ix_04a3f4544498bb9b5a088216');
            $table->integer('cache_time');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a3c0bd33a8f9093b38f830b2');
            $table->index('account_id', 'ix_b9a57da7d2e0bab9d7a0d971');
        });
        Schema::create('tl_messages_prepared_inline_message_prepared__42fa16a637ba', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_prepared_inline_message_prepared__abbe0eee55f7', 'id', 'fk_3a3115d49bb52640791630b5')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_3873cf06c7531a32ecc8');
            $table->index('account_id', 'ix_c6f837e313ff314c771e24ba');
        });
        Schema::create('tl_messages_prepared_inline_message_prepared__86dd012cf503', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_prepared_inline_message_prepared__abbe0eee55f7', 'id', 'fk_52bf71d196f8531950587a5b')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
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
    }
};
