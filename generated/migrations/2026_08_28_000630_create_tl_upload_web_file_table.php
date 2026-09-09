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
        Schema::create('tl_upload_web_file', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8daf98df63b19ee8b3c3cfab');
            $table->index('account_id', 'ix_f218a33ec55b75522ae10680');
        });
        Schema::create('tl_upload_web_file_web_file', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_upload_web_file')->cascadeOnDelete();
            $table->integer('tl_size');
            $table->text('mime_type');
            $table->uuid('file_type');
            $table->index('file_type', 'ix_55d4ecffe00d420f2f2db094');
            $table->integer('mtime');
            $table->binary('bytes');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_51202d498b19c212778a35b1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_upload_web_file_web_file');
        Schema::dropIfExists('tl_upload_web_file');
    }
};
