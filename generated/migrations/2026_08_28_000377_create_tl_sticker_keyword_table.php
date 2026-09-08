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
        Schema::create('tl_sticker_keyword', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_sticker_keyword_sticker_keyword', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_sticker_keyword')->cascadeOnDelete();
            $table->bigInteger('document_id');
            $table->timestamps();
        });
        Schema::create('tl_sticker_keyword_sticker_keyword__keyword', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_sticker_keyword_sticker_keyword')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_030c7f2de4a9c1c05d32');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_sticker_keyword_sticker_keyword__keyword');
        Schema::dropIfExists('tl_sticker_keyword_sticker_keyword');
        Schema::dropIfExists('tl_sticker_keyword');
    }
};
