<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tf_store_buckets.peer_type (remainder design §6): wave-2 surfacing —
 * records whether a backup bucket's peer is a channel or a group
 * (supergroup/megagroup) target, added now so the schema ships once.
 * Nullable: existing rows are the wave-1 owned channels; wave 2 classifies
 * non-owned peers and fills this column.
 *
 * Provenance:
 * docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md §6
 * ("same row + peer_type"), §8 wave-2. Curated ship dial via
 * teleframe:regenerate.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guarded in both directions, like every other ALTER migration in this
        // family (2026_09_14_000000, 2026_09_26_000100, 2026_09_27_000100). This
        // one was the outlier, so a re-run or a double rollback threw where its
        // siblings did not.
        if (Schema::hasTable('tf_store_buckets') && ! Schema::hasColumn('tf_store_buckets', 'peer_type')) {
            Schema::table('tf_store_buckets', function (Blueprint $table) {
                $table->string('peer_type', 16)->nullable(); // channel|group
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tf_store_buckets') && Schema::hasColumn('tf_store_buckets', 'peer_type')) {
            Schema::table('tf_store_buckets', function (Blueprint $table) {
                $table->dropColumn('peer_type');
            });
        }
    }
};
