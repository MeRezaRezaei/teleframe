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
        Schema::create('tl_cdn_config_cdn_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8244502b14b88b1bcdf053ac');
            $table->index('account_id', 'ix_cc91a254ce999fa2f172459d');
        });
        Schema::create('tl_cdn_config_cdn_config__public_keys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_89a864a96ec4e8bf99212d6c')->references('id')->on('tl_cdn_config_cdn_config')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_91f95444ff45c073df5f');
            $table->index('account_id', 'ix_a8a34629f12bfcc9d193b9b0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_cdn_config_cdn_config__public_keys');
        Schema::dropIfExists('tl_cdn_config_cdn_config');
    }
};
