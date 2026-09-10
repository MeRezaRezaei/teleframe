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
        Schema::create('tl_input_web_document_input_web_document', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->integer('tl_size')->nullable();
            $table->text('mime_type')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4ec06ebed989fdddc66f448d');
            $table->index('account_id', 'ix_79cdf171594109481b3d7346');
        });
        Schema::create('tl_input_web_document_input_web_document__attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_4e746843a751c58acb030f58')->references('id')->on('tl_input_web_document_input_web_document')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0bfabeb4ccecc076e097');
            $table->index('account_id', 'ix_068b28778db0080a5698e50f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_web_document_input_web_document__attributes');
        Schema::dropIfExists('tl_input_web_document_input_web_document');
    }
};
