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
        Schema::create('tl_payments_suggested_star_ref_bots_suggested_2b419606faf4', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('count')->nullable();
            $table->text('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a7a461d3652ba55326d46e8d');
            $table->index('account_id', 'ix_98fd502db7f7988fead12d58');
        });
        Schema::create('tl_payments_suggested_star_ref_bots_suggested_493afd9c3485', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_payments_suggested_star_ref_bots_suggested_2b419606faf4', 'id', 'fk_6f981f4aa2006b97dbd2a38b')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c6791c037d698bb5565f');
            $table->index('account_id', 'ix_ebf29bbc55b8d3ecfe2eac48');
        });
        Schema::create('tl_payments_suggested_star_ref_bots_suggested_a10aaedcb3f4', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_payments_suggested_star_ref_bots_suggested_2b419606faf4', 'id', 'fk_ac5f3584f4c0ec3ed81dac2f')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b26676c652122e31bac1');
            $table->index('account_id', 'ix_915db393a00877080d7abee8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_suggested_star_ref_bots_suggested_a10aaedcb3f4');
        Schema::dropIfExists('tl_payments_suggested_star_ref_bots_suggested_493afd9c3485');
        Schema::dropIfExists('tl_payments_suggested_star_ref_bots_suggested_2b419606faf4');
    }
};
