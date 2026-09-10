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
        Schema::create('tl_star_gift_attribute_rarity_star_gift_attribute_rarity', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('permille')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_24827973ef3a8c9a5af3f4d3');
            $table->index('account_id', 'ix_1e445bc221c6fc73365ece62');
        });
        Schema::create('tl_star_gift_attribute_rarity_star_gift_attri_ad46f7fd208c', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8f3e97cc37c8382bb5b4a1f3');
            $table->index('account_id', 'ix_661c380383f22f9252a20c93');
        });
        Schema::create('tl_star_gift_attribute_rarity_star_gift_attri_5570ca926404', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a63862b1e76e6c48ff115fb3');
            $table->index('account_id', 'ix_951b7c46ff7849f52fd838a3');
        });
        Schema::create('tl_star_gift_attribute_rarity_star_gift_attri_472f872db7c2', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_92d4383f7e4e58f20b653384');
            $table->index('account_id', 'ix_b781debb23dc19a8a843a0b4');
        });
        Schema::create('tl_star_gift_attribute_rarity_star_gift_attri_3936099eb309', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_734117987f0b7887e27d9480');
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
    }
};
