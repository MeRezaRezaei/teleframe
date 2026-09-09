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
        Schema::create('tl_messages_translated_text', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_359a555c2c7c4c074646a8b2');
            $table->index('account_id', 'ix_dd9dadeee6500e735a44090f');
        });
        Schema::create('tl_messages_translated_text_translate_result', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_translated_text')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a577888aa4e0c83887ceb279');
        });
        Schema::create('tl_messages_translated_text_translate_result__result', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_translated_text_translate_result')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c8ed709320a9e3a4b3ef');
            $table->index('account_id', 'ix_89775c6ccaba07fc127f5bfe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_translated_text_translate_result__result');
        Schema::dropIfExists('tl_messages_translated_text_translate_result');
        Schema::dropIfExists('tl_messages_translated_text');
    }
};
