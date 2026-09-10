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
        Schema::create('tl_payments_star_gift_auction_acquired_gifts__3ef8dccf2514', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ef748b441179e9f9118bab6f');
            $table->index('account_id', 'ix_5df9688bd133c79a323da7b6');
        });
        Schema::create('tl_payments_star_gift_auction_acquired_gifts__7d5c2644ce05', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_b934bff7b22343edacbaef3f')->references('id')->on('tl_payments_star_gift_auction_acquired_gifts__3ef8dccf2514')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f76a3812bf1cc91ce32e');
            $table->index('account_id', 'ix_2b5410e0f5f779c4d684766c');
        });
        Schema::create('tl_payments_star_gift_auction_acquired_gifts__239c224337a5', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_ed86acbaaec4a33ca53d52af')->references('id')->on('tl_payments_star_gift_auction_acquired_gifts__3ef8dccf2514')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_39049c53977af7e5e262');
            $table->index('account_id', 'ix_338a5992b7aa4dcebe8d1e17');
        });
        Schema::create('tl_payments_star_gift_auction_acquired_gifts__cd07c7958d0f', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_beae103a23046d73da4aa1f8')->references('id')->on('tl_payments_star_gift_auction_acquired_gifts__3ef8dccf2514')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1c6cc298460e2d934699');
            $table->index('account_id', 'ix_b5240616f07c992a594c603f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_star_gift_auction_acquired_gifts__cd07c7958d0f');
        Schema::dropIfExists('tl_payments_star_gift_auction_acquired_gifts__239c224337a5');
        Schema::dropIfExists('tl_payments_star_gift_auction_acquired_gifts__7d5c2644ce05');
        Schema::dropIfExists('tl_payments_star_gift_auction_acquired_gifts__3ef8dccf2514');
    }
};
