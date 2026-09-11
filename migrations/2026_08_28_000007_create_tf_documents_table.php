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
        Schema::create('tf_documents', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->bigInteger('constructor_id');
            $table->bigInteger('account_id');
            $table->bigInteger('access_hash')->nullable();
            $table->integer('date')->nullable();
            $table->text('mime_type')->nullable();
            $table->bigInteger('size')->nullable();
            $table->integer('dc_id')->nullable();
            $table->binary('file_reference')->nullable();
            $table->jsonb('tl_data');
            $table->timestamps();
            $table->primary(['id', 'account_id']);
            $table->index('account_id', 'ix_tf_documents_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_documents');
    }
};
