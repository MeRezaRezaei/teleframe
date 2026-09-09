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
        Schema::create('tl_input_star_gift_auction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_0935b5613a4413ef0095a96d');
            $table->index('account_id', 'ix_8cbab224ab78203eff06dbbe');
        });
        Schema::create('tl_input_star_gift_auction_input_star_gift_auction', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_star_gift_auction')->cascadeOnDelete();
            $table->bigInteger('gift_id');
            $table->index('gift_id', 'ix_bde5e2f3ed7bc5829a39bf31');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_07322297fab3dfc762178645');
        });
        Schema::create('tl_input_star_gift_auction_input_star_gift_auction_slug', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_star_gift_auction')->cascadeOnDelete();
            $table->text('slug');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_88b6b9a05ad973b2efd6f446');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_star_gift_auction_input_star_gift_auction_slug');
        Schema::dropIfExists('tl_input_star_gift_auction_input_star_gift_auction');
        Schema::dropIfExists('tl_input_star_gift_auction');
    }
};
