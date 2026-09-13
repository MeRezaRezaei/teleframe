<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Credential vault (accounts): one row per logged-in Telegram account.
 * - type `user`: MTProto — requires app_id FK (api_id/hash) + session.
 * - type `bot`: Bot API over HTTP needs only bot_token (app_id nullable);
 *   Bot over MTProto additionally links app_id + session.
 * Owner morph nullable (same pattern as tl_user_bindings). Secrets
 * (session, bot_token) stored with `encrypted` casts. Deleting an app
 * nulls app_id (accounts survive, MTProto unusable until re-linked);
 * deleting an account never touches its app (separate integrity).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->nullable()->constrained('telegram_apps')->nullOnDelete();
            $table->string('label', 255);
            $table->string('type', 16);
            $table->string('owner_type', 255)->nullable();
            $table->bigInteger('owner_id')->nullable();
            $table->text('session')->nullable();
            $table->text('bot_token')->nullable();
            $table->integer('dc_id')->default(2);
            $table->timestamps();
            $table->unique(['app_id', 'label']);
        });
        Schema::table('telegram_accounts', function (Blueprint $table) {
            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_accounts');
    }
};
