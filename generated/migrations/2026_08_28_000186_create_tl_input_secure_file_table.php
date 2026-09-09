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
        Schema::create('tl_input_secure_file', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8b46f73096dffd6073846e7e');
            $table->index('account_id', 'ix_f1d848b17548beb21eee4ea9');
        });
        Schema::create('tl_input_secure_file_input_secure_file', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_secure_file')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ceb3ffed45e6765009e1e922');
            $table->unique(['account_id', 'tl_id'], 'ux_d25fc3d59bbb5c441322');
        });
        Schema::create('tl_input_secure_file_input_secure_file_uploaded', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_secure_file')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->integer('parts');
            $table->text('md5_checksum');
            $table->binary('file_hash');
            $table->binary('secret');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2ab1f756262eca86271170a8');
            $table->unique(['account_id', 'tl_id'], 'ux_0f1c5d0ec71676cf16a3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_secure_file_input_secure_file_uploaded');
        Schema::dropIfExists('tl_input_secure_file_input_secure_file');
        Schema::dropIfExists('tl_input_secure_file');
    }
};
