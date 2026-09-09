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
        Schema::create('tl_stars_subscription', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_0b14c26c3c3be6b76f5cd263');
            $table->index('account_id', 'ix_1801ce11a6db3c825d8ed50d');
        });
        Schema::create('tl_stars_subscription_stars_subscription', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_subscription')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('canceled')->default(false);
            $table->boolean('can_refulfill')->default(false);
            $table->boolean('missing_balance')->default(false);
            $table->boolean('bot_canceled')->default(false);
            $table->text('tl_id');
            $table->bigInteger('peer');
            $table->index('peer', 'ix_10c96af00096767dba5c2115');
            $table->integer('until_date');
            $table->uuid('pricing');
            $table->index('pricing', 'ix_368fb35f00356a4f066aa479');
            $table->text('chat_invite_hash')->nullable();
            $table->text('title')->nullable();
            $table->uuid('photo')->nullable();
            $table->index('photo', 'ix_79373aa73597031f116e7682');
            $table->text('invoice_slug')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c5341c58a6c4e404752b3124');
            $table->unique(['account_id', 'peer', 'tl_id'], 'ux_e2892b7dc84938995740');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_subscription_stars_subscription');
        Schema::dropIfExists('tl_stars_subscription');
    }
};
