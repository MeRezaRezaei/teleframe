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
        Schema::create('tl_payments_connected_star_ref_bots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_payments_connected_star_ref_bots_connected_73bca7385b9a', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_connected_star_ref_bots')->cascadeOnDelete();
            $table->integer('count');
            $table->timestamps();
        });
        Schema::create('tl_payments_connected_star_ref_bots_connected_a1abdb5c778e', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_connected_star_ref_bots_connected_73bca7385b9a')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d0325b0c850d0aff4032');
        });
        Schema::create('tl_payments_connected_star_ref_bots_connected_552aa98cbdb5', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_connected_star_ref_bots_connected_73bca7385b9a')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_20542ca4e87b270d4c39');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_connected_star_ref_bots_connected_552aa98cbdb5');
        Schema::dropIfExists('tl_payments_connected_star_ref_bots_connected_a1abdb5c778e');
        Schema::dropIfExists('tl_payments_connected_star_ref_bots_connected_73bca7385b9a');
        Schema::dropIfExists('tl_payments_connected_star_ref_bots');
    }
};
