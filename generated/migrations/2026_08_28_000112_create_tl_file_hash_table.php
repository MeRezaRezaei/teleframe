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
        Schema::create('tl_file_hash', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_file_hash_file_hash', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_file_hash')->cascadeOnDelete();
            $table->bigInteger('tl_offset');
            $table->integer('tl_limit');
            $table->binary('hash');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_file_hash_file_hash');
        Schema::dropIfExists('tl_file_hash');
    }
};
