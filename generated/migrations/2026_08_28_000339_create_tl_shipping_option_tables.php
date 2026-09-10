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
        Schema::create('tl_shipping_option_shipping_option', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_id')->nullable();
            $table->text('title')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4abda5cbf198cacf40775256');
            $table->index('account_id', 'ix_8a096a4e2f9ac7a0cb79b2ec');
            $table->unique(['account_id'], 'ux_6fb37e76797c491db56e');
        });
        Schema::create('tl_shipping_option_shipping_option__prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_shipping_option_shipping_option', 'id', 'fk_e7e26fbbf9a0b46b30c380cd')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_7405724728c125bc263d');
            $table->index('account_id', 'ix_90d874e801ade91a489687bd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_shipping_option_shipping_option__prices');
        Schema::dropIfExists('tl_shipping_option_shipping_option');
    }
};
