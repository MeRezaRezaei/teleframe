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
        Schema::create('tl_input_star_gift_auction_input_star_gift_auction', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('gift_id')->nullable();
            $table->index('gift_id', 'ix_bde5e2f3ed7bc5829a39bf31');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_202b6a2e8d15a5c331a3753c');
            $table->index('account_id', 'ix_07322297fab3dfc762178645');
        });
        Schema::create('tl_input_star_gift_auction_input_star_gift_auction_slug', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('slug')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d5c25240f9d8bd4371f663e5');
            $table->index('account_id', 'ix_88b6b9a05ad973b2efd6f446');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_star_gift_auction_input_star_gift_auction_slug');
        Schema::dropIfExists('tl_input_star_gift_auction_input_star_gift_auction');
    }
};
