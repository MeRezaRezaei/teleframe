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
        Schema::create('tl_account_wall_papers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b7eb32b06e9995e5f3d388ea');
            $table->index('account_id', 'ix_1b8bdeea448782c71fb335dc');
        });
        Schema::create('tl_account_wall_papers_wall_papers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_wall_papers')->cascadeOnDelete();
            $table->bigInteger('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7759a1520614bff3f0e8f919');
        });
        Schema::create('tl_account_wall_papers_wall_papers__wallpapers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_wall_papers_wall_papers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_bc94a06e07bd62754c83');
            $table->index('account_id', 'ix_df91744e089c6112451a4db3');
        });
        Schema::create('tl_account_wall_papers_wall_papers_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_wall_papers')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5cc67ff9f1b7da5ed60b6acc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_wall_papers_wall_papers_not_modified');
        Schema::dropIfExists('tl_account_wall_papers_wall_papers__wallpapers');
        Schema::dropIfExists('tl_account_wall_papers_wall_papers');
        Schema::dropIfExists('tl_account_wall_papers');
    }
};
