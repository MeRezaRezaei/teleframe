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
        Schema::create('tl_bank_card_open_url', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8a299fa3a51b30475b64ebed');
            $table->index('account_id', 'ix_4d8e52175ba200164d614f16');
        });
        Schema::create('tl_bank_card_open_url_bank_card_open_url', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bank_card_open_url')->cascadeOnDelete();
            $table->text('url');
            $table->text('name');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c6002e41fc488dc138218fba');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bank_card_open_url_bank_card_open_url');
        Schema::dropIfExists('tl_bank_card_open_url');
    }
};
