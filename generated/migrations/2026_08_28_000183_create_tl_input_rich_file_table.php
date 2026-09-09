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
        Schema::create('tl_input_rich_file', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_9e7f504dac9ab08f1ff26cf8');
            $table->index('account_id', 'ix_f0544a0ecb28ae3cb2517f7c');
        });
        Schema::create('tl_input_rich_file_input_rich_file_document', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_rich_file')->cascadeOnDelete();
            $table->text('tl_id');
            $table->uuid('document');
            $table->index('document', 'ix_c909476ff93d6076b2260fc3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_69e9abc28bfaf62aa5b3912c');
            $table->unique(['account_id', 'tl_id'], 'ux_fa70500720a2695417aa');
        });
        Schema::create('tl_input_rich_file_input_rich_file_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_rich_file')->cascadeOnDelete();
            $table->text('tl_id');
            $table->uuid('photo');
            $table->index('photo', 'ix_3edaba4d0f5423052d85a0b7');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b7bdd7dec24d6f9543f0a8f8');
            $table->unique(['account_id', 'tl_id'], 'ux_00b37f6bbf758ad834fe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_rich_file_input_rich_file_photo');
        Schema::dropIfExists('tl_input_rich_file_input_rich_file_document');
        Schema::dropIfExists('tl_input_rich_file');
    }
};
