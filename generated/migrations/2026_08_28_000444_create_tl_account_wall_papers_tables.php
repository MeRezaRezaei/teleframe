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
        Schema::create('tl_account_wall_papers_wall_papers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a69444d7c66db0ac573bee8a');
            $table->index('account_id', 'ix_7759a1520614bff3f0e8f919');
        });
        Schema::create('tl_account_wall_papers_wall_papers__wallpapers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_9e63c903b887944a6ba64d94')->references('id')->on('tl_account_wall_papers_wall_papers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_bc94a06e07bd62754c83');
            $table->index('account_id', 'ix_df91744e089c6112451a4db3');
        });
        Schema::create('tl_account_wall_papers_wall_papers_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4c6bfb33fd698248fb09758f');
            $table->index('account_id', 'ix_5cc67ff9f1b7da5ed60b6acc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_wall_papers_wall_papers_not_modified');
        Schema::dropIfExists('tl_account_wall_papers_wall_papers__wallpapers');
        Schema::dropIfExists('tl_account_wall_papers_wall_papers');
    }
};
