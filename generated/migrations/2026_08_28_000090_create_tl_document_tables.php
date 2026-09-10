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
        Schema::create('tl_document_document', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->binary('file_reference')->nullable();
            $table->integer('date')->nullable();
            $table->text('mime_type')->nullable();
            $table->bigInteger('tl_size')->nullable();
            $table->integer('dc_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_069d063824affb368007ff2e');
            $table->index('account_id', 'ix_d996f478686a05864c7ed2ab');
        });
        Schema::create('tl_document_document__thumbs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_document_document', 'id', 'fk_7da113b0160f05446f474446')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ea8267eae15b64501cb2');
            $table->index('account_id', 'ix_9e18f1b3899085ee7c8c3869');
        });
        Schema::create('tl_document_document__video_thumbs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_document_document', 'id', 'fk_3aa336540c44ecfee19b4bb3')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e7fa625dcf972ddc8429');
            $table->index('account_id', 'ix_aed49c05e0fedfcdd279a44f');
        });
        Schema::create('tl_document_document__attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_document_document', 'id', 'fk_4e08e1e3b5baf9e2a1997073')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6d9066d04be16b0de4dc');
            $table->index('account_id', 'ix_939eb536a889cff7fb5fff9d');
        });
        Schema::create('tl_document_document_empty', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dd3c14ab209b26b3f8bd5fd3');
            $table->index('account_id', 'ix_59b3a9e2688309b5520acf2c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_document_document_empty');
        Schema::dropIfExists('tl_document_document__attributes');
        Schema::dropIfExists('tl_document_document__video_thumbs');
        Schema::dropIfExists('tl_document_document__thumbs');
        Schema::dropIfExists('tl_document_document');
    }
};
