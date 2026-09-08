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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_stars_subscription_stars_subscription', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_subscription')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('canceled')->default(false);
            $table->boolean('can_refulfill')->default(false);
            $table->boolean('missing_balance')->default(false);
            $table->boolean('bot_canceled')->default(false);
            $table->text('tl_id');
            $table->uuid('peer');
            $table->integer('until_date');
            $table->uuid('pricing');
            $table->text('chat_invite_hash')->nullable();
            $table->text('title')->nullable();
            $table->uuid('photo')->nullable();
            $table->text('invoice_slug')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_subscription_stars_subscription');
        Schema::dropIfExists('tl_stars_subscription');
    }
};
