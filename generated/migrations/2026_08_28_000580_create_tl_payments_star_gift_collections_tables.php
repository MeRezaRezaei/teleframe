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
        Schema::create('tl_payments_star_gift_collections_star_gift_collections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_86356e6998db3aafb8495f54');
            $table->index('account_id', 'ix_dcc95841c278ab804841b529');
        });
        Schema::create('tl_payments_star_gift_collections_star_gift_c_3c2191db9981', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_payments_star_gift_collections_star_gift_collections', 'id', 'fk_4bfdf43bae3d56d39ef3a09f')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_930a1cd8c51888c0d18b');
            $table->index('account_id', 'ix_b6edbda70ef030bdb65e069b');
        });
        Schema::create('tl_payments_star_gift_collections_star_gift_c_72774827a67d', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e088c9aa81a9b6064ac5e746');
            $table->index('account_id', 'ix_2def4d9f87b2c82b97e49294');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_star_gift_collections_star_gift_c_72774827a67d');
        Schema::dropIfExists('tl_payments_star_gift_collections_star_gift_c_3c2191db9981');
        Schema::dropIfExists('tl_payments_star_gift_collections_star_gift_collections');
    }
};
