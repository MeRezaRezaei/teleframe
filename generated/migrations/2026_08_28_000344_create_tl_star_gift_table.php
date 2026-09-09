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
            $table->index('constructor_id', 'ix_69a61e237979853466695b16');
            $table->index('account_id', 'ix_18dc9ef1e2cd2d3822be1806');
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
            $table->index('sticker', 'ix_3c75ea1ae4f3d78dd18c04d9');
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
            $table->bigInteger('released_by')->nullable();
            $table->index('released_by', 'ix_0a858f3cb461068bac68d26b');
            $table->integer('per_user_total')->nullable();
            $table->integer('per_user_remains')->nullable();
            $table->integer('locked_until_date')->nullable();
            $table->text('auction_slug')->nullable();
            $table->integer('gifts_per_round')->nullable();
            $table->integer('auction_start_date')->nullable();
            $table->integer('upgrade_variants')->nullable();
            $table->uuid('background')->nullable();
            $table->index('background', 'ix_0665987c4afaaa05070fd95b');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_76285e06e8fd38e80e12e59e');
            $table->unique(['account_id', 'released_by', 'tl_id'], 'ux_e8fca725fd63f927fdf8');
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
            $table->index('gift_id', 'ix_2dd6e652ade11c20ae82e973');
            $table->text('title');
            $table->text('slug');
            $table->integer('num');
            $table->bigInteger('owner_id')->nullable();
            $table->index('owner_id', 'ix_edb35afc13c00f4ec373ec88');
            $table->text('owner_name')->nullable();
            $table->text('owner_address')->nullable();
            $table->integer('availability_issued');
            $table->integer('availability_total');
            $table->text('gift_address')->nullable();
            $table->bigInteger('released_by')->nullable();
            $table->index('released_by', 'ix_dd2f775b3fb3e8d4da7eb6f9');
            $table->bigInteger('value_amount')->nullable();
            $table->text('value_currency')->nullable();
            $table->bigInteger('value_usd_amount')->nullable();
            $table->bigInteger('theme_peer')->nullable();
            $table->index('theme_peer', 'ix_a3f33e112edd1b3e8858380a');
            $table->uuid('peer_color')->nullable();
            $table->index('peer_color', 'ix_337423f5a7ac09b56edcc0fe');
            $table->bigInteger('host_id')->nullable();
            $table->index('host_id', 'ix_08019c3946d768b81104d93e');
            $table->integer('offer_min_stars')->nullable();
            $table->integer('craft_chance_permille')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9b92af4a662fcebf6c2056d9');
            $table->unique(['account_id', 'host_id', 'tl_id'], 'ux_cf12ea11852351f84714');
        });
        Schema::create('tl_star_gift_star_gift_unique__attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_star_gift_star_gift_unique')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_076962ca5dcb00c86e55');
            $table->index('account_id', 'ix_dc8eb90394726d2689e8efcb');
        });
        Schema::create('tl_star_gift_star_gift_unique__resell_amount', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_star_gift_star_gift_unique')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_747ddeb652121dda4bca');
            $table->index('account_id', 'ix_503289f1492990b6a6bb5420');
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
