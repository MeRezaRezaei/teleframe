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
        Schema::create('tl_help_premium_promo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_help_premium_promo_premium_promo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_premium_promo')->cascadeOnDelete();
            $table->text('status_text');
            $table->timestamps();
        });
        Schema::create('tl_help_premium_promo_premium_promo__status_entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_premium_promo_premium_promo')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_87af37cba24c6d59712c');
        });
        Schema::create('tl_help_premium_promo_premium_promo__video_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_premium_promo_premium_promo')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d723eac7c794fbc396a0');
        });
        Schema::create('tl_help_premium_promo_premium_promo__videos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_premium_promo_premium_promo')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_97eec5922e2a8c0e4d99');
        });
        Schema::create('tl_help_premium_promo_premium_promo__period_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_premium_promo_premium_promo')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_467e42436e38cca63aba');
        });
        Schema::create('tl_help_premium_promo_premium_promo__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_premium_promo_premium_promo')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_76880cb765dcebbe3e4b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_premium_promo_premium_promo__users');
        Schema::dropIfExists('tl_help_premium_promo_premium_promo__period_options');
        Schema::dropIfExists('tl_help_premium_promo_premium_promo__videos');
        Schema::dropIfExists('tl_help_premium_promo_premium_promo__video_sections');
        Schema::dropIfExists('tl_help_premium_promo_premium_promo__status_entities');
        Schema::dropIfExists('tl_help_premium_promo_premium_promo');
        Schema::dropIfExists('tl_help_premium_promo');
    }
};
