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
        Schema::create('tl_secure_value', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_secure_value_secure_value', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('tl_type');
            $table->uuid('data')->nullable();
            $table->uuid('front_side')->nullable();
            $table->uuid('reverse_side')->nullable();
            $table->uuid('selfie')->nullable();
            $table->uuid('plain_data')->nullable();
            $table->binary('hash');
            $table->timestamps();
        });
        Schema::create('tl_secure_value_secure_value__translation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_secure_value_secure_value')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ddeb26c2c6638797b991');
        });
        Schema::create('tl_secure_value_secure_value__files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_secure_value_secure_value')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ea7e2e151c772621cae1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_value_secure_value__files');
        Schema::dropIfExists('tl_secure_value_secure_value__translation');
        Schema::dropIfExists('tl_secure_value_secure_value');
        Schema::dropIfExists('tl_secure_value');
    }
};
