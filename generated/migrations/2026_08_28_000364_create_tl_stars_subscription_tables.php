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
        Schema::create('tl_stars_subscription_stars_subscription', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('canceled')->default(false);
            $table->boolean('can_refulfill')->default(false);
            $table->boolean('missing_balance')->default(false);
            $table->boolean('bot_canceled')->default(false);
            $table->text('tl_id')->nullable();
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_10c96af00096767dba5c2115');
            $table->integer('until_date')->nullable();
            $table->bigInteger('pricing')->nullable();
            $table->index('pricing', 'ix_368fb35f00356a4f066aa479');
            $table->text('chat_invite_hash')->nullable();
            $table->text('title')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_79373aa73597031f116e7682');
            $table->text('invoice_slug')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2f837382694635a0bb566ff1');
            $table->index('account_id', 'ix_c5341c58a6c4e404752b3124');
            $table->unique(['peer', 'account_id'], 'ux_e2892b7dc84938995740');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_subscription_stars_subscription');
    }
};
