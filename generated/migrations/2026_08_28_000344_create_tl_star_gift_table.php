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
        Schema::create('tl_star_gift', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_star_gift_star_gift', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('limited')->default(false);
            $table->boolean('sold_out')->default(false);
            $table->boolean('birthday')->default(false);
            $table->boolean('require_premium')->default(false);
            $table->boolean('limited_per_user')->default(false);
            $table->boolean('peer_color_available')->default(false);
            $table->boolean('auction')->default(false);
            $table->bigInteger('tl_id');
            $table->uuid('sticker');
            $table->bigInteger('stars');
            $table->integer('availability_remains')->nullable();
            $table->integer('availability_total')->nullable();
            $table->bigInteger('availability_resale')->nullable();
            $table->bigInteger('convert_stars');
            $table->integer('first_sale_date')->nullable();
            $table->integer('last_sale_date')->nullable();
            $table->bigInteger('upgrade_stars')->nullable();
            $table->bigInteger('resell_min_stars')->nullable();
            $table->text('title')->nullable();
            $table->uuid('released_by')->nullable();
            $table->integer('per_user_total')->nullable();
            $table->integer('per_user_remains')->nullable();
            $table->integer('locked_until_date')->nullable();
            $table->text('auction_slug')->nullable();
            $table->integer('gifts_per_round')->nullable();
            $table->integer('auction_start_date')->nullable();
            $table->integer('upgrade_variants')->nullable();
            $table->uuid('background')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_star_gift_star_gift_unique', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('require_premium')->default(false);
            $table->boolean('resale_ton_only')->default(false);
            $table->boolean('theme_available')->default(false);
            $table->boolean('burned')->default(false);
            $table->boolean('crafted')->default(false);
            $table->bigInteger('tl_id');
            $table->bigInteger('gift_id');
            $table->text('title');
            $table->text('slug');
            $table->integer('num');
            $table->uuid('owner_id')->nullable();
            $table->text('owner_name')->nullable();
            $table->text('owner_address')->nullable();
            $table->integer('availability_issued');
            $table->integer('availability_total');
            $table->text('gift_address')->nullable();
            $table->uuid('released_by')->nullable();
            $table->bigInteger('value_amount')->nullable();
            $table->text('value_currency')->nullable();
            $table->bigInteger('value_usd_amount')->nullable();
            $table->uuid('theme_peer')->nullable();
            $table->uuid('peer_color')->nullable();
            $table->uuid('host_id')->nullable();
            $table->integer('offer_min_stars')->nullable();
            $table->integer('craft_chance_permille')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_star_gift_star_gift_unique__attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_star_gift_star_gift_unique')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_076962ca5dcb00c86e55');
        });
        Schema::create('tl_star_gift_star_gift_unique__resell_amount', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_star_gift_star_gift_unique')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_747ddeb652121dda4bca');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_star_gift_unique__resell_amount');
        Schema::dropIfExists('tl_star_gift_star_gift_unique__attributes');
        Schema::dropIfExists('tl_star_gift_star_gift_unique');
        Schema::dropIfExists('tl_star_gift_star_gift');
        Schema::dropIfExists('tl_star_gift');
    }
};
