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
        Schema::create('tl_upload_cdn_file_cdn_file', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->binary('bytes')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8875c3098ba04d4e15f4a169');
            $table->index('account_id', 'ix_f47434793b6a66afe324f81d');
        });
        Schema::create('tl_upload_cdn_file_cdn_file_reupload_needed', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->binary('request_token')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1a491a21cd5691ee17c6215a');
            $table->index('account_id', 'ix_b44d2f062c130d19f4713a05');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_upload_cdn_file_cdn_file_reupload_needed');
        Schema::dropIfExists('tl_upload_cdn_file_cdn_file');
    }
};
