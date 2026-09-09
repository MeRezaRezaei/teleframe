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
        Schema::create('tl_payments_star_gifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_ea964c9667e490f691199dba');
            $table->index('account_id', 'ix_e3b2517c089d2d8ea51538c6');
        });
        Schema::create('tl_payments_star_gifts_star_gifts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_star_gifts')->cascadeOnDelete();
            $table->integer('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_df10f55efaa97202eadbcd70');
        });
        Schema::create('tl_payments_star_gifts_star_gifts__gifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gifts_star_gifts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_658bd0a018677c179bba');
            $table->index('account_id', 'ix_a8ece23ac16b632abde84b4f');
        });
        Schema::create('tl_payments_star_gifts_star_gifts__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gifts_star_gifts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ca61ec2ae8861bc1c321');
            $table->index('account_id', 'ix_eb51d4f55142e74a2d719e54');
        });
        Schema::create('tl_payments_star_gifts_star_gifts__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gifts_star_gifts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_3fcdf59d24b0013288a5');
            $table->index('account_id', 'ix_665590cdaacbe629229c0b58');
        });
        Schema::create('tl_payments_star_gifts_star_gifts_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_star_gifts')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d540e7fddb06887723502e88');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_star_gifts_star_gifts_not_modified');
        Schema::dropIfExists('tl_payments_star_gifts_star_gifts__users');
        Schema::dropIfExists('tl_payments_star_gifts_star_gifts__chats');
        Schema::dropIfExists('tl_payments_star_gifts_star_gifts__gifts');
        Schema::dropIfExists('tl_payments_star_gifts_star_gifts');
        Schema::dropIfExists('tl_payments_star_gifts');
    }
};
