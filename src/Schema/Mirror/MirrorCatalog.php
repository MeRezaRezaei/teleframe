<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlType;
use MeRezaRezaei\Teleframe\Schema\Mirror\Ddl\MirrorColumn;

final class MirrorCatalog
{
    /** @var array<string, MirrorTableEntry> */
    private array $tables = [];

    /** @var array<string, string> tl type => tf table */
    private array $tlIndex = [];

    /** @var array<string, string> tl constructor => tf table */
    private array $ctorIndex = [];

    /**
     * Declared nested sources resolved against the scheme, indexed by the
     * PARENT CONSTRUCTOR that carries them: parent ctor name => list of
     * sources. This is the walk map — an entry only exists because a catalog
     * author declared it, so the walker visits declared edges and nothing else.
     *
     * @var array<string, list<MirrorNestedSource>>
     */
    private array $nestedSourcesByParentCtor = [];

    /**
     * Memoised ctor name => named params, so the walker's wrapper descent does
     * not re-scan the whole scheme per node.
     *
     * @var array<string, array<string, TlParam>>
     */
    private array $ctorParams = [];

    /**
     * TL types by name, retained so an entry's explicit `key` can be checked
     * against the parameters its constructors actually carry. Populated by
     * `load()`; `fromEntries()` (the in-memory test/consumer path) leaves it
     * empty and the key check then falls back to base columns only.
     *
     * @var array<string, TlType>
     */
    private array $schemes = [];

    /** True when any constructor of the entry's TL type carries a param of this name. */
    private function isCtorParam(MirrorTableEntry $e, string $name): bool
    {
        $type = $this->schemes[$e->tlType] ?? null;
        if ($type === null) {
            return false;
        }
        foreach ($type->constructors() as $ctor) {
            if (isset($ctor->params()[$name])) {
                return true;
            }
        }

        return false;
    }

    /**
     * The base field, AFTER `base[0]`, that declares an enclosing peer scope —
     * i.e. whose shape expands into peer-FLAGGED columns (the curated
     * `peer_type TINYINT + peer_id BIGINT` pair).
     *
     * Detected by decomposing the declared shape and reading the resulting
     * column flags rather than by matching the shape STRING, so the catalog
     * convention cannot drift out from under the gate.
     */
    private function declaredPeerScopeField(MirrorTableEntry $e): ?string
    {
        foreach ($e->base as $index => [$name, $shape]) {
            if ($index === 0) {
                // base[0] IS the key by the catalog's own invariant; the peer
                // branch of the derivation already reads it.
                continue;
            }
            $resolved = MirrorFieldDecomposer::fromShape($shape, $name);
            if ($resolved === null) {
                continue;
            }
            foreach ($resolved as $column) {
                if ($column->peer) {
                    return $name;
                }
            }
        }

        return null;
    }

    /**
     * The base field whose shape expands into this column, or null when no
     * declared shape produces it.
     *
     * A `scoped_by` column is very often NOT a base column NAME: the curated
     * peer pair is declared once as `["peer_id","peer_type TINYINT + peer_id
     * BIGINT"]` and expands to `peer_type` + `peer_id`, so `peer_type` has no
     * base name of its own while being perfectly fillable (it is the type half
     * `MirrorFactDecomposer::fillPeerHalf()` writes). Validating against base
     * NAMES alone would refuse the only scope the curated dial actually uses.
     */
    private function baseFieldProducing(MirrorTableEntry $e, string $col): ?string
    {
        foreach ($e->base as [$name, $shape]) {
            foreach (MirrorFieldDecomposer::fromShape($shape, $name) ?? [] as $produced) {
                if ($produced->name === $col) {
                    return $name;
                }
            }
        }

        return null;
    }

    /**
     * The entry's OWN constructors that carry a param under this name, for an
     * error message an engineer can act on without reading the resolver.
     *
     * @return list<string>
     */
    private function ctorsCarrying(MirrorTableEntry $e, string $field): array
    {
        $type = $this->schemes[$e->tlType] ?? null;
        if ($type === null) {
            return [];
        }
        $carriers = [];
        foreach ($type->constructors() as $ctor) {
            if (isset($ctor->params()[$field])) {
                $carriers[] = $ctor->name;
            }
        }

        return $carriers;
    }

    /**
     * Assert an expressed key derivation is a path the wire actually carries.
     *
     * Two shapes are derivable, and only two:
     *   `param`          — a real parameter of the entry's own TL type
     *                      (tf_bot_infos.id ← user_id, from botInfo's user_id)
     *   `fkField.param`  — the field is declared `FK→Type` on this entry and
     *                      `param` is a real parameter of that type
     *                      (tf_saved_star_gifts.id ← gift.id)
     *
     * Anything longer is refused rather than guessed: a deeper walk would need
     * the same type resolution for every hop, and no shipped table needs it.
     */
    private function assertDerivable(MirrorTableEntry $e, string $col, string $path): void
    {
        $segments = explode('.', $path);

        if (count($segments) === 1) {
            if ($this->isCtorParam($e, $segments[0])) {
                return;
            }

            // THIRD derivable shape, added 2026-10-03: `param` supplied by a
            // declared `sources` CONTEXT overlay.
            //
            // The two shapes above both resolve the value against the entry's
            // OWN TL type, because both predate nested sources: they describe
            // a key the payload carries itself. A `sources` edge breaks that
            // assumption — the walker OVERLAYS the parent's declared `context`
            // fields onto the nested fact before decomposing it
            // (`MirrorNestedSourceWalker::walk()`), so a nested fact's key can
            // legitimately come from the ENCLOSING object.
            //
            // This is the shape `tf_message_medias` and `tf_message_actions`
            // need. Both are context-free union stores whose shipped PK names a
            // synthetic parent-link column (`message_media_id`,
            // `message_action_id`) that no `MessageMedia`/`MessageAction`
            // constructor carries — the value comes from the enclosing MESSAGE,
            // supplied by the edge's `context` overlay. Without this arm both
            // are unkeyable.
            //
            // The overlay is not a single `id`. `message#7600b9d3` numbers
            // `id:int` PER CHAT and also carries `peer_id:Peer`, so `id` alone
            // is not an identity: `tf_message_medias` therefore overlays
            // `["id", "peer_id"]` and declares `scoped_by: ["peer_type",
            // "peer_id"]`. The overlay and the peer scope were both read off
            // the shipped `schema/mirror-catalog.json` when this note was
            // written; the lost-page incident that forced them is recorded in
            // `tests/Ingest/MirrorDeclaredSourcesPlaceRowTest.php` and is not
            // re-measured here.
            //
            // Both entries are MEASURED to declare a `sources` edge in the
            // shipped `schema/mirror-catalog.json`, so neither is unreachable:
            // `tf_message_medias` asserts its edge, overlay and peer scope
            // positively in the
            // `test_a_table_whose_key_became_fillable_carries_the_edge_that_fills_it`
            // case of `tests/Ingest/MirrorDeclaredSourcesPlaceRowTest.php`, and
            // is proven end-to-end by
            // `tests/Schema/Mirror/FabricatedMediaKeyTest.php`, which runs the
            // shipped walker and decomposer over a real `message` payload.
            // `tf_message_actions` still overlays `["id"]` alone, declares no
            // `scoped_by`, and is NOT asserted by that case — its correctness
            // rests on the catalog alone, so this arm is load-bearing for a
            // table no test in this pair currently places.
            //
            // It is checked rather than trusted: the field must be declared in
            // the `context` of one of THIS entry's own sources, and
            // `assertNestedSources` has already proven (separately, for the
            // same source) that it is a real parameter of every parent
            // constructor that edge names. So this arm admits exactly the
            // values the wire genuinely carries at the moment of ingest, and
            // nothing else.
            foreach ($e->sources ?? [] as $source) {
                if (in_array($segments[0], $source['context'], true)) {
                    return;
                }
            }

            throw new MirrorSchemaException(
                "{$e->tfName}: keyFrom '{$col}' => '{$path}' — '{$segments[0]}' is not a parameter of any "
                ."constructor of {$e->tlType}, nor a declared context field of any of its sources"
            );
        }

        if (count($segments) !== 2) {
            throw new MirrorSchemaException(
                "{$e->tfName}: keyFrom '{$col}' => '{$path}' — only 'param' and 'fkField.param' are derivable"
            );
        }

        [$field, $param] = $segments;
        $shape = $this->fieldShape($e, $field);
        if ($shape === null || ! str_starts_with($shape, 'FK→')) {
            throw new MirrorSchemaException(
                "{$e->tfName}: keyFrom '{$col}' => '{$path}' — '{$field}' is not a declared FK→ field of this entry"
            );
        }
        $targetType = substr($shape, 5);
        $type = $this->schemes[$targetType] ?? null;
        if ($type === null) {
            throw new MirrorSchemaException(
                "{$e->tfName}: keyFrom '{$col}' => '{$path}' — FK target type '{$targetType}' is not in the scheme"
            );
        }
        foreach ($type->constructors() as $ctor) {
            if (isset($ctor->params()[$param])) {
                return;
            }
        }
        throw new MirrorSchemaException(
            "{$e->tfName}: keyFrom '{$col}' => '{$path}' — '{$param}' is not a parameter of any constructor of {$targetType}"
        );
    }

    /** The declared shape of a base or child field, or null when undeclared. */
    private function fieldShape(MirrorTableEntry $e, string $field): ?string
    {
        foreach ([...$e->base, ...$e->children] as [$name, $shape]) {
            if ($name === $field) {
                return $shape;
            }
        }

        return null;
    }

    /** @param string $jsonPath @param array<string,array> $catalog */
    public static function load(string $jsonPath, TlScheme $scheme): self
    {
        $json = file_get_contents($jsonPath);
        if ($json === false) {
            throw new MirrorSchemaException("Cannot read catalog: {$jsonPath}");
        }
        $entries = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $entries = (new MirrorCtorSplitter($scheme))->apply($entries);

        $self = new self;
        $self->schemes = $scheme->types();
        $self->fromEntriesInto($entries);

        return $self;
    }

    public static function fromEntries(array $entries): self
    {
        $self = new self;
        $self->fromEntriesInto($entries);

        return $self;
    }

    /** @param array<string,array> $entries */
    private function fromEntriesInto(array $entries): void
    {
        foreach ($entries as $tfName => $e) {
            $entry = new MirrorTableEntry(
                $tfName,
                $e['tl'],
                $e['ctors'],
                $e['base'],
                $e['bools'],
                $e['children'],
                // Optional explicit PK (Cycle 31, second finding). Absent on
                // every pre-existing entry, so the historical derivation stands.
                isset($e['key']) ? array_values(array_map('strval', (array) $e['key'])) : null,
                // Optional declared enclosing scope: base column names that
                // prefix the derived key (2026-10-03).
                isset($e['scoped_by']) ? array_values(array_map('strval', (array) $e['scoped_by'])) : null,
                // Optional expressed key derivation: key column => payload
                // dot-path that supplies it (2026-09-29).
                isset($e['keyFrom']) ? array_map('strval', (array) $e['keyFrom']) : null,
                // Optional nested-source declarations: the parent TL type and
                // wire field this table is really filled from (2026-09-29).
                isset($e['sources']) ? self::readSources($e['sources']) : null,
            );
            $this->tables[$tfName] = $entry;
            $this->tlIndex[$e['tl']] = $tfName;
            foreach ($e['ctors'] as $ctor) {
                $this->ctorIndex[$ctor] = $tfName;
            }
            $this->assertEntryInvariants($entry);
        }
        $this->assertInvariants();
    }

    /** @return list<string> */
    public function tableNames(): array
    {
        return array_keys($this->tables);
    }

    public function table(string $tfName): MirrorTableEntry
    {
        return $this->tables[$tfName] ?? throw new MirrorSchemaException("Unknown mirror table: {$tfName}");
    }

    public function has(string $tfName): bool
    {
        return isset($this->tables[$tfName]);
    }

    public function tableForTlType(string $tlType): ?MirrorTableEntry
    {
        return isset($this->tlIndex[$tlType]) ? $this->tables[$this->tlIndex[$tlType]] : null;
    }

    /**
     * Mirror table a decoded payload constructor belongs to.
     *
     * Decoded TL payloads carry their concrete constructor name ('message',
     * 'user', 'chatEmpty', ...), not the abstract TL type. This resolves the
     * constructor to the owning tf_ table — the daily ingester's
     * ctor→table seam.
     */
    public function tableForCtor(string $ctor): ?MirrorTableEntry
    {
        return isset($this->ctorIndex[$ctor]) ? $this->tables[$this->ctorIndex[$ctor]] : null;
    }

    /**
     * Identity (Ruling B): the first base column must be a key — a scalar or
     * peer-pair column, never a `1:N child` collection. The curated dial keys
     * tables on natural ids (`id`, `peer`, `link`, `title`, `emoticon`, ...),
     * so the column NAME is not constrained: the original id/peer-only rule
     * predates the curated dial and had drifted into dead code that the
     * shipped catalog itself violated (found by W-20 coverage).
     */
    public function assertInvariants(): void
    {
        foreach ($this->tables as $name => $entry) {
            if ($entry->base === []) {
                continue;
            }

            [$col, $shape] = $entry->base[0];
            if ($col === '' || $shape === '1:N child') {
                throw new MirrorSchemaException(
                    "{$name}: first base column must be an identity (scalar/peer), got '{$col}' => '{$shape}'"
                );
            }
        }
    }

    private function assertEntryInvariants(MirrorTableEntry $e): void
    {
        if ($e->ctors === []) {
            throw new MirrorSchemaException("{$e->tfName}: ctors must be non-empty");
        }

        // A declared `key` must be a column the decomposer can actually FILL,
        // or the write path emits a "keying column missing from payload" clue
        // and the row never places. That is precisely the failure the explicit
        // `key` exists to remove, so a key naming neither a base column nor a
        // constructor parameter is a catalog error, caught at load.
        if ($e->key !== null) {
            if ($e->key === []) {
                throw new MirrorSchemaException("{$e->tfName}: explicit key must name at least one column");
            }
            $baseNames = array_column($e->base, 0);
            foreach ($e->key as $col) {
                if (in_array($col, $baseNames, true)) {
                    continue;
                }
                if ($this->isCtorParam($e, $col)) {
                    continue;
                }
                // A declared derivation is the honest way to key on a column
                // the constructor does not carry but the wire does supply
                // (tf_bot_infos.id ← user_id). The path itself is checked
                // below, so this cannot become a laundering route.
                if (isset($e->keyFrom[$col])) {
                    continue;
                }
                throw new MirrorSchemaException(
                    "{$e->tfName}: explicit key '{$col}' is neither a base column nor a parameter of any "
                    ."constructor of {$e->tlType} — the write path can never fill it"
                );
            }
        }

        // `scoped_by: []` is the EMPTY declaration, and it is the exact defect
        // this field exists to end, entered through the new field: `foreach`
        // below iterates nothing, the arm passes vacuously, and the gate
        // believes the author said something. A peer-scoped entry then resolves
        // to a bare `['id']` — silently keyed inside a scope — while reading
        // as declared. `MirrorCtorSplitter::inheritedScopedBy()` already
        // returns null rather than `[]` for exactly this reason ("an empty list
        // would be an error, and `scoped_by: []` must not be mistaken for
        // 'declared'"); this is the parser holding up its end of the contract,
        // mirroring the empty-`key` check at line 377 above.
        if ($e->scopedBy === []) {
            throw new MirrorSchemaException("{$e->tfName}: scoped_by must name at least one column");
        }

        // A declared `scoped_by` must name columns the decomposer can actually
        // FILL, by the same honesty rule `key` obeys one arm above. A scope the
        // payload cannot supply is the fabrication this field exists to end, in
        // a new shape: without this arm an author could silence the gate below
        // with columns no row will ever carry.
        foreach ($e->scopedBy ?? [] as $col) {
            if (in_array($col, array_column($e->base, 0), true)) {
                continue;
            }
            if ($this->baseFieldProducing($e, $col) !== null) {
                continue;
            }
            if ($this->isCtorParam($e, $col) || isset($e->keyFrom[$col])) {
                continue;
            }
            throw new MirrorSchemaException(
                "{$e->tfName}: scoped_by '{$col}' is neither a base column nor a parameter of any "
                ."constructor of {$e->tlType} — the write path can never fill it"
            );
        }

        $this->assertScopeDeclared($e);

        // Every derivation must target a declared key column, and its path
        // must be resolvable against the TL scheme: `param`, or
        // `fkField.param` where the field is declared `FK→Type` and `param` is
        // a real parameter of that type. Anything else is a catalog error that
        // would silently drop the row at ingest.
        foreach ($e->keyFrom ?? [] as $col => $path) {
            if ($e->key === null || ! in_array($col, $e->key, true)) {
                throw new MirrorSchemaException(
                    "{$e->tfName}: keyFrom names '{$col}', which is not one of the declared key columns"
                );
            }
            $this->assertDerivable($e, $col, $path);
        }

        $this->assertNestedSources($e);

        foreach ([...$e->base, ...$e->children] as [$name, $shape]) {
            // zero-NULL: ban nullable marker (but 'NOT NULL' is the canonical non-nullable form)
            if (str_contains($shape, 'NULL') && ! str_contains($shape, 'NOT NULL')) {
                throw new MirrorSchemaException("{$e->tfName}.{$name}: nullable shape '{$shape}'");
            }
            foreach (['json', 'blob', 'binary'] as $banned) {
                if (str_contains(strtolower($shape), $banned)) {
                    throw new MirrorSchemaException("{$e->tfName}.{$name}: banned shape '{$shape}'");
                }
            }
        }
    }

    /**
     * THE SCOPE GATE — the load-time failure that stops the next regeneration
     * from re-creating a wrong key.
     *
     * The name heuristic's rule 1 trusts `base[0] = id` and "never asks what
     * scope Telegram scopes it to". When an entry ALSO declares an enclosing
     * peer scope in a later base field, the scope is not hidden in the protocol
     * — it is written down in the entry, two fields below the identity, and the
     * derivation still ignores it. Telegram numbers messages PER CHAT, so a bare
     * `id` key is not an identity: two chats both hold a message numbered 1 and
     * they collide on the account's own primary key.
     *
     * Failing at LOAD is the point. Left alone, the next `teleframe:regenerate`
     * re-derives the bare `id` and the wrong key returns, however carefully the
     * migrations were patched — which is exactly why this facility has to exist
     * BEFORE the migrations are widened.
     *
     * ## WHY THE TRIGGER IS THE DECLARED SHAPE, NOT A CTOR PARAM NAMED `peer`
     *
     * Measured on the shipped catalog 2026-10-03: four entries have
     * `base[0] = id` and a constructor carrying a `peer`-ish param, and only
     * TWO are peer-scoped. `tf_forum_topics`'s `forumTopic.peer` is the topic
     * AUTHOR rather than the forum (NF5 plan priority 6), and
     * `StarsTransaction.peer` is an `FK→StarsTransactionPeer` child table. A
     * ctor-param predicate would demand a `scoped_by` from both, and the only
     * value either could honestly give does not exist — so the gate would push
     * the dial toward a lie to satisfy itself. Keying on the declared
     * `peer_type TINYINT + peer_id BIGINT` BASE SHAPE is derivable from the
     * entry alone and is always resolvable into a real `scoped_by`.
     */
    private function assertScopeDeclared(MirrorTableEntry $e): void
    {
        // An explicit `key` states the WHOLE identity — scope included — so
        // there is nothing left to declare. Same exemption `keyFrom` needs:
        // a derivation is an expressed route to the scope column.
        if ($e->key !== null || $e->scopedBy !== null) {
            return;
        }

        $first = $e->base[0] ?? null;
        if ($first === null || $first[0] !== 'id') {
            return;
        }

        $scopeField = $this->declaredPeerScopeField($e);
        if ($scopeField === null) {
            return;
        }

        // A key is INHERITED by every child table (`makeChildPkCols()` copies
        // the parent's key columns onto each one), so declaring a scope here is
        // not one table's change: it widens every child's PRIMARY KEY as well,
        // and no catalog edit can move those columns atomically with the
        // parent's because they live in migrations this layer does not own.
        //
        // Measured 2026-10-03 on `tf_messages`, whose 30 children make exactly
        // that a broken intermediate state: declaring `scoped_by` there widened
        // every child's PK to include a `peer_id` column the shipped child DDL
        // does not have, the writer emitted "column 'peer_id' is not in the
        // shipped DDL — field not modelled, skipped" per child, and because the
        // ingest GATE treats a modelling-gap clue as a run failure the baseline
        // went from 596 green to 24 red. The declaration is correct and Task 2.2
        // owns it (its Phase A adds the columns to the ~29 children FIRST, then
        // widens the parent — "in one migration"). Forcing it here would
        // manufacture that broken state and then forbid fixing it.
        if ($e->children !== []) {
            return;
        }

        $carriers = $this->ctorsCarrying($e, $scopeField);
        $carriedBy = $carriers === [] ? 'no constructor of '.$e->tlType : "'".implode("', '", $carriers)."'";

        // The recommendation is DERIVED from the columns the gate actually
        // detected, never transcribed: this method fires on any base shape that
        // expands into peer-flagged columns, so a hardcoded
        // `peer_type TINYINT + peer_id BIGINT` / `"scoped_by":["peer_type",
        // "peer_id"]` would start lying the moment the curated spelling changed
        // — and the trigger would keep firing. The engineer is shown the
        // columns the detector found, in the exact form to paste.
        $scope = $this->peerScopeColumns($e, $scopeField);
        $detected = implode(' + ', array_map(
            static fn (MirrorColumn $c): string => $c->name.' '.$c->type->sql(),
            $scope,
        ));
        $declaration = '"scoped_by":['.implode(',', array_map(
            static fn (MirrorColumn $c): string => '"'.$c->name.'"',
            $scope,
        )).']';

        throw new MirrorSchemaException(
            "{$e->tfName}: base[0] 'id' ignores the enclosing scope its own base declares — "
            ."base field '{$scopeField}' expands into the peer pair "
            ."({$detected}) and is carried by {$carriedBy}, so 'id' is only "
            .'unique inside that scope. Declare '
            .$declaration.' (or an explicit "key" stating the whole identity); '
            .'deriving the key from base[0] alone re-creates a key that collides across peers.'
        );
    }

    /**
     * The peer-flagged columns the detected scope field expands into, in
     * declared order — the columns a `scoped_by` would honestly name.
     *
     * Read from the SAME `MirrorFieldDecomposer` result that
     * `declaredPeerScopeField()` triggered on, so the message can never name a
     * different pair than the gate detected.
     *
     * @return list<MirrorColumn>
     */
    private function peerScopeColumns(MirrorTableEntry $e, string $field): array
    {
        foreach ($e->base as [$name, $shape]) {
            if ($name !== $field) {
                continue;
            }
            $columns = [];
            foreach (MirrorFieldDecomposer::fromShape($shape, $name) ?? [] as $produced) {
                if ($produced->peer) {
                    $columns[] = $produced;
                }
            }

            return $columns;
        }

        return [];
    }

    /**
     * Validate every declared nested source against the TL scheme, and index it
     * by the parent constructors that genuinely carry the field.
     *
     * A source is a claim that "this table is filled from this field of that
     * parent". The scheme is the only thing that can confirm it, so the claim
     * is checked at LOAD, exactly like the `keyFrom` derivation above:
     *   - the named parent must exist in the scheme;
     *   - it must carry the declared field;
     *   - that field's baseType must be THIS table's TL type.
     * A source that fails any of these is a catalog error: it would silently
     * never produce a row, which is the very defect the declaration exists to
     * remove. It is also the guard that stops a declaration from papering over
     * a field the payload cannot carry — the field is proven to exist AND to
     * carry this type, so following it is never a guess.
     *
     * A source may name its parent two ways, and the distinction is load-bearing:
     *   - `"ctor": "updateChannelParticipant"` — ONE concrete wire constructor.
     *     Unambiguous, and the only precise form for a supertype: `Update` has
     *     many constructors sharing field NAMES that mean different things
     *     (`updateChatParticipant.new_participant` is a `ChatParticipant`, while
     *     `updateChannelParticipant.new_participant` is a `ChannelParticipant`),
     *     so naming the supertype would be a claim that cannot be true of all
     *     its carriers — and is rejected as such.
     *   - `"type": "UserFull"` — a TL type, permitted only when EVERY one of
     *     its constructors agrees on the field's type.
     */
    private function assertNestedSources(MirrorTableEntry $e): void
    {
        if ($e->sources === null || $e->sources === []) {
            return;
        }

        if ($this->schemes === []) {
            throw new MirrorSchemaException(
                "{$e->tfName}: declares nested sources, but the catalog was built without a TL scheme — "
                .'nested sources can only be validated against the scheme (use MirrorCatalog::load)'
            );
        }

        foreach ($e->sources as $source) {
            $field = $source['field'];

            if (isset($source['ctor'])) {
                $carriers = $this->carriersForCtor($e, $source['ctor'], $field);
            } else {
                $carriers = $this->carriersForType($e, $source['type'], $field);
            }

            if ($carriers === []) {
                throw new MirrorSchemaException(
                    "{$e->tfName}: source '{$source['parentLabel']}.{$field}' — no constructor carries a field of "
                    ."type {$e->tlType} named '{$field}'"
                );
            }

            // Enclosing context: parent fields overlaid onto the child payload.
            // Each must be a real parameter of the parent constructor, so the
            // value the overlay supplies is one the wire genuinely carries.
            foreach ($source['context'] as $contextField) {
                foreach (array_keys($carriers) as $ctorName) {
                    if (! isset($this->paramsOfCtor($ctorName)[$contextField])) {
                        throw new MirrorSchemaException(
                            "{$e->tfName}: source '{$source['parentLabel']}.{$field}' declares context "
                            ."'{$contextField}', which is not a parameter of {$ctorName}"
                        );
                    }
                }
            }

            $resolved = new MirrorNestedSource(
                $e->tfName,
                $this->tlTypeOfParentLabel($e, $source),
                $field,
                $this->paramFor($carriers, $field, $e->tlType),
                $carriers,
                $source['context'],
            );
            foreach (array_keys($carriers) as $ctorName) {
                $this->nestedSourcesByParentCtor[$ctorName][] = $resolved;
            }
        }
    }

    /**
     * The TL TYPE that carries a declared parent, resolved against the scheme.
     *
     * ## Why this is not simply `$source['parentLabel']`
     *
     * A source names its parent one of two ways, and the two labels are not
     * the same KIND of name:
     *
     *  - `{"type": "UserFull", ...}` — the label IS a TL type name.
     *  - `{"ctor": "updateChannelParticipant", ...}` — the label is a
     *    CONSTRUCTOR name. `UserFull`/`PeerNotifySettings`/`PhoneCall` happen
     *    to be spelled exactly like their own type, so for those two spellings
     *    coincide and the label could be used as a type directly.
     *
     * They do NOT coincide in general: the constructor of `Message` is
     * `message` and the type is `Message`, `Contact`'s is `contactUser`, and
     * an update's type is `Update` while each of its ~200 constructors is
     * `updateXxx`. `parentTlType` is consumed by
     * `MirrorNestedSourceWalker::declaredParentTypesFor()`, which compares it
     * against `TlParam::baseType()` — and `baseType()` is ALWAYS a type name.
     *
     * ## The defect that produced
     *
     * So for every `ctor`-style edge whose constructor is not spelled like its
     * type, the walker compared `'message'` against `'Message'`, never matched,
     * and so never descended into any param of that type. Measured: with a
     * load-validated `{"ctor":"message","field":"media"}` edge installed,
     * `collect()` on a `messages.messages` envelope returned ZERO facts, while
     * the same edge on a bare `message` payload returned the fact. The
     * twenty-one edges that shipped all name types whose spelling matches, so
     * this never fired for them and the gate looked correct.
     *
     * Resolving here — at load, against the scheme already in hand — is the
     * same provenance discipline `assertNestedSources` and `assertDerivable`
     * apply to every other claim in the file: if a parent label names no
     * constructor and no type, this throws rather than storing a label that
     * can never match.
     *
     * @param  array{parentLabel:string,field:string,ctor?:string,type?:string,context:list<string>}  $source
     */
    private function tlTypeOfParentLabel(MirrorTableEntry $e, array $source): string
    {
        // A `"type"` label is already a type name — used verbatim, exactly as
        // the walker consumed it before this method existed.
        if (isset($source['type'])) {
            return $source['type'];
        }

        // A `"ctor"` label is a constructor name: find the type that declares it.
        foreach ($this->schemes as $typeName => $type) {
            foreach ($type->constructors() as $ctor) {
                if ($ctor->name === $source['ctor']) {
                    return $typeName;
                }
            }
        }

        throw new MirrorSchemaException(
            "{$e->tfName}: source parent constructor '{$source['ctor']}' ({$source['parentLabel']}.{$source['field']}) "
            .'belongs to no type in the TL scheme — the walker could never descend into it'
        );
    }

    /**
     * Carriers for a single concrete wire constructor.
     *
     * @return array<string,bool> ctor name => true
     */
    private function carriersForCtor(MirrorTableEntry $e, string $ctorName, string $field): array
    {
        $params = $this->paramsOfCtor($ctorName);

        if (! isset($params[$field])) {
            return [];
        }
        if ($params[$field]->baseType() !== $e->tlType) {
            throw new MirrorSchemaException(
                "{$e->tfName}: source '{$ctorName}.{$field}' is declared to carry {$e->tlType}, but {$ctorName} "
                ."declares it as {$params[$field]->baseType()}"
            );
        }

        return [$ctorName => true];
    }

    /**
     * Carriers for a whole TL type — only when every constructor agrees.
     *
     * @return array<string,bool> ctor name => true
     */
    private function carriersForType(MirrorTableEntry $e, string $typeName, string $field): array
    {
        $type = $this->schemes[$typeName] ?? null;
        if ($type === null) {
            throw new MirrorSchemaException(
                "{$e->tfName}: source parent type '{$typeName}' is not in the TL scheme"
            );
        }

        $carriers = [];
        foreach ($type->constructors() as $ctor) {
            $params = $ctor->params();
            if (! isset($params[$field])) {
                continue;
            }
            if ($params[$field]->baseType() !== $e->tlType) {
                throw new MirrorSchemaException(
                    "{$e->tfName}: source '{$typeName}.{$field}' is declared to carry {$e->tlType}, but "
                    ."{$ctor->name} declares it as {$params[$field]->baseType()} — name the concrete constructor "
                    .'with "ctor" instead of the whole type'
                );
            }
            $carriers[$ctor->name] = true;
        }

        return $carriers;
    }

    /**
     * The TL parameter behind a validated source field, from the first carrier
     * that carries it.
     *
     * @param  array<string,bool>  $carriers
     */
    private function paramFor(array $carriers, string $field, string $tlType): TlParam
    {
        foreach (array_keys($carriers) as $ctorName) {
            $params = $this->paramsOfCtor($ctorName);
            if (isset($params[$field]) && $params[$field]->baseType() === $tlType) {
                return $params[$field];
            }
        }

        throw new MirrorSchemaException(
            "'{$field}': no parameter of type {$tlType} among the validated carriers (catalog invariant broken)"
        );
    }

    /**
     * @param  mixed  $raw  the `sources` value straight from the JSON catalog
     * @return list<array{parentLabel:string,field:string,ctor?:string,type?:string,context:list<string>}>
     */
    private static function readSources(mixed $raw): array
    {
        $out = [];
        foreach ((array) $raw as $source) {
            if (! is_array($source) || ! isset($source['field'])) {
                throw new MirrorSchemaException(
                    'each nested source must name a parent and a field: {"ctor":"<wire ctor>","field":"<field>"} '
                    .'or {"type":"<TL type>","field":"<field>"}'
                );
            }

            $hasCtor = isset($source['ctor']);
            $hasType = isset($source['type']);
            if ($hasCtor === $hasType) {
                throw new MirrorSchemaException(
                    'each nested source must name exactly one parent: either "ctor" or "type"'
                );
            }

            $label = $hasCtor ? (string) $source['ctor'] : (string) $source['type'];
            $entry = ['parentLabel' => $label, 'field' => (string) $source['field']];
            if ($hasCtor) {
                $entry['ctor'] = (string) $source['ctor'];
            } else {
                $entry['type'] = (string) $source['type'];
            }
            // Enclosing context is optional; default to none.
            $context = [];
            foreach ((array) ($source['context'] ?? []) as $contextField) {
                $context[] = (string) $contextField;
            }
            $entry['context'] = $context;
            $out[] = $entry;
        }

        return $out;
    }

    /**
     * Declared nested sources carried by a wire constructor.
     *
     * @return list<MirrorNestedSource>
     */
    public function nestedSourcesFor(string $ctor): array
    {
        return $this->nestedSourcesByParentCtor[$ctor] ?? [];
    }

    /**
     * Every declared nested source, for diagnostics and the coverage test.
     *
     * @return list<MirrorNestedSource>
     */
    public function nestedSources(): array
    {
        $seen = [];
        foreach ($this->nestedSourcesByParentCtor as $sources) {
            foreach ($sources as $source) {
                $seen[$source->describe()] = $source;
            }
        }

        return array_values($seen);
    }

    /**
     * Named parameters of a wire constructor, from the scheme.
     *
     * The nested-source walker needs this to pass through a wrapper node: it
     * looks for params whose TL type is a declared source parent. Null when the
     * constructor is not in the scheme (an unknown ctor has no params to walk).
     *
     * @return array<string, TlParam>
     */
    public function paramsOfCtor(string $ctor): array
    {
        if (array_key_exists($ctor, $this->ctorParams)) {
            return $this->ctorParams[$ctor];
        }

        $params = [];
        foreach ($this->schemes as $type) {
            foreach ($type->constructors() as $candidate) {
                if ($candidate->name === $ctor) {
                    $params = $candidate->params();

                    break 2;
                }
            }
        }

        return $this->ctorParams[$ctor] = $params;
    }
}
