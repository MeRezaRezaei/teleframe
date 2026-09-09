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
        Schema::create('tl_document', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_a0d84cc1d0811b2bd1f93047');
            $table->index('account_id', 'ix_5ef101c8fdd74458deca470c');
        });
        Schema::create('tl_document_document', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_document')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->binary('file_reference');
            $table->integer('date');
            $table->text('mime_type');
            $table->bigInteger('tl_size');
            $table->integer('dc_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d996f478686a05864c7ed2ab');
            $table->unique(['account_id', 'tl_id'], 'ux_263665bcc7f0f1f30456');
        });
        Schema::create('tl_document_document__thumbs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_document_document')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ea8267eae15b64501cb2');
            $table->index('account_id', 'ix_9e18f1b3899085ee7c8c3869');
        });
        Schema::create('tl_document_document__video_thumbs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_document_document')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e7fa625dcf972ddc8429');
            $table->index('account_id', 'ix_aed49c05e0fedfcdd279a44f');
        });
        Schema::create('tl_document_document__attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_document_document')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6d9066d04be16b0de4dc');
            $table->index('account_id', 'ix_939eb536a889cff7fb5fff9d');
        });
        Schema::create('tl_document_document_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_document')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_59b3a9e2688309b5520acf2c');
            $table->unique(['account_id', 'tl_id'], 'ux_c2f49ae8e2726cf3b70f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_document_document_empty');
        Schema::dropIfExists('tl_document_document__attributes');
        Schema::dropIfExists('tl_document_document__video_thumbs');
        Schema::dropIfExists('tl_document_document__thumbs');
        Schema::dropIfExists('tl_document_document');
        Schema::dropIfExists('tl_document');
    }
};
