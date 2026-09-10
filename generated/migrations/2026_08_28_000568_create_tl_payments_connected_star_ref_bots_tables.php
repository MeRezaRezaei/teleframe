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
        Schema::create('tl_payments_connected_star_ref_bots_connected_73bca7385b9a', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5515028fdf52c8ebf7f7c342');
            $table->index('account_id', 'ix_a31584e27f9a6963b10f257e');
        });
        Schema::create('tl_payments_connected_star_ref_bots_connected_a1abdb5c778e', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_payments_connected_star_ref_bots_connected_73bca7385b9a', 'id', 'fk_61ff3e83661e945eee5dd044')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d0325b0c850d0aff4032');
            $table->index('account_id', 'ix_b24b8f030f20afd1c13a230a');
        });
        Schema::create('tl_payments_connected_star_ref_bots_connected_552aa98cbdb5', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_payments_connected_star_ref_bots_connected_73bca7385b9a', 'id', 'fk_c1a4a120ab485c7e9e38268d')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_20542ca4e87b270d4c39');
            $table->index('account_id', 'ix_02bc45f5da962d91ce1e1795');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_connected_star_ref_bots_connected_552aa98cbdb5');
        Schema::dropIfExists('tl_payments_connected_star_ref_bots_connected_a1abdb5c778e');
        Schema::dropIfExists('tl_payments_connected_star_ref_bots_connected_73bca7385b9a');
    }
};
