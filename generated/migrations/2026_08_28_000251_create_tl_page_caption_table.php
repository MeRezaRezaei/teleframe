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
        Schema::create('tl_page_caption', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_0b4f189296094f9dff16b1ec');
            $table->index('account_id', 'ix_7abda1293a6aacba6cacf569');
        });
        Schema::create('tl_page_caption_page_caption', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_page_caption')->cascadeOnDelete();
            $table->uuid('text');
            $table->index('text', 'ix_d5b46c07996f3c7c2ea1e67c');
            $table->uuid('credit');
            $table->index('credit', 'ix_aa4ade5724bff0d3f88e7adf');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_aef555684de71f6130c51edb');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_page_caption_page_caption');
        Schema::dropIfExists('tl_page_caption');
    }
};
