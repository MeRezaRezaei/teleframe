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
        Schema::create('tl_shipping_option', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_a56bddab33c934905b6b2e41');
            $table->index('account_id', 'ix_21ae420859f5605926a93356');
        });
        Schema::create('tl_shipping_option_shipping_option', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_shipping_option')->cascadeOnDelete();
            $table->text('tl_id');
            $table->text('title');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8a096a4e2f9ac7a0cb79b2ec');
            $table->unique(['account_id', 'tl_id'], 'ux_6fb37e76797c491db56e');
        });
        Schema::create('tl_shipping_option_shipping_option__prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_shipping_option_shipping_option')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_7405724728c125bc263d');
            $table->index('account_id', 'ix_90d874e801ade91a489687bd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_shipping_option_shipping_option__prices');
        Schema::dropIfExists('tl_shipping_option_shipping_option');
        Schema::dropIfExists('tl_shipping_option');
    }
};
