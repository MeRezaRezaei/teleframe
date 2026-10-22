<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-backed runtime account registry (Cycle-4 RFC, Option A): the live
 * "which account/bot does the daemon drive" answer. One row per Telegram
 * account_id — NOT auto-increment, ids come from Telegram. The legacy
 * config('teleframe.daemon.accounts') stays the bootstrap seed; this table
 * becomes the registry the daemon re-reads on a reload signal
 * (StreamSchema::RELOAD_CHANNEL, kind 'accounts').
 *
 * session_ref is deliberately OPAQUE — a reference into the vault/host
 * session resolver, NEVER a session string.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('label');
            $table->unsignedInteger('dc_id');
            $table->unsignedInteger('port')->nullable();
            $table->string('kind', 16)->default('account');
            $table->string('session_ref');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index('enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_accounts');
    }
};
