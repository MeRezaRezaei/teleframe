<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\ChunkFetcher;
use MeRezaRezaei\Teleframe\Ingest\MirrorChunkSync;
use MeRezaRezaei\Teleframe\Ingest\MtprotoChunkFetcher;
use MeRezaRezaei\Teleframe\Ingest\PtsWatermark;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorChunkDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;

/**
 * artisan teleframe:ingest:gate — the session-register schema-integrity gate.
 *
 * Owner verbatim 2026-09-14: "at session register we are going to ask
 * teelgram to send all updates as a chunck and if we can ingest the big
 * update the full shcema is correct this is the way i can make sure my app
 * is working".
 *
 * Runs the mirror chunk sync for one account (updates.getState →
 * updates.getDifference → decompose → write) and reports integrity:
 * zero FK clues across the chunk = the full schema is correct for that
 * data. FK/NOT NULL failures are clues to the wrong ingest path and are
 * printed per-fact; success persists the new pts watermark so the daily
 * daemon resumes where the gate left off.
 *
 * The wire half is a container seam (mirrors BackfillCommand): FETCHER_KEY
 * binds callable(int $accountId): ChunkFetcher. The default builds a live
 * MtprotoChunkFetcher over a TeleframeClient::user() scope; hosts that test
 * the gate offline rebind the key to a fixture fetcher.
 */
final class IngestGateCommand extends Command
{
    /** Container seam: callable(int $accountId): ChunkFetcher — string literal on purpose (host-rebindable). */
    public const FETCHER_KEY = 'teleclient.ingest-gate.fetcher';

    protected $signature = 'teleframe:ingest:gate
        {account : Telegram account id to gate against}
        {--only : Only verify integrity; do NOT persist the new watermark}';

    protected $description = 'Session-register gate: ingest the Telegram big update chunk as the schema-correctness proof';

    protected $aliases = [];

    public function handle(): int
    {
        $accountId = (int) $this->argument('account');
        if ($accountId <= 0) {
            $this->error('account must be a positive integer.');

            return self::FAILURE;
        }

        $fetcher = $this->resolveFetcher()($accountId);

        // Mirror pipeline is pure (no host wiring) — construct from the
        // committed schema artifacts, exactly like teleframe:mirror does.
        $tl = base_path('schema/sources/TL_telegram_v227.tl');
        $catalogPath = base_path('docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json');
        $scheme = TlParser::parseFile($tl);
        $catalog = MirrorCatalog::load($catalogPath, $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);

        $sync = new MirrorChunkSync(
            new MirrorChunkDecomposer(
                new MirrorFactDecomposer($resolver, $catalog),
                $catalog,
            ),
            new MirrorFactWriter,
        );

        $watermark = $this->getLaravel()->make(PtsWatermark::class);
        $prior = $watermark->get($accountId);

        try {
            $report = $sync->sync(DB::connection(), $accountId, $fetcher, $prior);
        } catch (\Throwable $e) {
            $this->error("Chunk sync failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        if (! $this->option('only')) {
            $watermark->put($accountId, $report['state']);
        }

        $this->table(
            ['fact', 'value'],
            [
                ['account', $accountId],
                ['prior pts', $prior['pts'] ?? '—'],
                ['new pts', $report['state']['pts']],
                ['rows', $report['rows']],
                ['inserted', $report['inserted']],
                ['clues', count($report['clues'])],
                ['fk clues', count($report['fkClues'])],
            ],
        );

        foreach ($report['clues'] as $clue) {
            $this->warn("  clue: {$clue}");
        }
        foreach ($report['fkClues'] as $clue) {
            $this->warn("  FK clue: {$clue}");
        }

        if ($report['fkClues'] !== []) {
            $this->error('Schema integrity NOT reached — FK failures are clues to the wrong ingest path.');

            return self::FAILURE;
        }

        $this->info($this->option('only')
            ? 'Integrity OK — full schema is correct for this chunk (watermark not persisted).'
            : 'Integrity OK — full schema is correct for this chunk; watermark persisted.');

        return self::SUCCESS;
    }

    private function resolveFetcher(): callable
    {
        $app = $this->getLaravel();

        if ($app->bound(self::FETCHER_KEY)) {
            return $app->make(self::FETCHER_KEY);
        }

        return static function (int $accountId) use ($app): ChunkFetcher {
            $client = $app->make(TeleframeClient::class);

            return new MtprotoChunkFetcher($client->user($accountId)->mtproto);
        };
    }
}
