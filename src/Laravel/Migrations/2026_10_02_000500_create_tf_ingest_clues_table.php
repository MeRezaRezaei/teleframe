<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persistent record of what the mirror FAILED to store.
 *
 * The four clue buckets the mirror already computes (fkClues, unmappedClues,
 * valueClues, and the decomposer's own coverage clues) were, as of 2026-10-02,
 * produced correctly and then DISCARDED by TWO layers on the way to the
 * console. Nothing persisted them: no clue/ledger/telemetry column existed in
 * any shipped table. That made every coverage gap in this project invisible
 * and unrecoverable — the mirror had no accounting of its own reach.
 *
 * "TWO", and it was MEASURED rather than asserted, because in this codebase a
 * comment's job is to carry the measurement and a wrong count is worse than no
 * count: the two are `MirrorIngesterFactory::build()`'s `ingest()` call and
 * `MirrorIngesterFactory::ingestNestedSources()` — the two places a written
 * result's clue arrays are reduced to row counts. An earlier draft of this
 * comment said FOUR; that was a guess, and it is corrected here rather than
 * carried forward.
 *
 * `seen_at` is monotonic with the ingest watermark so a clue can never fall
 * outside the resync window of the run that produced it.
 *
 * This is LOCAL/APP state, not a Telegram mirror table: it is deliberately not
 * in `src/Schema/schema/mirror-catalog.json`, carries no `constructor`
 * discriminator and no `->foreign()`, and is keyed by nothing at all — it is a
 * ledger of defects, appended, never upserted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_ingest_clues', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->string('bucket', 32);
            $table->string('table', 64);
            $table->text('detail');
            $table->timestamp('seen_at');
            $table->index(['account_id', 'bucket'], 'ix_tf_ingest_clues_account_bucket');
            $table->index(['account_id', 'seen_at'], 'ix_tf_ingest_clues_account_seen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_ingest_clues');
    }
};
