<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\MirrorUpdateIngester;
use MeRezaRezaei\Teleframe\Ingest\RoutingEventGateway;
use MeRezaRezaei\Teleframe\Ingest\SelfOriginatedClassifier;
use MeRezaRezaei\Teleframe\Ingest\UpdateRouter;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;

/**
 * Default mirror-ingester factory — the daemon's daily observer, wired.
 *
 * Owner verbatim 2026-09-14: "the regular one is going to let us observe
 * the telegram update push them into redis using redis and observer lets
 * us keep our relational database updated with the latest telegram update
 * and the data after update comes back in shape of eloquent models".
 *
 * Builds the full NF5 mirror pipeline from the committed schema artifacts
 * (the same pure construction the gate command and MirrorChunkSync tests
 * use) and returns the callable seam the bus consumer accepts:
 * callable(array $update, int $accountId): void. The pipeline decides the
 * fact table from the constructor (tf_messages / tf_users / tf_chats) and
 * hydrates the stored model for the UpdateStored event (Eloquent models
 * back — the query surface the verbatim promises).
 */
final class MirrorIngesterFactory
{
    /** @var array<string, string>|null tf table => model FQCN, built once */
    private static ?array $modelIndex = null;

    public static function build(?Dispatcher $events = null): callable
    {
        $tl = base_path('schema/sources/TL_telegram_v227.tl');
        $catalogPath = base_path('docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json');

        $scheme = TlParser::parseFile($tl);
        $catalog = MirrorCatalog::load($catalogPath, $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $router = new UpdateRouter(DB::connection());

        $ingester = new MirrorUpdateIngester(
            new MirrorFactDecomposer($resolver, $catalog),
            new MirrorFactWriter,
            new SelfOriginatedClassifier($router),
            new RoutingEventGateway($router, $events),
        );

        return static function (array $update, int $accountId) use ($ingester, $catalog): void {
            $ctor = (string) ($update['_'] ?? '');
            $entry = $ctor === '' ? null : $catalog->tableForCtor($ctor);
            if ($entry === null) {
                return; // no mirror table for this constructor — nothing to store
            }

            $modelClass = self::modelClassFor($entry->tfName);

            $ingester->ingest(
                DB::connection(),
                $accountId,
                $update,
                $entry->tfName,
                static function (array $payload) use ($modelClass, $accountId) {
                    unset($payload['_']); // ctor marker is not a column

                    return (new $modelClass)->forceFill($payload + ['account_id' => $accountId]);
                },
            );
        };
    }

    /**
     * Map a mirror tf_ table name to its generated model class. The index
     * is built once by instantiating every generated model and reading its
     * $table — deterministic regardless of the generator's singular/plural
     * naming (tf_messages → TfMessage, tf_users → TfUser, ...). Throws when
     * the table name has no model, which is a generated-artifacts regression
     * surfaced loudly on the wire.
     */
    private static function modelClassFor(string $tfName): string
    {
        if (self::$modelIndex === null) {
            self::$modelIndex = [];
            $dir = dirname(__DIR__, 1).'/Schema/Generated/Models/Mirror';
            foreach (glob($dir.'/*.php') ?: [] as $file) {
                $class = 'MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\\'.basename($file, '.php');
                $model = new $class;
                self::$modelIndex[$model->getTable()] ??= $class;
            }
        }

        return self::$modelIndex[$tfName] ?? throw new \RuntimeException("No generated mirror model for table {$tfName} (run teleframe:regenerate).");
    }
}
