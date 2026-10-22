<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChannel;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChat;
use MeRezaRezaei\Teleframe\Mirror\Models\TfDialog;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdate;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUser;
use MeRezaRezaei\Teleframe\Mirror\Models\UnmappedEntityPeer;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerShapeTool;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfAccounts;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChat;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;
use MeRezaRezaei\Teleframe\Schema\Generator\HorizontalEntityRelations;
use MeRezaRezaei\Teleframe\Schema\Generator\ModelGenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\Naming;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorShape;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * The gate for the HORIZONTAL relations — the entity-to-entity axis that no
 * declared foreign key on this schema can express.
 *
 * ## WHY THIS FILE EXISTS, IN THE OPERATOR'S WORDS
 *
 * > "we must make it laravel models and all relations between them so when an
 * > update comes we can query it in fact … a consuming app can get any data in
 * > place, using all relations and data, in any app that uses this package"
 * > — `.brain/verbatim/2026-10-01-nf5-integrity-first-and-v1-v2-boundary.md:20-27`
 *
 * and the trap that makes a bad answer worse than no answer at all:
 *
 * > **A `belongsTo` whose foreign key does not exist returns an EMPTY relation,
 * > which is indistinguishable from "genuinely no rows".**
 *
 * So a relation that traverses and finds nothing is NOT evidence. This file is
 * built so that every horizontal relation is checked three ways — the LEGS
 * exist in the EXECUTED schema, the relation TRAVERSES to a row, and a DECOY row
 * of the same id in the WRONG entity table is NOT returned. A relation that
 * quietly stopped matching cannot pass any of the three.
 *
 * ## WHAT IS DERIVED AND WHAT IS TRANSCRIBED
 *
 * Nothing is transcribed. The relation population comes from
 * {@see HorizontalEntityRelations} run over the EXECUTED migration set and
 * over the committed `mirror-shape.json`; the schema shapes come from
 * `Schema::getColumns()` on the booted database; the target tables come from
 * `Naming::domainTable()`, which is the same function the generator reads. A
 * table shape or a join column list written into this file would be a second
 * copy of the DDL, and `ShippedStoreSchema`'s own docblock records what that
 * costs.
 *
 * ## WHY THE DECOY IS THE CENTRAL ASSERTION
 *
 * Telegram ids overlap across the three peer kinds — a chat id and a user id
 * can carry the same number, and the mirror keys each on `(account_id, id)`.
 * So a relation that keyed only on `peer_id` would return a row, just the
 * WRONG row, and every "does it return something?" assertion would be green.
 * Seeding the SAME id into `tf_users`, `tf_chats` and `tf_channels` and then
 * asserting which table comes back is the only assertion that can tell those
 * two cases apart.
 */
final class HorizontalEntityRelationsTest extends IngestTestCase
{
    /** A tenant no real account holds. */
    private const ACCOUNT = 71;

    /** The one id planted in ALL THREE identity tables, so only the discriminator can tell them apart. */
    private const SHARED_ID = 9004242;

    protected function ingestTenantIds(): array
    {
        return [self::ACCOUNT];
    }

    // =======================================================================
    // 1. THE CENSUS — the generator emitted everything the shape derives.
    // =======================================================================

    /**
     * Every derived peer pair reached BOTH ways: a forward method on the child
     * and a discriminator-pinned reverse method on each entity it can name.
     *
     * The half that is easy to get wrong is the reverse one, because it is
     * multiplied by the number of kinds — and dropping a kind does not raise,
     * it just makes one of the three entity classes unable to reach the rows
     * that name it. That is the operator's trap again, one level down: a host
     * asking a CHAT for its dialogs would get an empty collection and conclude
     * there are none.
     */
    public function test_every_derived_peer_pair_is_reachable_from_both_ends(): void
    {
        $relations = $this->derivedRelations();
        $misses = [];

        foreach ($this->horizontal()->peerPairs() as $child => $bases) {
            foreach ($bases as $base) {
                $childModel = $this->modelFor($child);
                $forward = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $base))));

                if ($childModel === null || ! method_exists($childModel, $forward)) {
                    $misses[] = $childModel === null
                        ? $child.': no generated model class'
                        : $child.'::'.$forward.'() — forward half missing';

                    continue;
                }

                foreach ($this->horizontal()->kindsFor($base) as $kind => $domain) {
                    $entity = $this->entityModel($domain);
                    $stem = ModelGenerator::studlyStem($child);
                    $reverse = $forward.ucfirst($stem);
                    $actual = $this->reverseMethodOn($domain, $reverse);

                    if ($actual === null) {
                        $misses[] = sprintf(
                            '%s::%s() — reverse half missing for kind %d',
                            $entity,
                            $reverse,
                            $kind,
                        );
                    }
                }
            }
        }

        self::assertSame(
            [],
            $misses,
            'the derivation produced an edge that no model exposes, so the horizontal graph is '
            .'incomplete — and a host reaching for the missing accessor reads an empty relation as '
            .'"no rows". Misses:\n'.implode("\n", $misses),
        );

        // The population this ran over must not be empty, or the loop above
        // proves nothing. MEASURED on this tree: 87 pairs over 66 tables.
        self::assertGreaterThan(
            0,
            $this->relationCount($relations),
            'no horizontal relation was derived, so this gate is inspecting an empty set and every '
            .'assertion it supports is decoration. See HorizontalEntityRelations for what each '
            .'mechanism reads.',
        );
    }

    /**
     * THE CLOSED-SET GATE for the one hand-maintained list in the derivation.
     *
     * `HorizontalEntityRelations::BASE_ENTITY` is the only place a human says
     * "this peer base names exactly one entity", because nothing in the DDL can.
     * It is therefore the one list that can go stale silently: a new `{base}_type`
     * pair lands, names neither a peer nor a listed entity, and every reverse
     * relation for it is quietly withheld — the failure being a permanently
     * empty relation, not an error.
     *
     * So the list is asserted CLOSED against the executed shape.
     */
    public function test_every_derived_peer_base_names_an_entity(): void
    {
        $orphans = [];

        foreach ($this->horizontal()->peerPairs() as $child => $bases) {
            foreach ($bases as $base) {
                if ($this->horizontal()->kindsFor($base) !== []) {
                    continue;
                }

                $orphans[] = $child.'.'.$base.'(type/id)';
            }
        }

        self::assertSame(
            [],
            $orphans,
            'a peer base now exists that names neither a `peer` union nor one entry of '
            .'HorizontalEntityRelations::BASE_ENTITY, so NO reverse relation was emitted for it and every '
            .'entity class is silently unable to reach those rows. That is an empty relation where a host '
            .'expects data — the exact failure this slice exists to remove. Add the base to BASE_ENTITY '
            .'with the entity Telegram writes there. Offenders: '.implode('; ', $orphans),
        );
    }

    /**
     * ANTI-REGRESSION for the residue trap.
     *
     * `tf_messages_media` and `tf_messages_service_action` carry `user_id`,
     * `chat_id` and `channel_id` columns, and `2026_09_14_200012:56-88` calls
     * them *"scalar residue of each ctor (nested objects deferred)"*. They hold
     * 0 on essentially every row the mirror can store.
     *
     * A `belongsTo` on one of those returns an empty relation forever, and an
     * empty relation is indistinguishable from "genuinely no rows" — so a host
     * concludes Telegram never sent the contact. The catalog is what keeps
     * these out; this is the gate that says so out loud.
     */
    public function test_no_relation_is_emitted_on_a_deferred_object_residue_column(): void
    {
        foreach (['tf_messages_media', 'tf_messages_service_action'] as $table) {
            self::assertTrue(
                $this->hasColumn($table, 'user_id'),
                $table.' no longer carries user_id, so this gate is asserting about a shape the shipped '
                .'DDL does not have and is proving nothing. Re-derive the residue families.',
            );
        }

        $catalog = $this->catalog();
        self::assertFalse(
            $this->catalogDeclares($catalog, 'tf_messages_media', 'user_id'),
            'the catalog now DECLARES tf_messages_media.user_id, so the residue argument no longer holds '
            .'for that column — either the nested object landed and a relation is now correct, or the '
            .'catalog is wrong. Re-read the residue note in 2026_09_14_200012 before trusting this.',
        );

        $model = $this->modelFor('tf_messages_media');
        self::assertNotNull($model, 'no generated model for tf_messages_media');
        self::assertFalse(
            method_exists($model, 'user'),
            'tf_messages_media now declares user(). If the nested contact object landed that is correct '
            .'and this assertion is stale; if it did not, this is a relation that can only ever return '
            .'an empty collection, and a host reading that concludes the data is missing from Telegram.',
        );
    }

    // =======================================================================
    // 2. THE LEGS EXIST — executed schema, not "it returned a collection".
    // =======================================================================

    /**
     * Every horizontal relation constrains columns that EXIST, in the executed
     * schema, on the table the relation qualifies.
     *
     * This is the assertion that distinguishes "no rows" from "no leg". A
     * relation whose column was renamed or dropped keeps returning an empty
     * collection, silently and forever; only a check against the live schema can
     * tell the two apart, and only this one can.
     */
    public function test_every_horizontal_relation_constrains_columns_that_exist(): void
    {
        $inspected = 0;
        $missing = [];

        foreach ($this->derivedRelations() as $table => $methods) {
            $model = $this->modelFor($table);
            if ($model === null) {
                continue;
            }

            $instance = $this->hydrated($model);

            foreach ($methods as $method) {
                $relation = $instance->{$method}();
                if ($relation === null) {
                    continue;
                }

                $inspected++;

                foreach ($this->legsOf($relation) as [$column, $against]) {
                    if ($this->hasColumn($against, $column)) {
                        continue;
                    }

                    $missing[] = sprintf('%s::%s() constrains %s.%s', $table, $method, $against, $column);
                }
            }
        }

        self::assertGreaterThan(
            0,
            $inspected,
            'no horizontal relation could be constructed, so the leg scan above inspected nothing and its '
            .'empty result is meaningless. The scan IS the assertion.',
        );

        self::assertSame(
            [],
            $missing,
            'these relations constrain a column that does not exist in the executed schema. They will '
            .'return an empty collection on every row, forever, and an empty collection is '
            .'indistinguishable from "genuinely no rows": '.implode('; ', $missing),
        );
    }

    /**
     * THE CONTROL HALF — the leg scan above can SEE a leg go away.
     *
     * Without this, `hasColumn()` could be reading a transcribed list and would
     * keep reporting green after the column was renamed, which would make the
     * gate above a decoration in the strict sense: passing, and guarding
     * nothing.
     */
    public function test_the_leg_scan_bites_when_a_column_is_renamed_away(): void
    {
        $model = $this->modelFor('tf_dialogs');
        self::assertNotNull($model);
        self::assertTrue($this->hasColumn('tf_dialogs', 'peer_id'));

        $before = $this->missingLegsFor($this->hydrated($model), 'peer');
        self::assertSame([], $before, 'the leg scan reports tf_dialogs::peer() as missing a leg BEFORE '
            .'anything was changed, so the control below would prove nothing.');

        // The rename is a CONTROL, and a control must not outlive the test that
        // plants it. Leaving `peer_id` renamed made every later test in this
        // class that inserts into tf_dialogs fail with "no column named
        // peer_id" — which is why this test passed under --filter and failed
        // in any directory run. Restored in `finally` so the restore happens
        // even when an assertion below throws.
        try {
            Schema::table('tf_dialogs', function ($table): void {
                $table->renameColumn('peer_id', 'peer_id_renamed');
            });

            self::assertFalse(
                $this->hasColumn('tf_dialogs', 'peer_id'),
                'the rename did not take, so the control half never removed a leg and proves nothing.',
            );

            $after = $this->missingLegsFor($this->hydrated($model), 'peer');

            self::assertNotSame(
                [],
                $after,
                'tf_dialogs.peer_id was renamed away and the leg scan still reports every leg present. It is '
                .'therefore not reading the live schema, and test_every_horizontal_relation_constrains_'
                .'columns_that_exist is decoration.',
            );
        } finally {
            Schema::table('tf_dialogs', function ($table): void {
                $table->renameColumn('peer_id_renamed', 'peer_id');
            });
        }
    }

    // =======================================================================
    // 3. TRAVERSAL — with a decoy in the wrong table.
    // =======================================================================

    /**
     * `tf_dialogs` follows its own peer triple to the right ENTITY TABLE.
     *
     * All three identity tables carry the SAME id here, so a relation that
     * ignored the discriminator would return a row — the wrong one — and every
     * existence check would be green.
     */
    public function test_a_dialog_resolves_to_the_entity_its_discriminator_names(): void
    {
        $this->plantSharedIdPeers();
        $this->plantDialogs();

        $expected = [
            PeerShapeTool::PEER_USER => 'tf_users',
            PeerShapeTool::PEER_CHAT => 'tf_chats',
            PeerShapeTool::PEER_CHANNEL => 'tf_channels',
        ];

        foreach ($expected as $kind => $table) {
            $dialog = $this->dialogOfKind($kind);
            $peer = $dialog->peer();

            self::assertNotNull($peer, 'peer_type '.$kind.' resolved to null, which means the relation '
                .'found no row for a peer this fixture DID plant under id '.self::SHARED_ID.'.');

            self::assertInstanceOf(BelongsTo::class, $peer);

            $resolved = $peer->first();

            self::assertNotNull(
                $resolved,
                'peer_type '.$kind.' resolved to no row although a row was planted under id '
                .self::SHARED_ID.' in every identity table.',
            );

            self::assertSame(
                $table,
                $resolved->getTable(),
                'peer_type '.$kind.' resolved to '.$resolved->getTable().' instead of '.$table.'. All '
                .'three identity tables hold id '.self::SHARED_ID.' in this fixture, so this is exactly '
                .'the confidently-wrong case: a row came back, and it is the wrong entity.',
            );
        }
    }

    /**
     * The reverse direction is discriminator-pinned, not just id-pinned.
     *
     * `TlUser::peerDialogs()` and `TlChat::peerDialogs()` must each return
     * ONE dialog, not the three this fixture planted. Without the pinned
     * `{base}_type` predicate both would return all three and a host would see
     * a user's dialogs containing the user's channels.
     */
    public function test_the_reverse_side_is_pinned_to_the_peer_kind(): void
    {
        $this->plantSharedIdPeers();
        $this->plantDialogs();

        $expected = [
            'users' => PeerShapeTool::PEER_USER,
            'chats' => PeerShapeTool::PEER_CHAT,
            'channels' => PeerShapeTool::PEER_CHANNEL,
        ];

        // BOTH LAYERS, and that is the load-bearing half of this test.
        //
        // `TfUser` and `TlUser` are two classes over the same `tf_users` table,
        // and only the generated anchor carries the complete derived set. A
        // traversal that runs on the hand model alone would leave the generated
        // one unchecked — which is exactly what happened the first time this
        // file was run: the discriminator pin was deleted from `TlUser` and
        // this test stayed green.
        foreach (['hand', 'generated'] as $layer) {
            foreach ($expected as $domain => $kind) {
                $peer = $layer === 'hand'
                    ? $this->sharedPeer($domain)
                    : $this->sharedAnchor($domain);

                $dialogs = $peer->peerDialogs()->get();

                self::assertCount(
                    1,
                    $dialogs,
                    $layer.' '.$domain.'::peerDialogs() returned '.count($dialogs).' dialogs where '
                    .'exactly 1 names kind '.$kind.'. All three dialogs carry peer_id = '
                    .self::SHARED_ID.', so an un-pinned relation returns all three and a host sees one '
                    .'entity\'s peers belonging to another.',
                );

                self::assertSame(
                    $kind,
                    (int) $dialogs->first()->getAttribute('peer_type'),
                    'the one dialog '.$layer.' '.$domain.' returned does not carry the kind the entity is.',
                );
            }
        }
    }

    /**
     * The GENERATED anchor's row, hydrated from the same planted row.
     *
     * Hydrated rather than queried so the returned type is the concrete class
     * and `peerDialogs()` resolves under static analysis; the row is the one
     * {@see self::plantSharedIdPeers()} wrote, and the table is named by
     * `Naming` rather than transcribed.
     */
    private function sharedAnchor(string $domain): TlUser|TlChat|TlChannel
    {
        $table = Naming::domainTable($domain);

        $row = DB::table($table)
            ->where('account_id', self::ACCOUNT)
            ->where('id', self::SHARED_ID)
            ->first();

        self::assertNotNull($row, 'no '.$table.' row was planted under the shared id '.self::SHARED_ID);

        return match ($domain) {
            'users' => $this->hydrate(TlUser::class, (array) $row),
            'chats' => $this->hydrate(TlChat::class, (array) $row),
            'channels' => $this->hydrate(TlChannel::class, (array) $row),
            default => self::fail('unknown mirror domain: '.$domain),
        };
    }

    /**
     * A model of the requested class carrying `$row`.
     *
     * Generic rather than a cast, so the caller gets the CONCRETE type back and
     * `peerDialogs()` resolves without a suppression or a widening.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $class
     * @param  array<string,mixed>  $row
     * @return TModel
     */
    private function hydrate(string $class, array $row): Model
    {
        $model = new $class;
        $model->setRawAttributes($row, true);

        return $model;
    }

    /**
     * `tf_updates` reaches the entity each of its three folded scalar columns
     * names — the update→entity leg, which the table had no way to express.
     */
    public function test_an_update_reaches_each_entity_its_folded_column_names(): void
    {
        $this->plantSharedIdPeers();
        $this->plantUpdates();

        $expected = [
            'user' => 'tf_users',
            'chat' => 'tf_chats',
            'channel' => 'tf_channels',
        ];

        $row = DB::table('tf_updates')
            ->where('account_id', self::ACCOUNT)
            ->orderBy('seq')
            ->first();

        self::assertNotNull($row, 'no tf_updates row was planted');

        $update = new TfUpdate;
        $update->setRawAttributes((array) $row, true);

        foreach ($expected as $method => $table) {
            $resolved = match ($method) {
                'user' => $update->user()->first(),
                'chat' => $update->chat()->first(),
                'channel' => $update->channel()->first(),
            };

            self::assertNotNull(
                $resolved,
                'TfUpdate::'.$method.'() found nothing although id '.self::SHARED_ID.' was planted in '
                .$table.'. The column is NOT NULL DEFAULT 0, so "no row" here means the leg did not '
                .'match — which is the failure this gate exists to catch.',
            );

            self::assertSame(
                $table,
                $resolved->getTable(),
                'TfUpdate::'.$method.'() resolved to '.$resolved->getTable().' rather than '.$table.'.',
            );
        }
    }

    /**
     * The TENANT ROOT reaches its anchors.
     *
     * `tf_accounts` carries no constraint at all — it has no `account_id`
     * column, so it structurally cannot hold the tenant FK that 264 other
     * `tf_*` tables declare against it. That made it the root every query
     * scopes through, with no way out.
     */
    public function test_the_tenant_root_reaches_each_domain_anchor(): void
    {
        $this->plantSharedIdPeers();
        $this->plantDialogs();
        $this->plantUpdates();
        $this->plantTenantRoot();

        $root = TfAccounts::query()
            ->where('id', self::ACCOUNT)
            ->first();

        self::assertNotNull($root, 'the tf_accounts tenant root row was not planted');

        $expected = [
            'users' => 'tf_users',
            'chats' => 'tf_chats',
            'channels' => 'tf_channels',
            'dialogs' => 'tf_dialogs',
            'updates' => 'tf_updates',
        ];

        foreach ($expected as $method => $table) {
            self::assertTrue(
                method_exists($root, $method),
                'TfAccounts::'.$method.'() does not exist. The tenant root has no `account_id` column and '
                .'therefore no constraint to hang a relation on, which is why it needs this one declared.',
            );

            self::assertGreaterThan(
                0,
                $root->{$method}()->count(),
                'TfAccounts::'.$method.'() returned nothing although this fixture planted rows in '.$table
                .' under this tenant. A tenant root that scopes a query to zero rows is worse than one '
                .'that raises.',
            );
        }
    }

    // =======================================================================
    // 4. UNMAPPED IS LOUD — the three answers stay apart.
    // =======================================================================

    /**
     * `peer_type = 0` is a REAL stored value — "Telegram did not send a peer" —
     * and it answers `null`. That is the ONE case where null is correct.
     */
    public function test_an_absent_peer_is_null_and_not_an_error(): void
    {
        $dialog = new TfDialog;
        $dialog->setRawAttributes(['account_id' => self::ACCOUNT, 'peer_type' => 0, 'peer_id' => 0], true);

        self::assertNull(
            $dialog->peer(),
            'peer_type 0 no longer resolves to null. It is a real stored value, not a placeholder — '
            .'2026_10_04_000300 says so — and it must not raise.',
        );
    }

    /**
     * A discriminator OUTSIDE the enum RAISES.
     *
     * This is the load-bearing assertion of the whole slice. The alternative —
     * returning an empty relation — is indistinguishable from "no rows", so a
     * host would read the miss as "Telegram never sent this peer" and be
     * confidently wrong.
     */
    public function test_an_unmapped_peer_kind_raises_rather_than_returning_an_empty_relation(): void
    {
        $dialog = new TfDialog;
        $dialog->setRawAttributes(['account_id' => self::ACCOUNT, 'peer_type' => 9, 'peer_id' => 42], true);

        $this->expectException(UnmappedEntityPeer::class);
        $this->expectExceptionMessage('tf_dialogs.peer_type holds 9');

        $dialog->peer();
    }

    /**
     * A MISSING discriminator column raises too, and this is the subtle one.
     *
     * `getAttribute()` returns `null` for an attribute the row does not carry,
     * `(int) null` is `0`, and `0` means "no peer". So a renamed or dropped
     * column would silently turn every peer on the table into "no peer" — the
     * exact confusion, wearing the costume of a legitimate answer.
     */
    public function test_a_missing_discriminator_column_raises_rather_than_reading_as_absent(): void
    {
        $dialog = new TfDialog;
        $dialog->setRawAttributes(['account_id' => self::ACCOUNT, 'peer_id' => 42], true);

        $this->expectException(UnmappedEntityPeer::class);
        $this->expectExceptionMessage('does not exist on the row');

        $dialog->peer();
    }

    /**
     * THE HAND-MAINTAINED SURFACE IS MEASURED, NOT ASSUMED.
     *
     * `TfUser` / `TfChat` / `TfChannel` are a second set of classes over the
     * same three tables as the generated anchors, and they carry the MANDATE
     * subset rather than the complete derived set. A thinner surface nobody
     * measures is how a host concludes a relation does not exist — so the
     * subset is asserted, and so is the fact that the generated anchors are the
     * complete surface.
     */
    public function test_the_generated_anchor_is_the_complete_surface_and_the_hand_model_is_the_mandate_subset(): void
    {
        $this->plantSharedIdPeers();

        $pairs = $this->horizontal()->peerPairs();

        $derivedPerDomain = ['users' => 0, 'chats' => 0, 'channels' => 0];

        foreach ($pairs as $child => $bases) {
            foreach ($bases as $base) {
                $forward = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $base))));
                $suffix = ucfirst(ModelGenerator::studlyStem($child));

                foreach ($this->horizontal()->kindsFor($base) as $domain) {
                    if ($this->reverseMethodOn($domain, $forward.$suffix) !== null) {
                        $derivedPerDomain[$domain]++;
                    }
                }
            }
        }

        foreach ($derivedPerDomain as $domain => $count) {
            self::assertGreaterThan(
                0,
                $count,
                'the derivation produced no reverse relation for the '.$domain.' anchor. The census test '
                .'above reads the same source, so an empty result here means the two disagree.',
            );
        }

        // THE SPLIT IS THE POINT, so it is stated rather than left for a reader
        // to discover: the generated anchors are complete, the hand models are
        // the mandate subset, and the difference is asserted to EXIST so that
        // closing it (or widening the hand layer) is a visible change.
        $hand = $this->sharedPeer('users');
        $anchor = $this->entityModel('users');

        $handRelations = self::countHorizontalRelationsOn($hand::class);
        $anchorRelations = self::countHorizontalRelationsOn($anchor);

        self::assertGreaterThan(
            $handRelations,
            $anchorRelations,
            'the generated anchor no longer carries MORE horizontal relations than the hand-maintained '
            .'model. If the two have converged, this split — and the paragraph in AGENTS.md that records '
            .'it — is stale and should be rewritten rather than left asserting a difference that is no '
            .'longer there.',
        );

        // The mandate subset itself: the two edges this slice names by hand.
        foreach (['peerDialogs', 'userIdUpdates'] as $method) {
            self::assertTrue(
                method_exists($hand, $method),
                'TfUser::'.$method.'() is missing. The hand-maintained layer carries the mandate subset '
                .'and the generated anchors carry the complete set; neither may quietly lose one, because '
                .'a host reaching for a missing accessor reads an empty relation as "no rows".',
            );
        }
    }

    /**
     * How many relation methods this class declares, on the relation types the
     * horizontal half uses.
     *
     * Read by REFLECTION over the class, never over a transcribed count, so the
     * number in the assertion above is measured from the same code the host
     * will call.
     *
     * @param  class-string  $class
     */
    private static function countHorizontalRelationsOn(string $class): int
    {
        $types = [
            HasMany::class,
            BelongsTo::class,
            HasOne::class,
        ];

        $count = 0;
        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $return = $method->getReturnType();
            if ($return instanceof ReflectionNamedType && in_array($return->getName(), $types, true)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * The planted row, typed as the CONCRETE hand model for the domain.
     *
     * Branches on the domain rather than resolving the class from a string, so
     * the union `$this->peerDialogs()` below is one PHPStan and the reader can
     * both see.
     */
    private function sharedPeer(string $domain): TfUser|TfChat|TfChannel
    {
        $peer = match ($domain) {
            'users' => TfUser::query()->find(self::SHARED_ID),
            'chats' => TfChat::query()->find(self::SHARED_ID),
            'channels' => TfChannel::query()->find(self::SHARED_ID),
            default => null,
        };

        self::assertNotNull($peer, 'no '.$domain.' row was planted under the shared id '.self::SHARED_ID);

        return $peer;
    }

    // =======================================================================
    // Harness. Nothing below transcribes a table shape.
    // =======================================================================

    /** @return array<string,list<string>> table => method names, from the executed derivation */
    private function derivedRelations(): array
    {
        $this->bootOnce();

        return $this->derived;
    }

    private HorizontalEntityRelations $horizontalResolver;

    private function horizontal(): HorizontalEntityRelations
    {
        $this->bootOnce();

        return $this->horizontalResolver;
    }

    private MirrorShape $mirrorShape;

    private MirrorCatalog $mirrorCatalog;

    /** @var array<string,list<string>> */
    private array $derived = [];

    private function bootOnce(): void
    {
        if (isset($this->horizontalResolver)) {
            return;
        }

        $this->mirrorShape = MirrorShape::load(MirrorShape::artifactPath());
        $this->mirrorCatalog = MirrorCatalog::load(
            SchemaArtifacts::path('mirror-catalog.json'),
            TlParser::parseFile(SchemaArtifacts::source('api_full.tl')),
        );

        $this->horizontalResolver = new HorizontalEntityRelations($this->mirrorShape, $this->mirrorCatalog);

        $classFor = [];
        foreach ($this->mirrorShape->tableNames() as $table) {
            $classFor[$table] = ModelGenerator::tableModelClass($table);
        }
        foreach (Naming::domainModelNames() as $domain) {
            $table = Naming::domainTable($domain);
            if (isset($classFor[$table])) {
                $classFor[$table] = Naming::domainModel($domain);
            }
        }

        foreach ($this->horizontalResolver->relationsFor($classFor) as $table => $list) {
            foreach ($list as $relation) {
                $this->derived[$table][] = $relation['method'];
            }
        }
    }

    private function catalog(): MirrorCatalog
    {
        $this->bootOnce();

        return $this->mirrorCatalog;
    }

    private function catalogDeclares(MirrorCatalog $catalog, string $table, string $column): bool
    {
        if (! $catalog->has($table)) {
            return false;
        }

        $entry = $catalog->table($table);
        foreach ([$entry->base, $entry->children] as $list) {
            foreach ($list as $field) {
                if ($field[0] === $column) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The GENERATED model class for a table, anchors included.
     *
     * The anchor rule is read from `Naming` for the same reason the generator
     * reads it from `Naming`: `tf_dialogs` is `TlDialog` and `tf_messages_from_id`
     * is `TfMessagesFromId`, and a lookup that applied only the second rule would
     * report all eleven domain roots as "no generated model class".
     *
     * @return class-string<Model>|null
     */
    private function modelFor(string $table): ?string
    {
        foreach (Naming::domainModelNames() as $domain) {
            if (Naming::domainTable($domain) !== $table) {
                continue;
            }

            $anchor = ModelGenerator::modelFqcn(Naming::domainModel($domain));

            return class_exists($anchor) ? $anchor : null;
        }

        $fqcn = ModelGenerator::modelFqcn(ModelGenerator::tableModelClass($table));

        return class_exists($fqcn) ? $fqcn : null;
    }

    /** @return class-string<Model> */
    private function entityModel(string $domain): string
    {
        return ModelGenerator::modelFqcn(Naming::domainModel($domain));
    }

    /**
     * A model instance carrying EVERY column the executed schema declares, so a
     * relation that reads an attribute for a leg cannot read `null` merely
     * because the fixture did not set it.
     */
    private function hydrated(string $model): Model
    {
        /** @var Model $instance */
        $instance = new $model;
        $instance->setRawAttributes($this->rowFor($instance->getTable()), true);

        return $instance;
    }

    /** @return array<string,int> */
    private function rowFor(string $table): array
    {
        $row = [];
        foreach ($this->columnsOf($table) as $column) {
            $row[$column] = 1;
        }

        return $row;
    }

    /**
     * The legs a built relation constrains, as `[column, table]`.
     *
     * Read off the relation's OWN query, because that is where the peer legs
     * live — `ResolvesMessagePeerScope` says so in its own docblock, and the
     * same is true of the tenant pin `PinsDeclaredForeignKeyLegs` adds. A leg
     * that is not in the query is not constraining anything.
     *
     * @return list<array{0:string,1:string}>
     */
    private function legsOf(Relation $relation): array
    {
        $related = $relation->getRelated()->getTable();
        $parent = $relation->getParent()->getTable();
        $legs = [];

        // THE RELATION'S OWN KEY PAIR — the half that is NOT in `wheres`.
        //
        // `BelongsTo` puts the owner-key predicate in the query but keeps the
        // FOREIGN KEY in `getForeignKeyName()`, so a scan that reads only
        // `wheres` sees none of the child's own column. That is precisely the
        // column a rename breaks, and it is why this scan has to read both
        // halves or the control half cannot fire.
        if ($relation instanceof BelongsTo) {
            $legs[] = [self::bare($relation->getForeignKeyName()), $parent];
            $legs[] = [self::bare($relation->getOwnerKeyName()), $related];
        }

        if ($relation instanceof HasOneOrMany) {
            $legs[] = [self::bare($relation->getForeignKeyName()), $related];
            $legs[] = [self::bare($relation->getLocalKeyName()), $parent];
        }

        foreach ($relation->getBaseQuery()->wheres as $where) {
            $column = (string) ($where['column'] ?? '');
            if ($column === '') {
                continue;
            }

            if (str_contains($column, '.')) {
                $legs[] = [substr($column, (int) strrpos($column, '.') + 1), substr($column, 0, (int) strrpos($column, '.'))];

                continue;
            }

            // An unqualified leg is written against the table the relation
            // queries — which is the related table for all three relation kinds
            // here, since the pin and the discriminator are both written bare.
            $legs[] = [$column, $related];
        }

        return $legs;
    }

    /** The column with any `table.` qualifier removed. */
    private static function bare(string $column): string
    {
        return str_contains($column, '.')
            ? substr($column, (int) strrpos($column, '.') + 1)
            : $column;
    }

    /**
     * @return list<string> human-readable descriptions of the missing legs
     */
    private function missingLegsFor(Model $instance, string $method): array
    {
        $relation = $instance->{$method}();
        if ($relation === null) {
            return ['peer() returned null, so no leg could be read at all'];
        }

        $missing = [];
        foreach ($this->legsOf($relation) as [$column, $against]) {
            if (! $this->hasColumn($against, $column)) {
                $missing[] = $against.'.'.$column;
            }
        }

        return $missing;
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columnsOf($table), true);
    }

    /** @return list<string> */
    private function columnsOf(string $table): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $columns = [];
        foreach (Schema::getColumns($table) as $definition) {
            $columns[] = (string) $definition['name'];
        }

        return $columns;
    }

    private function relationCount(array $relations): int
    {
        $total = 0;
        foreach ($relations as $methods) {
            $total += count($methods);
        }

        return $total;
    }

    private function reverseMethodOn(string $domain, string $method): ?string
    {
        $class = $this->entityModel($domain);

        return method_exists($class, $method) ? $method : null;
    }

    // -----------------------------------------------------------------------
    // Fixtures. Every id is synthetic; no session string is written.
    // -----------------------------------------------------------------------

    /** Plant the SAME id in all three identity tables, which is the decoy. */
    private function plantSharedIdPeers(): void
    {
        $this->seedIngestTenant(self::ACCOUNT);

        DB::table('tf_users')->insert([
            'account_id' => self::ACCOUNT,
            'id' => self::SHARED_ID,
            'constructor' => 'user',
        ]);

        DB::table('tf_chats')->insert([
            'account_id' => self::ACCOUNT,
            'id' => self::SHARED_ID,
            'constructor' => 'chat',
        ]);

        DB::table('tf_channels')->insert([
            'account_id' => self::ACCOUNT,
            'id' => self::SHARED_ID,
            'constructor' => 'channel',
        ]);
    }

    private function plantDialogs(): void
    {
        foreach ([PeerShapeTool::PEER_USER, PeerShapeTool::PEER_CHAT, PeerShapeTool::PEER_CHANNEL] as $kind) {
            $ctor = 'dialog';
            DB::table('tf_dialogs')->insert([
                'account_id' => self::ACCOUNT,
                'peer_type' => $kind,
                'peer_id' => self::SHARED_ID,
                'constructor' => $ctor,
                'top_message' => 0,
                'read_inbox_max_id' => 0,
                'read_outbox_max_id' => 0,
                'unread_count' => 0,
                'unread_mentions_count' => 0,
                'unread_reactions_count' => 0,
                'unread_poll_votes_count' => 0,
                'unread_muted_peers_count' => 0,
                'unread_unmuted_peers_count' => 0,
                'unread_muted_messages_count' => 0,
                'unread_unmuted_messages_count' => 0,
            ]);
        }
    }

    private function plantUpdates(): void
    {
        // ONE row naming all three, so each relation is exercised on the same
        // row and no leg can pass by reading a different row's value.
        DB::table('tf_updates')->insert([
            'account_id' => self::ACCOUNT,
            'seq' => 1,
            'position' => 0,
            'constructor' => 'updateChannelParticipant',
            'user_id' => self::SHARED_ID,
            'chat_id' => self::SHARED_ID,
            'channel_id' => self::SHARED_ID,
        ]);
    }

    private function plantTenantRoot(): void
    {
        DB::table('tf_accounts')->insert([
            'id' => self::ACCOUNT,
            'label' => 'horizontal-relation-fixture',
            'dc_id' => 2,
            'port' => null,
            'kind' => 'account',
            'session_ref' => 'fixture',
            'enabled' => true,
        ]);
    }

    private function dialogOfKind(int $kind): TfDialog
    {
        $dialog = TfDialog::query()
            ->where('account_id', self::ACCOUNT)
            ->where('peer_type', $kind)
            ->first();

        self::assertNotNull($dialog, 'no tf_dialogs row with peer_type '.$kind.' was planted');

        return $dialog;
    }
}
