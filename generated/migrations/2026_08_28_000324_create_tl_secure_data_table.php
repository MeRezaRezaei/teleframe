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
        Schema::create('tl_secure_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_9fb28a0b69436ae3821d164b');
            $table->index('account_id', 'ix_8af313abd89593e9efb3a317');
        });
        Schema::create('tl_secure_data_secure_data', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_data')->cascadeOnDelete();
            $table->binary('data');
            $table->binary('data_hash');
            $table->binary('secret');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cd6803d4a4ead8bdf6bb1699');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_data_secure_data');
        Schema::dropIfExists('tl_secure_data');
    }
};
