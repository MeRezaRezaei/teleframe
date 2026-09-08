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
        Schema::create('tl_payments_bank_card_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_payments_bank_card_data_bank_card_data', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_bank_card_data')->cascadeOnDelete();
            $table->text('title');
            $table->timestamps();
        });
        Schema::create('tl_payments_bank_card_data_bank_card_data__open_urls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_bank_card_data_bank_card_data')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ab820f8fc6cead734c0d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_bank_card_data_bank_card_data__open_urls');
        Schema::dropIfExists('tl_payments_bank_card_data_bank_card_data');
        Schema::dropIfExists('tl_payments_bank_card_data');
    }
};
