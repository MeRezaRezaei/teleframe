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
        Schema::create('tl_payments_star_gift_auction_acquired_gifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_bde2a17fc24155b16facdbcc');
            $table->index('account_id', 'ix_95837916b885b6cf42aac4fc');
        });
        Schema::create('tl_payments_star_gift_auction_acquired_gifts__3ef8dccf2514', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_star_gift_auction_acquired_gifts')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5df9688bd133c79a323da7b6');
        });
        Schema::create('tl_payments_star_gift_auction_acquired_gifts__7d5c2644ce05', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_auction_acquired_gifts__3ef8dccf2514')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f76a3812bf1cc91ce32e');
            $table->index('account_id', 'ix_2b5410e0f5f779c4d684766c');
        });
        Schema::create('tl_payments_star_gift_auction_acquired_gifts__239c224337a5', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_auction_acquired_gifts__3ef8dccf2514')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_39049c53977af7e5e262');
            $table->index('account_id', 'ix_338a5992b7aa4dcebe8d1e17');
        });
        Schema::create('tl_payments_star_gift_auction_acquired_gifts__cd07c7958d0f', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_auction_acquired_gifts__3ef8dccf2514')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
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
        Schema::dropIfExists('tl_payments_star_gift_auction_acquired_gifts');
    }
};
