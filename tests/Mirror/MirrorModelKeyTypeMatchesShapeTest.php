<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Mirror;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Mirror\Models\TfBusinessChatLink;
use MeRezaRezaei\Teleframe\Mirror\Models\TfStarsTransaction;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdate;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfBusinessChatLinks;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfStarsTransactions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfStoreBuckets;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfUpdatesPts;
use MeRezaRezaei\Teleframe\Schema\Generator\ModelGenerator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Two gates. Both exist because every pre-existing gate enumerates from the
 * CATALOG, never from the FILESYSTEM or from the runtime — so a defect that
 * lives in a model file is STRUCTURALLY INVISIBLE to them.
 *
 * 1. `getKeyType()` agrees with the column the model actually keys on.
 *    76 shipped mirror models key on a `varchar(255)` column: the opaque,
 *    server-issued Telegram handle (`starsTransaction#id`,
 *    `botInlineResult#id`, `businessChatLink#link`, `storeBucket#bucket`,
 *    `storeObject#name`). `Model::getKeyType()` returning `'int'` for one of
 *    those makes Eloquent pick `Relation::whereIntegerInRaw()`, which `(int)`s
 *    each value — an opaque id collapses to `0` and the relation silently
 *    matches nothing. `TfMirrorModel` derives it from the generator's own
 *    `$casts`; this test is what proves the derivation still agrees with the
 *    shipped DDL after every regeneration.
 *
 * 2. Every mirror model names a table the shipped dial actually creates.
 *    `tf_updates_channel` / `_chat` / `_user` survived as models — and their
 *    relations on `TfUpdate` — for as long as the fold migration had been
 *    dropping their tables, because nothing compared a model to the dial.
 *    This one does, and it starts from the FILESYSTEM, so a model with no
 *    catalog entry is exactly the case it is built to catch.
 *
 * The DDL truth is `mirror-shape.json` (per-column `type` + `primary`, fed by
 * the executed shipped migrations) and the shipped migration set itself.
 * Neither is transcribed here, so a column type that moves moves this test
 * with it.
 *
 * Provenance: `src/Schema/Eloquent/TfMirrorModel.php` (the derivation),
 * `src/Schema/Generator/ModelGenerator.php:123` (`castFor()` — public static,
 * the exact mapping the generator casts with), `mirror-shape.json` scalar key
 * column types, `src/Laravel/Migrations/` (the dial),
 * `2026_10_10_000100_fold_update_scalar_children_onto_the_update_fact.php:245-247`.
 */
final class MirrorModelKeyTypeMatchesShapeTest extends TestCase
{
    private const SHAPE = __DIR__.'/../../src/Schema/schema/mirror-shape.json';

    private const SHIP_DIR = __DIR__.'/../../src/Laravel/Migrations';

    /**
     * Every model directory, paired with its namespace. BOTH are checked: the
     * generated tree is what `wipe()` erases and re-emits, and the
     * hand-authored tree is what nothing regenerates — so a gap in either is
     * a gap.
     *
     * @var array<string,string>
     */
    private const MODEL_DIRS = [
        __DIR__.'/../../src/Teleframe/Mirror/Models' => 'MeRezaRezaei\\Teleframe\\Mirror\\Models',
        __DIR__.'/../../src/Schema/Generated/Models' => 'MeRezaRezaei\\Teleframe\\Schema\\Generated\\Models',
    ];

    /**
     * Relations whose eager load routes keys through
     * `Relation::whereIntegerInRaw()` using the DECLARING model's
     * `getKeyType()`. `BelongsTo` is deliberately absent: its eager load
     * consults `$this->related` (the model being pointed AT), not the
     * declaring one, so declaring it is not what exposes the declaring model.
     *
     * @var list<string>
     */
    private const EAGER_RELATIONS = [
        'HasOne', 'HasMany', 'HasManyThrough', 'HasOneThrough',
        'MorphOne', 'MorphMany', 'MorphToMany',
    ];

    /**
     * MODELS THAT STILL MISREPORT, listed rather than tolerated.
     *
     * These are the hand-authored `TfStars*` children that set
     * `$primaryKey = 'id'` on a `varchar(255)` column while — unlike the
     * generator, and unlike the hand-authored roots beside them — declaring
     * neither `$keyType` nor an `id` entry in their hand-maintained
     * `$casts`. There is therefore nothing on the base class to derive from,
     * and `keyType = 'string'` is a one-line edit each in a file outside this
     * change's scope.
     *
     * They are enumerated, not globbed, so the list cannot absorb a NEW
     * mismatch: an unlisted model that misreports fails the gate above. And
     * each one is re-proven INERT below — it declares no eager relation, so
     * `whereIntegerInRaw()` is never handed its key — which is the difference
     * between a latent landmine and a live wrong answer. The moment one of
     * them grows a `hasOne`, the inertness test fails and it must be fixed.
     *
     * @var list<string>
     */
    private const INERT_STRING_KEYED_GAP = [
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsSubscriptionChatInviteHash',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsSubscriptionInvoiceSlug',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsSubscriptionPhoto',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsSubscriptionPricing',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsSubscriptionTitle',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionAdsProceedsFromDate',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionAdsProceedsToDate',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionAmount',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionBotPayload',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionDescription',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionExtendedMedia',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionFloodskipNumber',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionGiveawayPostId',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionMsgId',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionPaidMessages',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionPeer',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionPhoto',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionPremiumGiftMonths',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionStargift',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionStarrefAmount',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionStarrefCommissionPermille',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionStarrefPeer',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionSubscriptionPeriod',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionTitle',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionTransactionDate',
        'MeRezaRezaei\\Teleframe\\Mirror\\Models\\TfStarsTransactionTransactionUrl',
    ];

    /**
     * The gate. Every concrete mirror model's `getKeyType()` must be what
     * `ModelGenerator::castFor()` produces for the type of the column it
     * actually keys on.
     */
    public function test_every_model_reports_the_key_type_its_key_column_has(): void
    {
        $shape = $this->shape();
        $checked = 0;
        $stringKeyed = 0;

        /** @var array<string,string> $mismatched FQCN => why */
        $mismatched = [];

        foreach ($this->concreteModels() as $class => $model) {
            $table = $model->getTable();
            $key = $model->getKeyName();

            if (! isset($shape[$table])) {
                self::fail(sprintf('%s keys on table "%s", which mirror-shape.json does not describe.', $class, $table));
            }

            if (! isset($shape[$table]['columns'][$key])) {
                // A model may key on a column the shape does not carry (a
                // seam column added by a later migration). Nothing to assert.
                continue;
            }

            $checked++;
            $declared = (string) $shape[$table]['columns'][$key]['type'];
            $want = ModelGenerator::castFor($declared) === 'string' ? 'string' : 'int';

            if ($want === 'string') {
                $stringKeyed++;
            }

            if ($model->getKeyType() !== $want) {
                $mismatched[$class] = sprintf(
                    'keys on %s.%s (%s) so getKeyType() must be "%s", got "%s"',
                    $table, $key, $declared, $want, $model->getKeyType(),
                );
            }
        }

        self::assertGreaterThan(
            400, $checked,
            'the gate covered almost nothing — a model directory moved, and this test would pass vacuously',
        );
        self::assertGreaterThan(
            60, $stringKeyed,
            'fewer string-keyed models than the shipped DDL has; the shape or the model dirs moved and this gate is now blind',
        );

        // The enumerated gap must be the WHOLE gap. An unlisted model that
        // misreports fails here; a listed one that has since been fixed is a
        // stale gate and fails below, so the list can neither grow silently
        // nor sit there concealing a fix.
        $gap = array_fill_keys(self::INERT_STRING_KEYED_GAP, true);

        $unlisted = array_diff_key($mismatched, $gap);
        self::assertSame(
            [], $unlisted,
            "a model reports a key type its key column does not have.\n".$this->lines($unlisted),
        );

        $alreadyRight = array_diff(self::INERT_STRING_KEYED_GAP, array_keys($mismatched));
        self::assertSame(
            [], array_values($alreadyRight),
            'these are listed as an accepted gap but now report the right key type — '
            .'delete them from INERT_STRING_KEYED_GAP'.implode(', ', $alreadyRight),
        );
    }

    /**
     * Anti-vacuity. A `getKeyType()` that returned `'int'` unconditionally —
     * which is what this base class did before — would satisfy the whole
     * comparison above except for the string-keyed half, so that half is
     * pinned by name as well as by count.
     */
    public function test_the_string_keyed_half_is_actually_exercised(): void
    {
        foreach ([
            // generated: the generator emits `$casts['id'] = 'string'`
            TfStarsTransactions::class,
            TfStoreBuckets::class,
            TfBusinessChatLinks::class,
            // hand-authored root: an EXPLICIT `$keyType` must win over the derivation
            TfStarsTransaction::class,
            TfBusinessChatLink::class,
        ] as $class) {
            self::assertSame(
                'string', (new $class)->getKeyType(),
                $class.' keys on an opaque varchar id; whereIntegerInRaw() would (int) it to 0',
            );
        }

        foreach ([
            TfUpdatesPts::class,
            TfUpdate::class,
        ] as $class) {
            self::assertSame('int', (new $class)->getKeyType(), $class.' keys on an integer column');
        }
    }

    /**
     * Each enumerated gap is re-proven harmless rather than merely listed.
     *
     * A gap model only misbehaves once something hands its key to
     * `whereIntegerInRaw()`, and that happens only when IT declares an eager
     * relation — or when it is the target of a `BelongsTo`, whose eager load
     * consults the related model's `getKeyType()`. Both are checked here
     * statically: declared relation TYPES via reflection (invoking a relation
     * needs a live connection, and a probe that silently swallows the
     * resulting `Error` reports "none" for every model), and `belongsTo`
     * targets by scanning the model's own source for the target name.
     */
    public function test_every_enumerated_gap_is_still_inert(): void
    {
        $sources = $this->modelSources();

        foreach (self::INERT_STRING_KEYED_GAP as $class) {
            self::assertTrue(
                class_exists($class), $class.' is listed as an accepted gap but no longer exists',
            );

            $eager = $this->declaredEagerRelations($class);
            self::assertSame(
                [], $eager,
                sprintf(
                    '%s keys on an opaque varchar id and reports "int". It now declares the eager relation(s) %s, '
                    .'so whereIntegerInRaw() IS handed that key — this is no longer latent. Set keyType = "string" on it.',
                    $class, implode(', ', $eager),
                ),
            );

            $short = substr($class, (int) strrpos($class, '\\') + 1);
            foreach ($sources as $file => $body) {
                self::assertStringNotContainsString(
                    "belongsTo({$short}::class", $body,
                    $short.' is now the TARGET of a BelongsTo, whose eager load consults its own getKeyType(). '
                    .'Set keyType = "string" on it.',
                );
            }
        }
    }

    /**
     * The Fix 1 gate, and the reason this file exists.
     *
     * Every concrete mirror model must name a table the SHIPPED DIAL leaves
     * standing: created by some migration's `up()`, and not dropped by a
     * later one. Enumerating from the catalog cannot catch this — the three
     * tables this gate was written for have no catalog entry at all, which is
     * exactly why their models sat there for so long.
     *
     * Creates and drops are replayed in FILENAME order across the shipped
     * migration set, and each migration is read with its `down()` body
     * removed, because `down()` restores what `up()` retired and a whole-file
     * scan would resurrect every folded table.
     */
    public function test_every_mirror_model_names_a_table_the_shipped_dial_creates(): void
    {
        $dial = $this->shippedTables();

        self::assertGreaterThan(
            200, count($dial),
            'the shipped-dial scan found almost no tables — the migration directory moved and this gate is now blind',
        );

        $orphans = [];
        foreach ($this->concreteModels() as $class => $model) {
            $table = $model->getTable();

            if (! isset($dial[$table])) {
                $orphans[] = sprintf(
                    '%s maps to table "%s", which the shipped dial does not leave standing',
                    $class, $table,
                );
            }
        }

        self::assertSame([], $orphans, implode("\n", $orphans));

        // Named, so a regression names the defect instead of merely
        // counting: these are the three that this gate was written for.
        foreach (['tf_updates_channel', 'tf_updates_chat', 'tf_updates_user'] as $retired) {
            self::assertArrayNotHasKey(
                $retired, $dial,
                $retired.' was retired by 2026_10_10_000100; if it is back, something restored it',
            );
        }
    }

    /** @return array<string,array<string,array<string,mixed>>> table => table shape */
    private function shape(): array
    {
        $raw = file_get_contents(self::SHAPE);
        self::assertIsString($raw, 'cannot read '.self::SHAPE);

        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        return $decoded['tables'];
    }

    /**
     * Every concrete mirror model, keyed by FQCN.
     *
     * A model that does not declare its own `$table` is a BASE — `TfMirrorModel`
     * itself, `TfChildModel`, and the hand-authored `MirrorChildModel` — and is
     * out of scope: it names no table on purpose. The test is that
     * `$table` is redeclared on the concrete class, which is exact, because
     * redeclaring a property moves its declaring class.
     *
     * @return array<string,Model>
     */
    private function concreteModels(): array
    {
        $models = [];

        foreach (self::MODEL_DIRS as $dir => $namespace) {
            $files = glob($dir.'/*.php') ?: [];
            self::assertNotEmpty($files, 'no models found in '.$dir);

            foreach ($files as $file) {
                $class = $namespace.'\\'.basename($file, '.php');

                self::assertTrue(
                    class_exists($class) || trait_exists($class),
                    $class.' does not autoload from '.$file,
                );

                // The model directories hold traits beside the models
                // (`PinsDeclaredForeignKeyLegs`, `ResolvesMessagePeerScope`).
                // They are not models and there is nothing to say about them.
                if (! class_exists($class)) {
                    continue;
                }

                $reflection = new ReflectionClass($class);

                if ($reflection->isAbstract() || ! $reflection->isSubclassOf(TfMirrorModel::class)) {
                    continue;
                }

                if ($reflection->getProperty('table')->getDeclaringClass()->getName() !== $class) {
                    continue;
                }

                $model = $reflection->newInstance();
                self::assertInstanceOf(Model::class, $model);

                $models[$class] = $model;
            }
        }

        return $models;
    }

    /**
     * The eager relations a class DECLARES, read from the declared return
     * types — never by invoking them, which needs a live connection.
     *
     * @return list<string>
     */
    private function declaredEagerRelations(string $class): array
    {
        $found = [];

        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            $return = $method->getReturnType();
            if (! $return instanceof ReflectionNamedType) {
                continue;
            }

            $name = $return->getName();
            $position = strrpos($name, '\\');
            if ($position !== false) {
                $name = substr($name, $position + 1);
            }

            if (in_array($name, self::EAGER_RELATIONS, true)) {
                $found[] = $method->getName().'(): '.$name;
            }
        }

        return $found;
    }

    /**
     * Every model file's source text, for the `belongsTo` target scan.
     *
     * @return array<string,string>
     */
    private function modelSources(): array
    {
        $sources = [];

        foreach (self::MODEL_DIRS as $dir => $namespace) {
            foreach (glob($dir.'/*.php') ?: [] as $file) {
                $sources[basename($file, '.php')] = (string) file_get_contents($file);
            }
        }

        return $sources;
    }

    /**
     * Tables the shipped dial leaves standing, replayed across the migration
     * set in filename order.
     *
     * Regex-free: every call site in this repo spells the argument
     * `Schema::create('<table>', …`, so the literal marker plus the next quote
     * delimits the name exactly. Hits are applied in OFFSET order within a
     * file, so a migration that drops before it creates is honoured.
     *
     * @return array<string,true>
     */
    private function shippedTables(): array
    {
        $files = glob(self::SHIP_DIR.'/*.php') ?: [];
        self::assertNotEmpty($files, 'no shipped migrations found in '.self::SHIP_DIR);
        sort($files);

        $live = [];

        foreach ($files as $file) {
            $up = $this->withoutDownBody((string) file_get_contents($file));

            $hits = [];
            foreach ([
                "Schema::create('" => 1,
                "Schema::dropIfExists('" => -1,
                "Schema::drop('" => -1,
            ] as $marker => $direction) {
                $offset = 0;
                while (($start = strpos($up, $marker, $offset)) !== false) {
                    $start += strlen($marker);
                    $end = strpos($up, "'", $start);
                    if ($end === false) {
                        break;
                    }
                    $hits[$start] = [$direction, substr($up, $start, $end - $start)];
                    $offset = $end;
                }
            }

            ksort($hits);
            foreach ($hits as [$direction, $table]) {
                if ($direction > 0) {
                    $live[$table] = true;
                } else {
                    unset($live[$table]);
                }
            }
        }

        return $live;
    }

    /**
     * A migration with its `down()` body cut out.
     *
     * Many shipped migrations build tables in PRIVATE helpers called from
     * `up()` — `createUpdateChildren()` and friends create ~204 of them — so
     * slicing to the `up()` body alone loses them. Cutting `down()` out of the
     * whole file is the correct inverse: it keeps the helpers, and it removes
     * exactly the restores that would resurrect every folded table. The body
     * is brace-balanced rather than sliced between `up()` and `down()`, because
     * method order varies across the set.
     */
    private function withoutDownBody(string $source): string
    {
        $declaration = strpos($source, 'function down(');
        if ($declaration === false) {
            return $source;
        }

        $open = strpos($source, '{', $declaration);
        if ($open === false) {
            return $source;
        }

        $depth = 0;
        $length = strlen($source);
        for ($i = $open; $i < $length; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return str_replace(substr($source, $open, $i - $open + 1), '', $source);
                }
            }
        }

        return $source;
    }

    /**
     * Render a FQCN => reason map as a failure message body.
     *
     * @param  array<string,string>  $reasons
     */
    private function lines(array $reasons): string
    {
        $out = [];
        foreach ($reasons as $class => $reason) {
            $out[] = '  '.$class.' — '.$reason;
        }

        return "\n".implode("\n", $out);
    }
}
