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
        Schema::create('tl_upload_web_file_web_file', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_size')->nullable();
            $table->text('mime_type')->nullable();
            $table->bigInteger('file_type')->nullable();
            $table->index('file_type', 'ix_55d4ecffe00d420f2f2db094');
            $table->integer('mtime')->nullable();
            $table->binary('bytes')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1b48d51b78b767fc724c7864');
            $table->index('account_id', 'ix_51202d498b19c212778a35b1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_upload_web_file_web_file');
    }
};
