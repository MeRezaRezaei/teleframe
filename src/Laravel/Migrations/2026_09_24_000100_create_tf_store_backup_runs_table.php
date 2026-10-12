<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tf_store_backup_runs (remainder design §6 index rows): the backup run
 * ledger — one row per {account_id, object_name} recording each dump run's
 * identity (bucket, driver, database), the stored object's observed truth
 * (bytes/chunks/sha256/mode — empty until observed on a queue dispatch),
 * status (ok|queued), and the run heartbeat (heartbeat_at_unix, null until
 * the upload is observed). peer_type is the wave-2 surfacing slot
 * (channel|group). A REAL autoincrement id + timestamps, unlike the
 * composite-key state-projection store tables: this ledger is append-only
 * history. No FK — a run row is a ledger fact, not a bucket child (survives
 * bucket reclassification; prune owns retention).
 *
 * Provenance:
 * docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md §6.
 * Migration 2026_09_24_000100 (curated ship dial via teleframe:regenerate).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_store_backup_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('bucket', 255)->index();
            $table->string('peer_type', 16)->nullable(); // channel|group (wave 2 surfacing)
            $table->string('driver', 32);
            $table->string('database', 255);
            $table->string('object_name', 255);
            $table->unsignedBigInteger('bytes');
            $table->unsignedInteger('chunks');
            $table->string('sha256', 64);
            $table->string('mode', 16); // sealed (the only mode in the slice)
            $table->string('status', 16)->default('ok'); // ok|queued
            $table->bigInteger('heartbeat_at_unix')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'object_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_store_backup_runs');
    }
};
