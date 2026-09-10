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
        Schema::create('tl_photos_photos_photos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_42deff461ee2e783870eed0c');
            $table->index('account_id', 'ix_3b4c4fc7310ac973169569c4');
        });
        Schema::create('tl_photos_photos_photos__photos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_photos_photos_photos', 'id', 'fk_1a29b3b0fc61f0d87a835280')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_22eb0c90e9edc9747469');
            $table->index('account_id', 'ix_fcb24f70b86f8e70f99ec76f');
        });
        Schema::create('tl_photos_photos_photos__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_photos_photos_photos', 'id', 'fk_b61a1138b565aeb79f2a0e92')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ffa5f9e8aa6ac298a1bf');
            $table->index('account_id', 'ix_60131d96d31f4795aba49169');
        });
        Schema::create('tl_photos_photos_photos_slice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_858d96f872eeb517b0f23284');
            $table->index('account_id', 'ix_aea154309bb313a8c56ef07c');
        });
        Schema::create('tl_photos_photos_photos_slice__photos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_photos_photos_photos_slice', 'id', 'fk_0ab37acb2f683208e1b3916a')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ca998ab24ca1b00d0d32');
            $table->index('account_id', 'ix_75a89c86c1d74cf481325109');
        });
        Schema::create('tl_photos_photos_photos_slice__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_photos_photos_photos_slice', 'id', 'fk_3a3af5ee5b7ab583f8cce5b3')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_84a2ce5ba9f56e96415e');
            $table->index('account_id', 'ix_e446e8e4fd246f2e7215332f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_photos_photos_photos_slice__users');
        Schema::dropIfExists('tl_photos_photos_photos_slice__photos');
        Schema::dropIfExists('tl_photos_photos_photos_slice');
        Schema::dropIfExists('tl_photos_photos_photos__users');
        Schema::dropIfExists('tl_photos_photos_photos__photos');
        Schema::dropIfExists('tl_photos_photos_photos');
    }
};
