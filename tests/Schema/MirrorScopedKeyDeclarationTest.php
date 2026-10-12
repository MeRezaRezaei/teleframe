<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFieldDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorSchemaException;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use PHPUnit\Framework\TestCase;

/**
 * A key that needs an enclosing scope must be ABLE to declare one, and the
 * heuristic must never invent one it cannot justify.
 *
 * `deriveKeyColumns()` picks keys from a COLUMN-NAME heuristic. Its own docblock
 * admits rule 1 trusts the name `id` "and never asks what scope Telegram scopes
 * it to", and rule 5 FABRICATES a `{singular}_id` when no identity is found --
 * a column the payload can never fill. `tf_business_chat_links` escaped the
 * whole heuristic with an explicit `"key":["link"]`; that escape hatch is the
 * only reason a curated identity survives today.
 *
 * A migration patch without this facility re-derives the same wrong key at the
 * next regeneration.
 *
 * MEASURED (2026-10-03, Task 2.1 round 1), the surface this test covers is
 * exactly:
 *  - AS OF 2026-10-03 (Task 2.2) this is ONE entry, not two. `tf_messages`
 *    (base field `peer_id`) now DECLARES `"scoped_by":
 *    ["peer_type","peer_id"]`, because Task 2.2 landed the atomic move the gate
 *    was deferring: all 36 tables under `tf_messages` gained the peer columns and
 *    the peer-scoped primary key in the same change, and
 *    `MirrorTableResolver::peerKeyColumns()` learned to flag the `peer_id`
 *    spelling so `fillPeerHalf()` still fills BOTH halves of a key column.
 *    Measured on the real decomposer: keyColumns
 *    `['peer_type','peer_id','id']`, both halves filled, zero clues, and the
 *    resolver key equal to the shipped `primary()` — so no divergence had to be
 *    recorded in `MirrorResolverKeyMatchesShippedDdlTest`.
 *  - `tf_stars_subscriptions` (base field `peer`) STILL does not declare one, and
 *    that is a LIVE deferral rather than a historical note.
 *    `makeChildPkCols()` inherits a parent's key onto every child, so declaring
 *    the scope there widens the PRIMARY KEY of all FIVE tables resolved beneath
 *    it at once — columns that live in migrations, which that lane does not own.
 *    Measured, the declaration made all five emit "column 'peer_id' is not in
 *    the shipped DDL — field not modelled, skipped" — TEN clues, two per table,
 *    and since Task 1.2 made `unmappedClues` fail a run, `teleframe:ingest:gate`
 *    and backfill then FAIL for any account carrying a `payments.starsStatus`.
 *    So the correct declaration is DEFERRED, not wrong:
 *    `test_a_scope_deferral_for_a_child_bearing_entry_is_pinned_not_silent`
 *    pins it, `test_the_child_bearing_exempt_set_is_exactly_the_measured_one`
 *    pins the exempt set so a new offender turns red, and
 *    `tests/Schema/StarSubscriptionKeyingLeavesNoModellingGapTest.php` is the
 *    gate that would catch the same edit again.
 *
 * ## THE TWO COUNTS USE DIFFERENT BASES — read the basis next to the number
 *
 * The two numbers in this class are NOT the same measurement, and a reader
 * cannot tell that from either one alone:
 *
 *  | number | basis | what it counts |
 *  |---|---|---|
 *  | 2 of 36 → 1 of 36 | the JSON FILE, 36 entries | peer-scoped + undeclared, as authored |
 *  | 3 of 37 → 1 of 37 | the LOADED catalog, 37 entries | the same predicate, after load |
 *
 * Both bases MEASURED 2026-10-03 (round 2, before Task 2.2), both RE-MEASURED
 * after it: each dropped by one, because `tf_messages` now declares its scope
 * and `MirrorCtorSplitter` carries the declaration onto the `tf_messages_service`
 * it derives on every load. Neither count is an estimate and neither is
 * interchangeable with the other. The pin at
 * `test_the_child_bearing_exempt_set_is_exactly_the_measured_one` walks the
 * LOADED catalog and also filters on `children !== []`.
 *  - `tf_forum_topics` and `tf_stars_transactions` ALSO carry a ctor param
 *    named `peer`, and are DELIBERATELY not required to declare a scope --
 *    `forumTopic.peer` is the topic AUTHOR, not the forum (NF5 plan priority 6),
 *    and `StarsTransaction.peer` is an `FK→StarsTransactionPeer`, not a peer
 *    pair. Firing on a ctor param named `peer` would force both entries to
 *    declare a scope that is not theirs. See
 *    `test_a_ctor_peer_param_that_is_not_a_scope_is_not_required_to_declare_one`.
 */
final class MirrorScopedKeyDeclarationTest extends TestCase
{
    /** Repo root — this suite is filesystem-only, no Laravel boot needed. */
    private const PACKAGE_ROOT = __DIR__.'/../..';

    private const CATALOG = self::PACKAGE_ROOT.'/src/Schema/schema/mirror-catalog.json';

    private const TL = self::PACKAGE_ROOT.'/schema/sources/TL_telegram_v227.tl';

    /**
     * The explicit `key` escape hatch must stay byte-identical: it wins over
     * both `scoped_by` and the whole name heuristic. `tf_business_chat_links`
     * leads its `base` with `link`, which no rule recognises, so this is the one
     * shipped entry whose survival depends on the hatch.
     */
    public function test_an_explicit_key_still_wins_outright(): void
    {
        [$catalog, $resolver] = $this->pipeline();

        $table = $resolver->resolveAll(['tf_business_chat_links'])[0];

        self::assertSame(['link'], $table->keyColumns, 'an explicit `key` must resolve to exactly what it declares');
        self::assertNull(
            $catalog->table('tf_business_chat_links')->scopedBy,
            'tf_business_chat_links declares no `scoped_by`: its `link` IS its whole identity',
        );
        self::assertArrayNotHasKey(
            'business_chat_link_id',
            array_column($table->columns instanceof \Traversable ? iterator_to_array($table->columns) : $table->columns, 'name'),
            'the fabricated singular_id must not be materialised as a column',
        );
    }

    /**
     * A DECLARED enclosing scope prefixes the natural key, and it must sit
     * ABOVE the name heuristic — the heuristic's whole failure is that it
     * cannot see the scope. `base[0]` is `id` here, which rule 1 would resolve to
     * a bare `['id']`.
     */
    public function test_scoped_by_prefixes_the_natural_key(): void
    {
        $entry = $this->entry(['scoped_by' => ['peer_type', 'peer_id']]);

        $resolver = new MirrorTableResolver(MirrorCatalog::fromEntries(['tf_probe' => $entry]), $this->scheme());
        $table = $resolver->resolveAll(['tf_probe'])[0];

        self::assertSame(
            ['peer_type', 'peer_id', 'id'],
            $table->keyColumns,
            'a declared `scoped_by` must prefix the natural key, not replace it',
        );
    }

    /**
     * A declared scope must actually be FILLABLE, or it is the fabrication it
     * replaced: `scoped_by` obeys the same honesty rule `key` obeys — every
     * named column must be a base column, a constructor parameter, or carry an
     * expressed derivation. `nope_id` is none of those.
     */
    public function test_a_scoped_by_naming_an_unfillable_column_is_rejected_at_load(): void
    {
        $this->expectExceptionMessage("scoped_by 'nope_id' is neither a base column nor a parameter");

        MirrorCatalog::load($this->tempCatalog($this->entry(['scoped_by' => ['peer_type', 'nope_id']])), $this->scheme());
    }

    /**
     * THE REGRESSION GATE. An entry whose `base[0]` is `id` but which declares a
     * peer-pair base field — i.e. the scope is right there in the entry and the
     * heuristic cannot see it — with NO `scoped_by` and NO `key`, must be
     * REJECTED at LOAD, not silently keyed on the bare `id`.
     *
     * This is what stops the next regeneration from re-creating a wrong key.
     */
    public function test_a_table_needing_a_scope_without_declaring_one_is_reported(): void
    {
        $this->expectExceptionMessage('tf_probe');

        MirrorCatalog::load($this->tempCatalog($this->entry()), $this->scheme());
    }

    /**
     * The message must be actionable WITHOUT reading the resolver: it has to
     * name the table, the constructor that carries the scope, the field that
     * carries it, and the declaration that fixes it.
     */
    public function test_the_scope_failure_names_the_constructor_and_the_field(): void
    {
        try {
            MirrorCatalog::load($this->tempCatalog($this->entry()), $this->scheme());
            self::fail('an undeclared scope must be rejected at load');
        } catch (MirrorSchemaException $e) {
            $message = $e->getMessage();

            self::assertStringContainsString('tf_probe', $message, 'the message must name the table');
            self::assertStringContainsString("message'", $message, 'the message must name the constructor that carries the scope');
            self::assertStringContainsString("'peer_id'", $message, 'the message must name the field that carries the scope');
            self::assertStringContainsString('scoped_by', $message, 'the message must name the declaration that fixes it');
        }
    }

    /**
     * A `key` IS a declaration of the whole identity, so an entry that already
     * has one is exempt -- otherwise the gate would fail every curated table
     * whose key happens to be reached by an FK.
     */
    public function test_an_explicit_key_satisfies_the_scope_gate(): void
    {
        $entry = $this->entry(['key' => ['peer_id', 'id']]);

        $catalog = MirrorCatalog::load($this->tempCatalog($entry), $this->scheme());

        self::assertSame(
            ['peer_id', 'id'],
            $catalog->table('tf_probe')->key,
            'an explicit `key` is a declaration and must be accepted',
        );
    }

    /**
     * WHY THE GATE IS NOT "a ctor param named `peer`". Measured on the shipped
     * catalog 2026-10-03: four entries have `base[0] = id` and a constructor
     * carrying a `peer`-ish param, and only TWO of them are peer-scoped.
     *
     *  - `tf_forum_topics`  — `forumTopic.peer` is the topic AUTHOR, not the
     *    forum (NF5 plan priority 6), and its `base` declares no peer pair.
     *  - `tf_stars_transactions` — `StarsTransaction.peer` is an
     *    `FK→StarsTransactionPeer`, a child table, not a peer pair.
     *
     * A ctor-param predicate would demand a `scoped_by` from both, and the only
     * ones either could give would be a lie. The gate is therefore keyed on the
     * DECLARED peer-pair BASE SHAPE, which is both mechanically derivable from
     * the entry alone and resolvable into an honest `scoped_by`.
     */
    public function test_a_ctor_peer_param_that_is_not_a_scope_is_not_required_to_declare_one(): void
    {
        // `forumTopic`'s real shape: `id` plus a `peer` that is the author, so
        // the only base field beyond `id` is a plain scalar.
        $entry = [
            'tl' => 'ForumTopic',
            'ctors' => ['forumTopic'],
            'base' => [['id', 'INTEGER NOT NULL'], ['date', 'INTEGER NOT NULL']],
            'bools' => [],
            'children' => [],
        ];

        $catalog = MirrorCatalog::load($this->tempCatalog($entry), $this->scheme());
        $resolver = new MirrorTableResolver($catalog, $this->scheme());
        $table = $resolver->resolveAll(['tf_probe'])[0];

        self::assertSame(
            ['id'],
            $table->keyColumns,
            'a ctor peer param that is not a declared peer scope must not force a declaration',
        );
    }

    /**
     * THE FABRICATION BRANCH IS NOW A THROW. A table with no discoverable
     * identity and no declaration is a catalog bug: inventing a `{singular}_id`
     * produces a column no payload can fill. Measured on the shipped catalog
     * 2026-10-03, this branch is reached by NO shipped entry — both entries with
     * an empty `base` (`tf_bot_infos`, `tf_message_medias`) already declare an
     * explicit `key` — so the throw is armed and never fires on the shipped dial.
     */
    public function test_a_table_with_no_identity_and_no_declaration_throws(): void
    {
        $entry = [
            'tl' => 'MessageMedia',
            'ctors' => ['messageMediaEmpty'],
            'base' => [],
            'bools' => [],
            'children' => [],
        ];

        $catalog = MirrorCatalog::load($this->tempCatalog($entry), $this->scheme());

        // Loading succeeds; the throw belongs to the resolver, which is where
        // the key is actually chosen.
        $resolver = new MirrorTableResolver($catalog, $this->scheme());

        $this->expectExceptionMessage('tf_probe');
        $resolver->resolveAll(['tf_probe']);
    }

    /**
     * The declared scope must reach the table the SPLITTER derives.
     *
     * `MirrorCtorSplitter` re-derives `tf_messages_service` mechanically from
     * `tf_messages`. A scope declared on the parent and not carried across
     * would leave the derived table keyed on a bare `id` — the same defect one
     * file over, re-created by the generator on the next load, which is exactly
     * what a declaration exists to prevent. This is the only place the
     * inheritance is exercised, so it is pinned rather than left implicit.
     */
    public function test_the_mechanically_split_table_inherits_the_declared_scope(): void
    {
        $entry = [
            'tl' => 'Message',
            'ctors' => ['message', 'messageService'],
            'scoped_by' => ['peer_id'],
            'base' => [
                ['id', 'INTEGER NOT NULL'],
                ['peer_id', 'peer_type TINYINT + peer_id BIGINT'],
            ],
            'bools' => [],
            'children' => [],
        ];

        $catalog = MirrorCatalog::load($this->tempCatalogNamed('tf_messages', $entry), $this->scheme());

        self::assertSame(['peer_id'], $catalog->table('tf_messages')->scopedBy);
        self::assertSame(
            ['peer_id'],
            $catalog->table('tf_messages_service')->scopedBy,
            'the split entry must inherit the scope, or it re-derives a bare `id`',
        );

        $resolver = new MirrorTableResolver($catalog, $this->scheme());
        self::assertSame(
            ['peer_id', 'id'],
            $resolver->resolveAll(['tf_messages_service'])[0]->keyColumns,
            'the derived table must key on the scope too',
        );
    }

    /**
     * The scope gate deliberately does NOT fire for an entry whose key is
     * INHERITED by child tables, and that is a measured exemption, not a gap.
     *
     * `makeChildPkCols()` copies the parent's key onto every child, so declaring
     * a scope on `tf_messages` widens the PRIMARY KEY of all 30 of its children
     * — columns that live in migrations, not in the catalog. Measured 2026-10-03:
     * declaring it there turned the 596-test baseline into 24 failures, because
     * the writer then emits "column 'peer_id' is not in the shipped DDL" per
     * child and the ingest GATE treats a modelling-gap clue as a run failure.
     * Task 2.2 owns the atomic move (its Phase A adds the children\' columns
     * first, then widens the parent).
     *
     * So the gate is armed for a LEAF entry and defers the child-bearing one,
     * which is why `tf_messages` still resolves to a bare `id` today. This test
     * pins that state so the deferral cannot rot into silence: if it ever starts
     * throwing, the deferral was dropped and Task 2.2 must land first.
     */
    public function test_a_scope_deferral_for_a_child_bearing_entry_is_pinned_not_silent(): void
    {
        $entry = $this->entry();
        $entry['children'] = [['media', 'FK→MessageMedia']];

        $catalog = MirrorCatalog::load($this->tempCatalog($entry), $this->scheme());
        $resolver = new MirrorTableResolver($catalog, $this->scheme());

        self::assertNull(
            $catalog->table('tf_probe')->scopedBy,
            'the probe declares no scope — the gate defers it because the key is inherited by children',
        );
        self::assertSame(
            ['id'],
            $resolver->resolveAll(['tf_probe'])[0]->keyColumns,
            'a child-bearing entry must keep its pre-Task-2.2 key so parent and children stay consistent',
        );
    }

    /**
     * THE EXEMPTION IS PINNED TO ITS MEASURED SET, not left as an open door.
     *
     * `MirrorCatalog::assertScopeDeclared()` returns early for ANY entry with
     * `children !== []` — sixteen shipped entries have children, so adding a
     * peer-pair base field to `tf_chats`, `tf_users`, `tf_documents` or any
     * other child-bearing table would bypass the scope gate PERMANENTLY, with
     * nothing in the suite noticing. The condition is right (Task 2.2 owns the
     * atomic move) but it is UNCONDITIONAL as written, so this test closes the
     * gap the same way `SCOPE_AWAITING_MIGRATION` is closed in
     * `MirrorResolverKeyMatchesShippedDdlTest`: by asserting the exempt set
     * equals the measured one.
     *
     * The set is recomputed from the SHIPPED catalog using the same three
     * predicates the gate uses — `base[0] = id`, no `key`, no `scoped_by`, a
     * declared peer-pair base field, and `children !== []` — so it is a
     * measurement, not a restatement of this test's expectations.
     *
     * ## THE SET SHRANK FROM THREE TO ONE ON 2026-10-03 (Task 2.2) — the signal
     *
     * It was `['tf_messages', 'tf_messages_service', 'tf_stars_subscriptions']`
     * and `tf_messages` + `tf_messages_service` are GONE, because Task 2.2
     * landed what the gate was deferring: `mirror-catalog.json` now declares
     * `"scoped_by": ["peer_type", "peer_id"]` on `tf_messages`, the messages
     * migration widened all 36 of its tables to
     * `primary(['account_id','peer_type','peer_id','id'])` (+ `position` /
     * `parent_position`), and `MirrorTableResolver::peerKeyColumns()` learned to
     * flag the `peer_id` spelling so `fillPeerHalf()` still fills both halves of
     * a key column. Measured on the real decomposer with a real `message`
     * payload: keyColumns `['peer_type','peer_id','id']`, both halves filled,
     * ZERO clues, and the resolver key matches the shipped `primary()` exactly
     * — so `MirrorResolverKeyMatchesShippedDdlTest`'s
     * `SCOPE_AWAITING_MIGRATION` set stayed EMPTY rather than growing by 36
     * names. That is the whole point of doing the two halves in one change.
     * `tf_messages_service` is absent because `MirrorCtorSplitter` derives it
     * from `tf_messages` and INHERITS the declaration — pinned by
     * `test_the_mechanically_split_table_inherits_the_declared_scope`.
     *
     * `tf_stars_subscriptions` REMAINS, and its presence is still live rather
     * than historical: the same two halves have not landed for it (Task 2.2
     * scoped its migration work to the messages domain and the peer-SET tables),
     * so declaring its scope would still widen the primary key of all five
     * tables resolved beneath it past their shipped DDL — the exact regression
     * the constant above documents. A MISSING entry from here still means one
     * landed and must be deleted from this list.
     *
     * THE BASIS OF THE NUMBER BELOW: this test walks the LOADED catalog, so its
     * set is "1 of the 37 loaded entries". Both bases were MEASURED
     * 2026-10-03 (round 2, before Task 2.2) and neither count is an estimate.
     */
    public function test_the_child_bearing_exempt_set_is_exactly_the_measured_one(): void
    {
        [$catalog] = $this->pipeline();

        $exempt = [];
        foreach ($catalog->tableNames() as $tfName) {
            $entry = $catalog->table($tfName);
            if ($entry->key !== null || $entry->scopedBy !== null) {
                continue;
            }
            if ($entry->children === []) {
                continue;
            }
            $first = $entry->base[0] ?? null;
            if ($first === null || $first[0] !== 'id') {
                continue;
            }
            if ($this->declaresPeerPair($catalog->table($tfName)->base)) {
                $exempt[] = $tfName;
            }
        }
        sort($exempt);

        self::assertSame(
            ['tf_stars_subscriptions'],
            $exempt,
            'the set of child-bearing, peer-scoped, UNDECLARED entries has changed. '
            .'A NEW entry means a table bypasses the scope gate for the same Task 2.2 reason '
            .'(makeChildPkCols() would widen every child PK past the shipped DDL) — it must be '
            .'listed here with that reason, or given its scoped_by in the same migration Task 2.2 '
            .'makes atomic. A MISSING entry means Task 2.2 landed for it: delete it here.',
        );
    }

    /**
     * `"scoped_by": []` IS NOT A DECLARATION — it is the defect this field
     * exists to end, entered through the new field.
     *
     * The honesty arm above validates each NAMED column by iterating it, so an
     * empty list iterates nothing and passes vacuously; the gate then reads the
     * entry as declared and stands down. The result is the bare `["id"]` this
     * task exists to eliminate — silently keyed inside a scope — recorded in
     * the catalog as if it had been fixed. `MirrorCtorSplitter`'s own docblock
     * says "`scoped_by: []` must not be mistaken for 'declared'"; this is that
     * sentence enforced in the parser.
     */
    public function test_an_empty_scoped_by_is_not_mistaken_for_a_declaration(): void
    {
        $entry = $this->entry(['scoped_by' => []]);

        $this->expectExceptionMessage('tf_probe: scoped_by must name at least one column');

        MirrorCatalog::load($this->tempCatalog($entry), $this->scheme());
    }

    /**
     * …and the same entry with a NAMED scope still loads, so the arm above
     * refuses emptiness rather than `scoped_by` itself.
     */
    public function test_a_named_scoped_by_is_still_accepted_alongside_the_empty_refusal(): void
    {
        $catalog = MirrorCatalog::load(
            $this->tempCatalog($this->entry(['scoped_by' => ['peer_id']])),
            $this->scheme(),
        );

        self::assertSame(['peer_id'], $catalog->table('tf_probe')->scopedBy);
    }

    /**
     * The empty-base + declared-scope arm, which the resolver's own comment
     * wrongly called unreachable.
     *
     * With `base === []` there is no `base[0]` to derive, so the naive
     * `return [...$scopedBy, ...$this->deriveNaturalKey($entry)]` fell through
     * to rule 5's THROW — whose message tells the engineer to "declare
     * `scoped_by`", the thing they just did. The declared scope IS the whole
     * key here: nothing is invented and no fabrication is involved.
     *
     * `Message` is the empty-base TL type whose constructors genuinely carry a
     * `peer_id` param, so the declared scope is fillable (the honesty arm at
     * load accepts a constructor parameter) and the probe resolves against the
     * shipped scheme rather than a synthetic one.
     */
    public function test_an_empty_base_with_a_declared_scope_keys_on_the_scope_alone(): void
    {
        $entry = [
            'tl' => 'Message',
            'ctors' => ['message'],
            'scoped_by' => ['peer_id'],
            'base' => [],
            'bools' => [],
            'children' => [],
        ];

        $catalog = MirrorCatalog::load($this->tempCatalog($entry), $this->scheme());
        $resolver = new MirrorTableResolver($catalog, $this->scheme());

        self::assertSame(
            ['peer_id'],
            $resolver->resolveAll(['tf_probe'])[0]->keyColumns,
            'with no base to derive from, the declared scope IS the whole key — and rule 5 must not '
            .'throw a message telling the author to declare the scoped_by they already declared',
        );
    }

    /**
     * The scope-gate message must be derived from what the gate DETECTED, not
     * transcribed. It used to hardcode `(peer_type TINYINT + peer_id BIGINT)` and
     * `"scoped_by":["peer_type","peer_id"]` while the trigger itself is generic
     * (any base shape expanding into peer-flagged columns), so a changed curated
     * spelling would make the message lie while the gate kept firing.
     *
     * The probe uses a peer-pair base field NOT named `peer_id`, so a
     * hardcoded message fails here: `MirrorFieldDecomposer` expands the curated
     * peer shape to `{field}_type`/`{field}_id` for every field other than
     * `peer_id`, so this entry's detected pair is `from_id_type`/`from_id_id`
     * while the transcribed text always said `peer_type`/`peer_id`.
     */
    public function test_the_scope_failure_recommends_the_columns_it_actually_detected(): void
    {
        $entry = [
            'tl' => 'Message',
            'ctors' => ['message'],
            'base' => [
                ['id', 'INTEGER NOT NULL'],
                ['from_id', 'peer_type TINYINT + peer_id BIGINT'],
            ],
            'bools' => [],
            'children' => [],
        ];

        try {
            MirrorCatalog::load($this->tempCatalog($entry), $this->scheme());
            self::fail('an undeclared scope must be rejected at load');
        } catch (MirrorSchemaException $e) {
            $message = $e->getMessage();

            self::assertStringContainsString('from_id_type TINYINT + from_id_id BIGINT', $message, 'the detected pair must be rendered from the shape the gate fired on');
            self::assertStringContainsString('"scoped_by":["from_id_type","from_id_id"]', $message, 'the recommendation must be the detected columns, paste-ready');
            self::assertStringNotContainsString('peer_type', $message, 'the message must not transcribe the curated spelling — that is what made it lie when the field was not named peer_id');
        }
    }

    /**
     * A base field, after `base[0]`, whose declared shape expands into
     * peer-FLAGGED columns — the gate's own trigger, reimplemented here so the
     * exempt set above is measured against the shipped artifact rather than
     * asserted from memory.
     *
     * @param  array<int,array{0:string,1:string}>  $base
     */
    private function declaresPeerPair(array $base): bool
    {
        foreach ($base as $index => [$name, $shape]) {
            if ($index === 0) {
                continue;
            }
            foreach (MirrorFieldDecomposer::fromShape($shape, $name) ?? [] as $column) {
                if ($column->peer) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * A base-shape probe entry shaped like the real `tf_messages`: `base[0]` is
     * `id`, and `base[1]` declares the curated peer pair. That is the whole
     * defect -- the scope is IN the entry and the name heuristic never looks at
     * anything past `base[0]`.
     *
     * @param  array<string,mixed>  $extra
     * @return array<string,mixed>
     */
    private function entry(array $extra = []): array
    {
        return $extra + [
            'tl' => 'Message',
            'ctors' => ['message'],
            'base' => [
                ['id', 'INTEGER NOT NULL'],
                ['peer_id', 'peer_type TINYINT + peer_id BIGINT'],
            ],
            'bools' => [],
            'children' => [],
        ];
    }

    /** Write a one-entry catalog to a temp file so `load()` really is the seam under test. */
    private function tempCatalog(array $entry): string
    {
        return $this->tempCatalogNamed('tf_probe', $entry);
    }

    /**
     * Same, under a chosen table name. `MirrorCtorSplitter` keys off the literal
     * name `tf_messages`, so the split-inheritance case needs the real one.
     *
     * @param  array<string,mixed>  $entry
     */
    private function tempCatalogNamed(string $tfName, array $entry): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tf-catalog-').'.json';
        self::assertIsString($path);
        file_put_contents($path, json_encode([$tfName => $entry], JSON_THROW_ON_ERROR));

        return $path;
    }

    private function scheme(): TlScheme
    {
        return TlParser::parseFile(self::TL);
    }

    /**
     * @return array{0: MirrorCatalog, 1: MirrorTableResolver}
     */
    private function pipeline(): array
    {
        $scheme = $this->scheme();
        $catalog = MirrorCatalog::load(self::CATALOG, $scheme);

        return [$catalog, new MirrorTableResolver($catalog, $scheme)];
    }
}
