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
        Schema::create('tl_payments_resale_star_gifts_resale_star_gifts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('count')->nullable();
            $table->text('next_offset')->nullable();
            $table->bigInteger('attributes_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_17cd0787f0c57b32b61a5234');
            $table->index('account_id', 'ix_b36db263dba3c85511f0ca64');
        });
        Schema::create('tl_payments_resale_star_gifts_resale_star_gifts__gifts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_payments_resale_star_gifts_resale_star_gifts', 'id', 'fk_05a48d52a3fe7c611597742c')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d8debd141496a4be9a0d');
            $table->index('account_id', 'ix_6f03744805c8e7bf04e9aa8d');
        });
        Schema::create('tl_payments_resale_star_gifts_resale_star_gif_3aab68d26831', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_payments_resale_star_gifts_resale_star_gifts', 'id', 'fk_fa9b3755701b685592f32324')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0a7656cbc809918a5f49');
            $table->index('account_id', 'ix_a2722ef900b7b36a1e3a2e48');
        });
        Schema::create('tl_payments_resale_star_gifts_resale_star_gifts__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_payments_resale_star_gifts_resale_star_gifts', 'id', 'fk_67f260a04d0aac1f59466819')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c2320b6473f2040c6d96');
            $table->index('account_id', 'ix_2397b253650c3f02a65c0e97');
        });
        Schema::create('tl_payments_resale_star_gifts_resale_star_gifts__counters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_payments_resale_star_gifts_resale_star_gifts', 'id', 'fk_a6f6e09ada9bbb05f68ff6bd')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0be14209e581e46ff705');
            $table->index('account_id', 'ix_d992f60f74f458d1dee9f235');
        });
        Schema::create('tl_payments_resale_star_gifts_resale_star_gifts__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_payments_resale_star_gifts_resale_star_gifts', 'id', 'fk_0175b5330f1b45a22aff6392')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5c0483336ab72aaad5b2');
            $table->index('account_id', 'ix_e88eed58a12784a31bb32e92');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_resale_star_gifts_resale_star_gifts__users');
        Schema::dropIfExists('tl_payments_resale_star_gifts_resale_star_gifts__counters');
        Schema::dropIfExists('tl_payments_resale_star_gifts_resale_star_gifts__chats');
        Schema::dropIfExists('tl_payments_resale_star_gifts_resale_star_gif_3aab68d26831');
        Schema::dropIfExists('tl_payments_resale_star_gifts_resale_star_gifts__gifts');
        Schema::dropIfExists('tl_payments_resale_star_gifts_resale_star_gifts');
    }
};
