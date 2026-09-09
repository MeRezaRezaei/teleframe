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
        Schema::create('tl_aicompose_tones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_4bde14caeda5359c36d884d8');
            $table->index('account_id', 'ix_b5a54f270b3cf1b37d7a6e74');
        });
        Schema::create('tl_aicompose_tones_tones', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_aicompose_tones')->cascadeOnDelete();
            $table->bigInteger('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cb7631ec012f9b11f88983b1');
        });
        Schema::create('tl_aicompose_tones_tones__tones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_aicompose_tones_tones')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b0b728ea789831b1d172');
            $table->index('account_id', 'ix_641577c5580e2c850bfec2b3');
        });
        Schema::create('tl_aicompose_tones_tones__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_aicompose_tones_tones')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6509e41d03471cf85af3');
            $table->index('account_id', 'ix_b6b5935592b68e193539a8a4');
        });
        Schema::create('tl_aicompose_tones_tones_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_aicompose_tones')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a4d0deb41fbe475a4571ae81');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_aicompose_tones_tones_not_modified');
        Schema::dropIfExists('tl_aicompose_tones_tones__users');
        Schema::dropIfExists('tl_aicompose_tones_tones__tones');
        Schema::dropIfExists('tl_aicompose_tones_tones');
        Schema::dropIfExists('tl_aicompose_tones');
    }
};
