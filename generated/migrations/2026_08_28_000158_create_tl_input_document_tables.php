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
        Schema::create('tl_input_document_input_document', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->binary('file_reference')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d58276e0a7f79b3371da1380');
            $table->index('account_id', 'ix_7af7a6704b3b3ade01c51606');
        });
        Schema::create('tl_input_document_input_document_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8644c67f2d708a9583bf11a3');
            $table->index('account_id', 'ix_741e69a8bb5b0590d3dcd445');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_document_input_document_empty');
        Schema::dropIfExists('tl_input_document_input_document');
    }
};
