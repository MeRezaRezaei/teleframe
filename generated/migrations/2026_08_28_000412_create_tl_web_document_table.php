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
        Schema::create('tl_web_document', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_21eb396d9f61ad808658f2a8');
            $table->index('account_id', 'ix_d97f608a88330ecc44a78b75');
        });
        Schema::create('tl_web_document_web_document', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_document')->cascadeOnDelete();
            $table->text('url');
            $table->bigInteger('access_hash');
            $table->integer('tl_size');
            $table->text('mime_type');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3b1b1a99415fcf5127c5c60d');
        });
        Schema::create('tl_web_document_web_document__attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_web_document_web_document')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c14c4791227afbcb2654');
            $table->index('account_id', 'ix_d809a7a6d1dcf77db78840dc');
        });
        Schema::create('tl_web_document_web_document_no_proxy', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_document')->cascadeOnDelete();
            $table->text('url');
            $table->integer('tl_size');
            $table->text('mime_type');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e9268571035f3efe2680f02a');
        });
        Schema::create('tl_web_document_web_document_no_proxy__attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_web_document_web_document_no_proxy')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_deed4e5e6451f3930dca');
            $table->index('account_id', 'ix_11fbba23b1aef4382248d8ac');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_web_document_web_document_no_proxy__attributes');
        Schema::dropIfExists('tl_web_document_web_document_no_proxy');
        Schema::dropIfExists('tl_web_document_web_document__attributes');
        Schema::dropIfExists('tl_web_document_web_document');
        Schema::dropIfExists('tl_web_document');
    }
};
