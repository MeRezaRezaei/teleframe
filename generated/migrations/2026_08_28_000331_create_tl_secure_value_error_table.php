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
        Schema::create('tl_secure_value_error', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_secure_value_error_secure_value_error', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_error')->cascadeOnDelete();
            $table->uuid('tl_type');
            $table->binary('hash');
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_secure_value_error_secure_value_error_data', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_error')->cascadeOnDelete();
            $table->uuid('tl_type');
            $table->binary('data_hash');
            $table->text('field');
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_secure_value_error_secure_value_error_file', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_error')->cascadeOnDelete();
            $table->uuid('tl_type');
            $table->binary('file_hash');
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_secure_value_error_secure_value_error_files', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_error')->cascadeOnDelete();
            $table->uuid('tl_type');
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_secure_value_error_secure_value_error_files__file_hash', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_secure_value_error_secure_value_error_files')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->binary('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8e467ffc3c4d89ed51ba');
        });
        Schema::create('tl_secure_value_error_secure_value_error_front_side', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_error')->cascadeOnDelete();
            $table->uuid('tl_type');
            $table->binary('file_hash');
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_secure_value_error_secure_value_error_reverse_side', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_error')->cascadeOnDelete();
            $table->uuid('tl_type');
            $table->binary('file_hash');
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_secure_value_error_secure_value_error_selfie', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_error')->cascadeOnDelete();
            $table->uuid('tl_type');
            $table->binary('file_hash');
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_secure_value_error_secure_value_error_translation_file', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_error')->cascadeOnDelete();
            $table->uuid('tl_type');
            $table->binary('file_hash');
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_secure_value_error_secure_value_error_translation_files', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_error')->cascadeOnDelete();
            $table->uuid('tl_type');
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_secure_value_error_secure_value_error_tran_f5846064b312', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_secure_value_error_secure_value_error_translation_files')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->binary('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_487d4b242ffeb4d6c379');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_tran_f5846064b312');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_translation_files');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_translation_file');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_selfie');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_reverse_side');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_front_side');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_files__file_hash');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_files');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_file');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_data');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error');
        Schema::dropIfExists('tl_secure_value_error');
    }
};
