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
        Schema::create('tl_help_premium_promo_premium_promo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('status_text');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_321ab53451683a817a861bc6');
            $table->index('account_id', 'ix_28419d30fb3aa790ca7d6037');
        });
        Schema::create('tl_help_premium_promo_premium_promo__status_entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_help_premium_promo_premium_promo', 'id', 'fk_2cc57e2d82096ab08070f1e9')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_87af37cba24c6d59712c');
            $table->index('account_id', 'ix_374cc869af11fc208e3864c8');
        });
        Schema::create('tl_help_premium_promo_premium_promo__video_sections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_help_premium_promo_premium_promo', 'id', 'fk_53e78fd427f488d7fc1a3bc3')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d723eac7c794fbc396a0');
            $table->index('account_id', 'ix_c137eeefc6c68fe6c65a09e3');
        });
        Schema::create('tl_help_premium_promo_premium_promo__videos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_help_premium_promo_premium_promo', 'id', 'fk_9088c54a9a3a00ee640b876a')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_97eec5922e2a8c0e4d99');
            $table->index('account_id', 'ix_7d941c7aa5c3f590e9c7682c');
        });
        Schema::create('tl_help_premium_promo_premium_promo__period_options', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_help_premium_promo_premium_promo', 'id', 'fk_6f8755b291e62a35d32f5382')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_467e42436e38cca63aba');
            $table->index('account_id', 'ix_fdc5726cc03c510e6a46bc77');
        });
        Schema::create('tl_help_premium_promo_premium_promo__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_help_premium_promo_premium_promo', 'id', 'fk_446bb1e1a2fa32f41c4bb015')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_76880cb765dcebbe3e4b');
            $table->index('account_id', 'ix_70c1ec5363b83aca9fa8255c');
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
    }
};
