<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

use LogicException;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlConstructor;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;

/**
 * The endpoint inventory: every `update*` constructor the layer-227 scheme
 * declares, classified by what the mirror will actually do with it.
 *
 * ## WHY THIS IS A CLASS AND NOT A DOCUMENT
 *
 * A hand-written list of endpoints is a second copy of the scheme, and the
 * scheme changes every time the schema pipeline runs. `bin/generate-relation-graph.php`
 * exists for the same reason about foreign keys, and its docblock names the
 * failure precisely: a text search cannot see constraints that reach the
 * schema through shared Blueprint helpers, and it cannot see primary keys.
 * The same blindness applies to endpoints — a `grep update` finds constructor
 * names in comments, in migration docblocks, and in test fixtures, and cannot
 * tell which of them the catalog actually claims.
 *
 * So the classification is DERIVED, from the parsed scheme and the loaded
 * catalog, and the document is generated from the class. The gate reads the
 * class; the document is for humans.
 *
 * ## THE FOUR KINDS, AND WHY `unmodelled` IS A FIRST-CLASS ANSWER
 *
 * `unmodelled` is not a failure state to be minimised. It is the honest label
 * for "this endpoint reaches tf_updates and nothing deeper", and this package's
 * purpose — storing the full history so an agent can act on it — makes the
 * population of unmodelled endpoints the project's actual backlog. Labelling
 * them is what turns that backlog from invisible into assignable.
 *
 * What is NOT acceptable is an endpoint with no label at all, which is the state
 * measured at `6ff6f1c7`: 178 declared, 9 named in the catalog.
 *
 * ## MEASURED DIVERGENCES FROM THE PLAN THIS FILE IMPLEMENTS
 *
 * The plan's sketch named two APIs that do not exist. Both were measured, both
 * are corrected here rather than worked around:
 *
 * 1. **`TlScheme::constructors()` DOES NOT EXIST.** `TlScheme` lives in
 *    `...\Schema\Generator\Model\`, not `...\Schema\Generator\`, and exposes
 *    `types()`, `methods()` and `counts()` only — `counts()` itself sums
 *    `constructors()` over every type, so the flat map has to be walked out of
 *    `types()`. `TlType::constructors()` IS keyed by constructor name, so the
 *    per-type walk yields exactly the flat ctor map the plan assumed.
 *
 * 2. **`MirrorCatalog::nestedSources()` RETURNS `list<MirrorNestedSource>`, NOT
 *    `parentCtor => sources`.** The plan iterated it as an associative map and
 *    read `$source['tl']`, which on an object yields its property array and
 *    would silently produce garbage. The real nested source carries `tfName`,
 *    `parentTlType`, `field`, the resolved `TlParam`, and `parentCtors()` — and
 *    crucially it names the tf TABLE and the PARENT constructor, never the
 *    carried object's constructor. The carried ctors are therefore DERIVED here
 *    from the source's resolved param type: the constructors of
 *    `$source->param->baseType()` in the scheme are the objects that field can
 *    carry.
 *
 * **Measured consequence, 2026-10-04, this tree:**
 * `root-fact = 0` and `nested-source = 0`, and `unmodelled = 175` (the whole
 * parsed universe). Both zeros are real, not stubs:
 *
 * - `root-fact = 0` because NO catalog entry declares ANY `update*` constructor
 *   in its `ctors` list. The "9 named in mirror-catalog.json" figure is real but
 *   is a different fact: those 9 (`updateChannelParticipant`,
 *   `updateDialogFilter`, `updateEncryption`, `updateNewQuickReply`,
 *   `updatePeerWallpaper`, `updatePhoneCall`, `updateQuickReplies`,
 *   `updateStory`, `updateTheme`) are the PARENT constructors of catalog
 *   nested-source declarations on the `Update` type — not tables' own row types.
 * - `nested-source = 0` because no declared nested source carries an object
 *   whose constructors are `update*`. Every one of the 32 declared nested
 *   sources carries a plain object type (`ChannelParticipant`, `BotInfo`,
 *   `MessageMedia`, …).
 *
 * ## THE NAME PREFIX OVER-SELECTS, AND WHAT CAME OF IT (2026-10-05)
 *
 * The universe rule is a NAME rule — every constructor whose name starts with
 * `update` — and a name rule cannot tell an update from an RPC that returns
 * one. Measured on this tree, the scheme holds **175** such constructors whose
 * **declared result types** are:
 *
 * | result type | count | what it is |
 * |---|---:|---|
 * | `Update` | 160 | an update endpoint — the honest universe |
 * | `Updates` | 7 | `updates`, `updatesCombined`, `updatesTooLong`, `updateShort`, `updateShortMessage`, `updateShortChatMessage`, `updateShortSentMessage` |
 * | `updates.Difference` | 4 | `updates.difference{Empty,Slice,TooLong}` |
 * | `updates.ChannelDifference` | 3 | `updates.channelDifference{Empty,TooLong}` |
 * | `updates.State` | 1 | `updates.state` |
 *
 * The 15 non-`Update` constructors are RPC RESULTS. Two of them carry real
 * updates and were being asked to satisfy an obligation they cannot meet —
 * the gate demanded a `tf_updates` row naming `updates`, and none was written,
 * because `updates` is decomposed into its members rather than stored. That is
 * why `KIND_UPDATE_CARRIER` and `KIND_RPC_RESULT` exist, and why both are
 * decided from the parsed constructor's `resultType` and `Vector<Update>`
 * parameters rather than from any hand-typed list.
 *
 * **NEITHER new kind excludes anything.** Both populations stay in the
 * partition, so the per-kind counts still sum to the universe and the gate
 * still asserts it. A kind that merely dropped these fifteen would be a quiet
 * deletion of fifteen constructors from the coverage claim.
 */
final class EndpointCoverage
{
    /** Closed set. A constructor with no kind is a bug in this class. */
    public const KIND_ROOT_FACT = 'root-fact';

    public const KIND_NESTED_SOURCE = 'nested-source';

    public const KIND_LOG_ONLY = 'log-only';

    public const KIND_UNMODELLED = 'unmodelled';

    /**
     * An RPC RESULT that carries real `Update`s in a `Vector<Update>` field.
     *
     * The obligation is UNWRAP: each member of that vector must be placed by the
     * per-update ingest path. The carrier itself owes NO `tf_updates` row,
     * because a carrier is not an update — `updates#74ae4240` is the reply to
     * `updates.getDifference`, and a row naming it would record a fact that never
     * happened as though it were one.
     */
    public const KIND_UPDATE_CARRIER = 'update-carrier';

    /**
     * An RPC RESULT that constructs no `Update` and carries none.
     *
     * `updates.state` is a pts/qts/date/seq snapshot; `updatesTooLong` and the
     * `*Empty` / `*TooLong` results are the absence of a difference or an error
     * about the window. None is an update endpoint, so none owes a
     * `tf_updates` row. They are still CLASSIFIED and COUNTED — see the class
     * docblock: a kind that merely excluded them would be a quiet deletion of ten
     * constructors.
     */
    public const KIND_RPC_RESULT = 'rpc-result';

    /**
     * Every kind, in classification precedence order. A constructor with no kind
     * is a bug in this class, and an unknown kind raises rather than inventing a
     * bucket — see build().
     */
    public const KINDS = [
        self::KIND_UPDATE_CARRIER,
        self::KIND_RPC_RESULT,
        self::KIND_ROOT_FACT,
        self::KIND_NESTED_SOURCE,
        self::KIND_LOG_ONLY,
        self::KIND_UNMODELLED,
    ];

    /**
     * @param  list<array{ctor:string,kind:string,tables:list<string>,reason:string}>  $endpoints
     * @param  array<string,int>  $counts
     */
    private function __construct(
        private readonly array $endpoints,
        private readonly array $counts,
    ) {}

    /**
     * Classify every `update*` constructor the scheme declares.
     *
     * ONE factory, ONE object shape. `endpoints()` and `counts()` are instance
     * methods on the returned object — an earlier draft of this plan exposed
     * them as three static methods returning two different types (a list and an
     * array), which the gate test then called as if both were the object. The
     * inconsistency is fixed here rather than left for the implementer.
     */
    public static function classify(TlScheme $scheme, MirrorCatalog $catalog): self
    {
        return self::build($scheme, $catalog);
    }

    /**
     * Every classified endpoint, sorted by constructor name.
     *
     * @return list<array{ctor:string,kind:string,tables:list<string>,reason:string}>
     */
    public function endpoints(): array
    {
        return $this->endpoints;
    }

    /**
     * Constructors per kind. The four keys are always present, even at zero, so
     * the generated document's table has a stable shape.
     *
     * @return array<string,int>
     */
    public function counts(): array
    {
        return $this->counts;
    }

    private static function build(TlScheme $scheme, MirrorCatalog $catalog): self
    {
        // 1. The universe: every constructor the PARSED scheme holds whose name
        //    starts with `update`. `TlScheme` has no `constructors()` of its own;
        //    its flat ctor map is walked out of `types()`, and `TlType::constructors()`
        //    is keyed by ctor name. Nothing is excluded: the partition is total
        //    over this set, and the gate asserts the counts sum to it.
        //
        //    The name-prefix rule OVER-SELECTS, and both groups it pulls in are
        //    kept rather than dropped — see the docblock. `TlConstructor` is kept
        //    alongside each name because the two new kinds are decided from the
        //    constructor's DECLARED SHAPE (its result type and its parameters),
        //    never from a hand-listed name.
        $updates = [];
        foreach ($scheme->types() as $type) {
            foreach ($type->constructors() as $name => $constructor) {
                $name = (string) $name;
                if (str_starts_with($name, 'update')) {
                    $updates[$name] = $constructor;
                }
            }
        }
        ksort($updates);

        // 2. Which update ctors the catalog claims as a table's OWN row.
        /** @var array<string,list<string>> $rootFact */
        $rootFact = [];
        foreach ($catalog->tableNames() as $tfName) {
            $entry = $catalog->table($tfName);

            foreach ($entry->ctors as $ctor) {
                $ctor = (string) $ctor;
                if (! str_starts_with($ctor, 'update')) {
                    continue;
                }
                $rootFact[$ctor][] = $tfName;
            }
        }

        // 3. Which update ctors a catalogued entry's nested-source walk DECLARES
        //    as the carried object. Read from the CATALOG's edges rather than
        //    inferred from the payload shape — inferring it is how a wrong edge
        //    gets believed.
        //
        //    A `MirrorNestedSource` names the tf table, the parent TL type, the
        //    wire field and the resolved param — never the carried ctor. The
        //    carried objects are the constructors of the param's base type, so
        //    they are derived here against the same scheme.
        /** @var array<string,list<string>> $nestedSource */
        $nestedSource = [];
        foreach ($catalog->nestedSources() as $source) {
            $carried = $source->param->baseType();
            $carriedType = $scheme->types()[$carried] ?? null;
            if ($carriedType === null) {
                continue;
            }

            foreach (array_keys($carriedType->constructors()) as $ctor) {
                $ctor = (string) $ctor;
                if (! str_starts_with($ctor, 'update')) {
                    continue;
                }
                $nestedSource[$ctor][] = $source->describe();
            }
        }

        // 4. Partition. Precedence: update-carrier and rpc-result FIRST — they
        //    are decided by the constructor's declared RESULT TYPE, which no
        //    catalog edge can change — then root-fact, nested-source, log-only,
        //    then unmodelled. The counts MUST sum to the universe, which the
        //    gate asserts.
        $endpoints = [];
        foreach ($updates as $ctor => $constructor) {
            // 4a. Not a constructor of `Update` ⇒ this is an RPC RESULT, whatever
            //     the catalog says about it. `updates.state` returns
            //     `updates.State`; `updatesTooLong` returns `Updates`; the
            //     difference family returns `updates.Difference`. None of them
            //     IS an update, so none can be a table's own row type and none
            //     owes a `tf_updates` row.
            if ($constructor->resultType !== 'Update' && ! self::carriesUpdateWatermark($constructor)) {
                $fields = self::carriedUpdateFields($constructor);
                if ($fields !== []) {
                    $endpoints[] = [
                        'ctor' => $ctor,
                        'kind' => self::KIND_UPDATE_CARRIER,
                        'tables' => ['tf_updates'],
                        'reason' => 'this is an RPC RESULT (returns '
                            .$constructor->resultType.', not Update), not an update endpoint. It carries real '
                            .'updates in '.implode(', ', array_map(
                                static fn (string $f): string => '"'.$f.':Vector<Update>"',
                                $fields,
                            )).', so the obligation is UNWRAP: each member of that vector must be placed '
                            .'by the per-update ingest path under its OWN constructor. The carrier itself '
                            .'writes no tf_updates row, because a row naming it would record a thing that '
                            .'did not happen as though it were an update.',
                    ];
                } else {
                    $endpoints[] = [
                        'ctor' => $ctor,
                        'kind' => self::KIND_RPC_RESULT,
                        'tables' => [],
                        'reason' => 'this is an RPC RESULT (returns '
                            .$constructor->resultType.', not Update), not an update endpoint: it constructs '
                            .'no Update and declares no Vector<Update> field, so it carries no update and owes '
                            .'no tf_updates row. It is classified and counted here rather than excluded, so a '
                            .'crawl which encounters it has a stated fate instead of a silence.',
                    ];
                }

                continue;
            }

            // 4b. An `Updates`-union constructor that declares no `Update`
            //     member BUT declares the update watermark `pts_count` is still
            //     an update endpoint — see carriesUpdateWatermark(). The three
            //     `*ShortMessage` forms fall here and keep their ordinary
            //     `unmodelled` label, so this branch is only the reason they
            //     are not routed into KIND_RPC_RESULT above.

            if (isset($rootFact[$ctor])) {
                $endpoints[] = [
                    'ctor' => $ctor,
                    'kind' => self::KIND_ROOT_FACT,
                    'tables' => self::unique($rootFact[$ctor]),
                    'reason' => 'the mirror catalog declares this constructor as a table\'s own row type',
                ];

                continue;
            }

            if (isset($nestedSource[$ctor])) {
                $endpoints[] = [
                    'ctor' => $ctor,
                    'kind' => self::KIND_NESTED_SOURCE,
                    'tables' => self::unique($nestedSource[$ctor]),
                    'reason' => 'a catalog nested-source declaration carries this constructor under: '
                        .implode(', ', self::unique($nestedSource[$ctor])),
                ];

                continue;
            }

            // Not modelled as a row by any catalog entry. It still reaches
            // tf_updates and the tf_updates_* children — that path is the generic
            // fact log and needs no catalog entry — so the honest label is a
            // reason naming the MISSING artifact, NOT a silent omission.
            //
            // Two mechanisms, because one reason would be false for the nine
            // constructors that ARE the parent of a declared nested source: those
            // nine ARE partly modelled (their nested object lands in a tf_ table)
            // yet no entry declares them as a row. Naming the real mechanism is
            // the difference between an excuse a reader can act on and a phrase.
            $carriesNested = $catalog->nestedSourcesFor($ctor);
            if ($carriesNested !== []) {
                $tables = self::unique(array_map(
                    static fn (MirrorNestedSource $s): string => $s->tfName,
                    $carriesNested,
                ));
                $reason = 'no catalog entry declares this constructor as a table\'s own row type, '
                    .'though '.count($carriesNested).' catalog nested-source declaration(s) sit '
                    .'under it, filling: '.implode(', ', $tables).'. Its remaining fields reach '
                    .'tf_updates only. To model the rest, add the constructor to a catalog '
                    .'entry\'s `ctors`.';

                $endpoints[] = [
                    'ctor' => $ctor,
                    'kind' => self::KIND_UNMODELLED,
                    'tables' => array_merge(['tf_updates'], $tables),
                    'reason' => $reason,
                ];

                continue;
            }

            $endpoints[] = [
                'ctor' => $ctor,
                'kind' => self::KIND_UNMODELLED,
                'tables' => ['tf_updates'],
                'reason' => 'no catalog entry declares this constructor, and no catalog nested-source '
                    .'declaration sits under it: the payload reaches tf_updates only. To model it, '
                    .'add the constructor to a catalog entry\'s `ctors`, or declare it as a nested '
                    .'source on the parent that carries it.',
            ];
        }

        // Every key is present even at zero, so the generated document's table has a
        // stable shape.
        $counts = [];
        foreach (self::KINDS as $kind) {
            $counts[$kind] = 0;
        }
        foreach ($endpoints as $e) {
            $kind = $e['kind'];
            if (! array_key_exists($kind, $counts)) {
                // The kind set is CLOSED, so an unknown kind can only be a bug
                // in this class — never a data problem. Fail loudly instead of
                // inventing a fifth bucket and reporting a count that lies.
                throw new LogicException(
                    "EndpointCoverage produced the unknown kind '{$kind}' for {$e['ctor']}; the kind set is closed.",
                );
            }
            $counts[$kind]++;
        }

        return new self($endpoints, $counts);
    }

    /**
     * The fields through which this constructor carries a real `Update` — a
     * `Vector<Update>` (`updates`, `updatesCombined`, the difference family) or
     * a singular `Update` ref (`updateShort`).
     *
     * Read from the PARSED constructor's parameters, never from a name list:
     * `updates` and `updatesCombined` call their field `updates`, while
     * `updates.difference`, `updates.differenceSlice` and
     * `updates.channelDifference` all call theirs `other_updates`, so any
     * transcribed field name would be wrong for at least one of them.
     *
     * An empty result means the constructor carries no update at all, which is
     * what separates `KIND_RPC_RESULT` from `KIND_UPDATE_CARRIER`.
     *
     * @return list<string>
     */
    private static function carriedUpdateFields(TlConstructor $constructor): array
    {
        $fields = [];
        foreach ($constructor->allParams() as $param) {
            if ($param->baseType() !== 'Update') {
                continue;
            }

            $kind = $param->kind();
            if ($kind === 'vector' || $kind === 'ref') {
                $fields[] = $param->name;
            }
        }

        return $fields;
    }

    /**
     * TRUE when this constructor carries no update AND carries no update
     * WATERMARK either — which is what makes it an RPC result rather than an
     * update endpoint.
     *
     * ## WHY `pts_count` IS THE SECOND HALF OF THIS TEST
     *
     * A name prefix cannot tell an update from an RPC that returns one, and
     * "does it carry an `Update`" alone is not enough either. Four constructors
     * of the `Updates` union declare NO `Update` member and are still updates:
     * `updateShortMessage`, `updateShortChatMessage`, `updateShortSentMessage`
     * and `updateShort`. Measured on this tree:
     *
     * | ctor                       | result type | `Vector<Update>` | `Update` ref | `pts_count` |
     * |----------------------------|-------------|------------------|--------------|--------------|
     * | `updateShort`              | `Updates`   | no               | **yes**      | no           |
     * | `updateShortMessage`       | `Updates`   | no               | no           | **yes**      |
     * | `updateShortChatMessage`   | `Updates`   | no               | no           | **yes**      |
     * | `updateShortSentMessage`   | `Updates`   | no               | no           | **yes**      |
     * | `updates.state`            | `updates.State` | no          | no           | no           |
     * | `updatesTooLong`           | `Updates`   | no               | no           | no           |
     * | `updates.differenceEmpty`  | `updates.Difference` | no    | no           | no           |
     *
     * The three `*ShortMessage` forms ARE updates in substance: each is a
     * short-poll delivery of one message and each declares `pts` AND
     * `pts_count`, the update-watermark pair no state or error result carries.
     * Classifying them `rpc-result` would have REMOVED a `tf_updates` row
     * obligation from three endpoints that place one today and are green — a
     * silent weakening dressed as a correction, which is the one thing this
     * change must not do.
     *
     * So the test is two-sided and both halves are read off the parsed
     * constructor: no carried `Update`, and no `pts_count` watermark. Measured,
     * that leaves exactly the six constructors that genuinely carry nothing —
     * `updates.state`, `updatesTooLong`, `updates.differenceEmpty`,
     * `updates.differenceTooLong`, `updates.channelDifferenceEmpty`,
     * `updates.channelDifferenceTooLong`.
     */
    private static function carriesUpdateWatermark(TlConstructor $constructor): bool
    {
        foreach ($constructor->allParams() as $param) {
            if ($param->name === 'pts_count') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $in
     * @return list<string>
     */
    private static function unique(array $in): array
    {
        return array_values(array_unique($in));
    }
}
