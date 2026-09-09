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
        Schema::create('tl_account_saved_music_ids', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_79280f6e95263a38ac877618');
            $table->index('account_id', 'ix_f9834e57f04b985f9cbe5c4c');
        });
        Schema::create('tl_account_saved_music_ids_saved_music_ids', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_saved_music_ids')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0c3c20a5e8a6c94ec21439d8');
        });
        Schema::create('tl_account_saved_music_ids_saved_music_ids__ids', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_saved_music_ids_saved_music_ids')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_12e3d4688db4c14714a8');
            $table->index('account_id', 'ix_bb89ff64b40ef3cde1bcd951');
        });
        Schema::create('tl_account_saved_music_ids_saved_music_ids_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_saved_music_ids')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ad85a20ca26e1c49dd06576e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_saved_music_ids_saved_music_ids_not_modified');
        Schema::dropIfExists('tl_account_saved_music_ids_saved_music_ids__ids');
        Schema::dropIfExists('tl_account_saved_music_ids_saved_music_ids');
        Schema::dropIfExists('tl_account_saved_music_ids');
    }
};
