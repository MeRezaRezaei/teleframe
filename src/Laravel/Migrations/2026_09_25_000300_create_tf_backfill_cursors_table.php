<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tf_backfill_cursors (Cycle 17, verbatim 2026-09-16 "i need the full acount
 * hsitory in my database to let me work with it"): the durable per-peer
 * backfill progress ledger that makes the crawl able to REACH message id 1.
 *
 * One row per {account_id, peer_key} recording how far that dialog's history
 * crawl has walked (offset_id), how many runs it has failed (attempts), and
 * where it ended (status active|complete|dead, with the dead-letter reason).
 *
 * Peer keys are dialog KEYS, not chat ids: a key that no longer resolves into
 * an inputPeer must keep its progress rather than silently restarting at 0.
 * A REAL autoincrement id + timestamps, unlike the composite-key
 * state-projection store tables, so a peer's history is auditable and the row
 * can carry a heartbeat. No FK — a cursor is a ledger fact about an account,
 * not a bucket child, and it deliberately outlives dialog reclassification.
 *
 * offset_id is an UNSIGNED BIG integer on purpose: Telegram message ids are
 * 32-bit and only grow, so an unsigned 64-bit column never wraps in a
 * long-lived deployment.
 *
 * Provenance: docs/verbatim-goals.md Cycle 17.
 * Migration 2026_09_25_000300.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_backfill_cursors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('peer_key', 255);
            $table->unsignedBigInteger('offset_id')->default(0);
            $table->unsignedInteger('attempts')->default(0);
            $table->string('status', 16)->default('active'); // active|complete|dead
            $table->string('reason', 255)->nullable();
            $table->bigInteger('last_offset_at_unix')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'peer_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_backfill_cursors');
    }
};
