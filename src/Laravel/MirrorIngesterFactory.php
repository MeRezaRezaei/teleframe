<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\MirrorUpdateIngester;
use MeRezaRezaei\Teleframe\Ingest\RoutingEventGateway;
use MeRezaRezaei\Teleframe\Ingest\SelfOriginatedClassifier;
use MeRezaRezaei\Teleframe\Ingest\UpdateRouter;
use MeRezaRezaei\Teleframe\Ingest\UpdateRoutingCache;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerShapeTool;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use Psr\SimpleCache\CacheInterface;

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

    public static function build(
        ?Dispatcher $events = null,
        ?CacheInterface $sends = null,
        ?UpdateRoutingCache $routingCache = null,
    ): callable {
        $tl = base_path('schema/sources/TL_telegram_v227.tl');
        $catalogPath = base_path('docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json');

        $scheme = TlParser::parseFile($tl);
        $catalog = MirrorCatalog::load($catalogPath, $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $router = new UpdateRouter(DB::connection(), $routingCache);

        $ingester = new MirrorUpdateIngester(
            new MirrorFactDecomposer($resolver, $catalog),
            new MirrorFactWriter,
            new SelfOriginatedClassifier($router, $sends),
            new RoutingEventGateway($router, $events),
        );

        return static function (array $update, int $accountId) use ($ingester, $catalog, $resolver): void {
            $ctor = (string) ($update['_'] ?? '');
            $entry = $ctor === '' ? null : $catalog->tableForCtor($ctor);
            if ($entry === null) {
                return; // no mirror table for this constructor — nothing to store
            }

            $modelClass = self::modelClassFor($entry->tfName);
            $table = $resolver->resolveAll([$entry->tfName])[0];

            $ingester->ingest(
                DB::connection(),
                $accountId,
                $update,
                $entry->tfName,
                static function (array $payload) use ($modelClass, $accountId, $table) {
                    unset($payload['_']); // ctor marker is not a column
                    $payload['account_id'] = $accountId;

                    // Peer fields arrive on the wire as a single object
                    // (peerChannel#channel_id …); the model stores the canonical
                    // _type/_id halves. Expand so the hydrated model — the
                    // verbatim's query surface — carries the same peer shape
                    // the mirror rows do. Missing/unresolvable peers are left
                    // unset (the decomposer already surfaced the clue).
                    $payload = self::expandPeers($payload, $table->peerColumns);

                    return (new $modelClass)->forceFill($payload);
                },
            );
        };
    }

    /**
     * Expand peer-field half names (peer_id_type / peer_id_id) from the
     * single payload field they encode (peer_id). Accepts the wire ctor
     * object and the canonical _type/_id pair alike (PeerShapeTool), and
     * drops the source array field so no stray attribute reaches the model.
     *
     * @param  array<string, mixed>  $payload
     * @param  list<array{kind: 'type'|'id', name: string}>  $peerColumns
     * @return array<string, mixed>
     */
    private static function expandPeers(array $payload, array $peerColumns): array
    {
        foreach ($peerColumns as $half) {
            if ($half['kind'] !== 'type') {
                continue; // the id half rides along with its type twin
            }
            $name = $half['name'];
            if (! str_ends_with($name, '_type')) {
                continue;
            }
            $stem = substr($name, 0, -5);
            // Canonical peer mapping: `peer_type`/`peer_id` encode payload `peer_id`.
            $field = $stem === 'peer' ? 'peer_id' : $stem;
            $value = $payload[$field] ?? null;
            if (! is_array($value)) {
                continue; // decomposer already clues; model stays unset
            }

            [$type, $id] = PeerShapeTool::normalize($value);
            if ($type === 0 && $id === 0) {
                continue; // unresolvable — do not hydrate 0/0 into the model
            }
            $payload[$name] = $type;
            $idColumn = $stem.'_id';
            $payload[$idColumn] = $id;
            if ($field !== $idColumn) {
                unset($payload[$field]); // canonical peer: id column reuses the wire key
            }
        }

        return $payload;
    }

    /**
     * Map a mirror tf_ table name to its curated model class. The index is
     * built once by instantiating every curated model and reading its $table
     * — deterministic regardless of the generator's singular/plural naming
     * (tf_messages → TfMessage, tf_users → TfUser, ...). Throws when the
     * table name has no model, which is a curated-artifacts regression
     * surfaced loudly on the wire.
     */
    private static function modelClassFor(string $tfName): string
    {
        if (self::$modelIndex === null) {
            self::$modelIndex = [];
            $dir = dirname(__DIR__, 1).'/Teleframe/Mirror/Models';
            foreach (glob($dir.'/*.php') ?: [] as $file) {
                $class = 'MeRezaRezaei\Teleframe\Mirror\Models\\'.basename($file, '.php');
                if (! class_exists($class)) {
                    continue; // trait/concern files are not models
                }
                $model = new $class;
                self::$modelIndex[$model->getTable()] ??= $class;
            }
        }

        return self::$modelIndex[$tfName] ?? throw new \RuntimeException("No curated mirror model for table {$tfName} (curated dial regression).");
    }
}
