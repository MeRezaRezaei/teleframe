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
        Schema::create('tl_input_document', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_3e9f4982fcc75fc049f67c7b');
            $table->index('account_id', 'ix_d84638169ac660f00e760271');
        });
        Schema::create('tl_input_document_input_document', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_document')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->binary('file_reference');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7af7a6704b3b3ade01c51606');
            $table->unique(['account_id', 'tl_id'], 'ux_24c5ab83ee9504f75cf0');
        });
        Schema::create('tl_input_document_input_document_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_document')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_741e69a8bb5b0590d3dcd445');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_document_input_document_empty');
        Schema::dropIfExists('tl_input_document_input_document');
        Schema::dropIfExists('tl_input_document');
    }
};
