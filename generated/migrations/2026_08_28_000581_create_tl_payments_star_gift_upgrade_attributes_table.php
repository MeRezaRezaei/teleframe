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
        Schema::create('tl_payments_star_gift_upgrade_attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_6e5b221a4f60a58a296a31e1');
            $table->index('account_id', 'ix_6ffdafd1b0b4b2e036b80355');
        });
        Schema::create('tl_payments_star_gift_upgrade_attributes_star_b00cb34f5cf4', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_star_gift_upgrade_attributes')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d1235b246a823d9de193533a');
        });
        Schema::create('tl_payments_star_gift_upgrade_attributes_star_badc40fe3ef8', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_upgrade_attributes_star_b00cb34f5cf4')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_930223e281e1861e86cb');
            $table->index('account_id', 'ix_3d971211ba5b5a5c480bfb12');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_star_gift_upgrade_attributes_star_badc40fe3ef8');
        Schema::dropIfExists('tl_payments_star_gift_upgrade_attributes_star_b00cb34f5cf4');
        Schema::dropIfExists('tl_payments_star_gift_upgrade_attributes');
    }
};
