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
        Schema::create('tl_input_web_document', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_3d004cccc4f5a84f0cd45c86');
            $table->index('account_id', 'ix_51610fe50575a915811872c0');
        });
        Schema::create('tl_input_web_document_input_web_document', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_web_document')->cascadeOnDelete();
            $table->text('url');
            $table->integer('tl_size');
            $table->text('mime_type');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_79cdf171594109481b3d7346');
        });
        Schema::create('tl_input_web_document_input_web_document__attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_web_document_input_web_document')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0bfabeb4ccecc076e097');
            $table->index('account_id', 'ix_068b28778db0080a5698e50f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_web_document_input_web_document__attributes');
        Schema::dropIfExists('tl_input_web_document_input_web_document');
        Schema::dropIfExists('tl_input_web_document');
    }
};
