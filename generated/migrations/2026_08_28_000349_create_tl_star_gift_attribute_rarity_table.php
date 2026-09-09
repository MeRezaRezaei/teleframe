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
        Schema::create('tl_star_gift_attribute_rarity', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_3d483ba5d2aaaf1ca86b13fc');
            $table->index('account_id', 'ix_7298337fdb549e2aa94fb643');
        });
        Schema::create('tl_star_gift_attribute_rarity_star_gift_attribute_rarity', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_attribute_rarity')->cascadeOnDelete();
            $table->integer('permille');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1e445bc221c6fc73365ece62');
        });
        Schema::create('tl_star_gift_attribute_rarity_star_gift_attri_ad46f7fd208c', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_attribute_rarity')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_661c380383f22f9252a20c93');
        });
        Schema::create('tl_star_gift_attribute_rarity_star_gift_attri_5570ca926404', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_attribute_rarity')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_951b7c46ff7849f52fd838a3');
        });
        Schema::create('tl_star_gift_attribute_rarity_star_gift_attri_472f872db7c2', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_attribute_rarity')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b781debb23dc19a8a843a0b4');
        });
        Schema::create('tl_star_gift_attribute_rarity_star_gift_attri_3936099eb309', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_attribute_rarity')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_dcceb5ab49c95def9f64bf0b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_attribute_rarity_star_gift_attri_3936099eb309');
        Schema::dropIfExists('tl_star_gift_attribute_rarity_star_gift_attri_472f872db7c2');
        Schema::dropIfExists('tl_star_gift_attribute_rarity_star_gift_attri_5570ca926404');
        Schema::dropIfExists('tl_star_gift_attribute_rarity_star_gift_attri_ad46f7fd208c');
        Schema::dropIfExists('tl_star_gift_attribute_rarity_star_gift_attribute_rarity');
        Schema::dropIfExists('tl_star_gift_attribute_rarity');
    }
};
