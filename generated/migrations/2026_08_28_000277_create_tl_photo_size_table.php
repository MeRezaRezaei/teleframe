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
        Schema::create('tl_photo_size', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_e9d4b9070c291ec37703a60d');
            $table->index('account_id', 'ix_97d3ad5579aed6480a1571a3');
        });
        Schema::create('tl_photo_size_photo_cached_size', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_photo_size')->cascadeOnDelete();
            $table->text('tl_type');
            $table->integer('w');
            $table->integer('h');
            $table->binary('bytes');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7e8bdee12297f3769a3862d5');
        });
        Schema::create('tl_photo_size_photo_path_size', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_photo_size')->cascadeOnDelete();
            $table->text('tl_type');
            $table->binary('bytes');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c6bd1e8a1bce3cca67213f4f');
        });
        Schema::create('tl_photo_size_photo_size', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_photo_size')->cascadeOnDelete();
            $table->text('tl_type');
            $table->integer('w');
            $table->integer('h');
            $table->integer('tl_size');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2554d9b94b43ca099c401ebf');
        });
        Schema::create('tl_photo_size_photo_size_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_photo_size')->cascadeOnDelete();
            $table->text('tl_type');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b14e6cc93bd2514616b79ff4');
        });
        Schema::create('tl_photo_size_photo_size_progressive', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_photo_size')->cascadeOnDelete();
            $table->text('tl_type');
            $table->integer('w');
            $table->integer('h');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1b30fa0576e9119d2e0d86e0');
        });
        Schema::create('tl_photo_size_photo_size_progressive__sizes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_photo_size_photo_size_progressive')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b604eca0ff5967528041');
            $table->index('account_id', 'ix_d3fa36eb0f026fa0f19afde0');
        });
        Schema::create('tl_photo_size_photo_stripped_size', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_photo_size')->cascadeOnDelete();
            $table->text('tl_type');
            $table->binary('bytes');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_811387bbb5354ed3b66c2616');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_photo_size_photo_stripped_size');
        Schema::dropIfExists('tl_photo_size_photo_size_progressive__sizes');
        Schema::dropIfExists('tl_photo_size_photo_size_progressive');
        Schema::dropIfExists('tl_photo_size_photo_size_empty');
        Schema::dropIfExists('tl_photo_size_photo_size');
        Schema::dropIfExists('tl_photo_size_photo_path_size');
        Schema::dropIfExists('tl_photo_size_photo_cached_size');
        Schema::dropIfExists('tl_photo_size');
    }
};
