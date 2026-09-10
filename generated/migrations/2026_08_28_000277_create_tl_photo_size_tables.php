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
        Schema::create('tl_photo_size_photo_cached_size', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_type')->nullable();
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->binary('bytes')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_246df214eb5beb879961a8e6');
            $table->index('account_id', 'ix_7e8bdee12297f3769a3862d5');
        });
        Schema::create('tl_photo_size_photo_path_size', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_type')->nullable();
            $table->binary('bytes')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f2c52ce40e5e8bc36cc1bd79');
            $table->index('account_id', 'ix_c6bd1e8a1bce3cca67213f4f');
        });
        Schema::create('tl_photo_size_photo_size', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_type')->nullable();
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->integer('tl_size')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b96acb2a646b0347fd15ee1f');
            $table->index('account_id', 'ix_2554d9b94b43ca099c401ebf');
        });
        Schema::create('tl_photo_size_photo_size_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_type')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_58a5d39a69f3d1ec29198eb4');
            $table->index('account_id', 'ix_b14e6cc93bd2514616b79ff4');
        });
        Schema::create('tl_photo_size_photo_size_progressive', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_type')->nullable();
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f6128879075198a4e280ac5e');
            $table->index('account_id', 'ix_1b30fa0576e9119d2e0d86e0');
        });
        Schema::create('tl_photo_size_photo_size_progressive__sizes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_photo_size_photo_size_progressive', 'id', 'fk_8c956f4c7b32cf7fec5e044f')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b604eca0ff5967528041');
            $table->index('account_id', 'ix_d3fa36eb0f026fa0f19afde0');
        });
        Schema::create('tl_photo_size_photo_stripped_size', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_type')->nullable();
            $table->binary('bytes')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_77d06cf6b681de00aa02e0b6');
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
    }
};
