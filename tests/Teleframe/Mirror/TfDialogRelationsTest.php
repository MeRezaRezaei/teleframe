<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Teleframe\Mirror;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Mirror\Models\TfDialog;
use MeRezaRezaei\Teleframe\Mirror\Models\TfDialogsDraft;
use MeRezaRezaei\Teleframe\Mirror\Models\TfDialogsFolder;
use MeRezaRezaei\Teleframe\Mirror\Models\TfDialogsFolderId;
use MeRezaRezaei\Teleframe\Mirror\Models\TfDialogsNotifySettings;
use MeRezaRezaei\Teleframe\Mirror\Models\TfDialogsPts;
use MeRezaRezaei\Teleframe\Mirror\Models\TfDialogsTtlPeriod;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * `TfDialog`'s six `tf_dialogs_*` accessors return an Eloquent `Builder`, not
 * a `Relation`. This file PINS that, and pins — measured, not preferred — why
 * they may not be converted to `HasOne`.
 *
 * ## WHAT THE ACCESSORS ARE
 *
 * `notifySettings()`, `pts()`, `draft()`, `folderId()`, `ttlPeriod()` and
 * `folder()` each return `Builder` filtered on the SAME THREE columns the
 * parent row is keyed by: `(account_id, peer_type, peer_id)`. The supported
 * read is the METHOD CALL — `$dialog->pts()->sole()`. Every test below builds
 * two dialogs of ONE account with different `peer_id` and different child
 * values, so a filter that dropped any one leg would read the other dialog's
 * row and fail.
 *
 * ## THE PROPERTY READ THROWS — IT IS NOT A SILENT NULL
 *
 * `$dialog->pts` raises `LogicException: …TfDialog::pts must return a
 * relationship instance.` (`HasAttributes::getRelationshipFromMethod`).
 * The accessor is reached, its return value is rejected for not being a
 * `Relation`, and the read stops. Nothing is logged, nothing is returned
 * wrong, and no caller can mistake it for absent data.
 *
 * `Model::isRelation()` is TRUE for all six — it asks only whether a method of
 * that NAME exists — so `isRelation()` is NOT an oracle for "safe to read as a
 * property". Pinned so the next reader does not reason from it.
 *
 * The two tests that measure this call `getAttribute()` rather than writing
 * `$dialog->pts`. Those are the same read: `Model::__get()` is exactly
 * `return $this->getAttribute($key);` (Model.php:2747). The magic fetch is
 * written through the door the analyser can type, because `$dialog->pts` as
 * source is a statically undefined property and annotating that away would be a
 * suppression where a measurement was asked for.
 *
 * ## WHY A `HasOne` CONVERSION IS REFUSED (two independent legs)
 *
 *  1. **A one-column `hasOne` is AMBIGUOUS, and it says so.** `tf_dialogs` and
 *     every child are keyed by the same three columns, so no single column
 *     identifies a child row: `account_id` is shared by EVERY dialog of the
 *     tenant. `hasOne(…, 'account_id', 'account_id')->sole()` raises
 *     `MultipleRecordsFoundException: 2 records were found.` — the loud,
 *     correct failure, because the relation genuinely cannot say which of the
 *     two rows is this dialog's.
 *  2. **The composite key cannot be handed to `hasOne` at all.** The array
 *     form raises `TypeError`. See
 *     `PinsDeclaredForeignKeyLegs`' docblock for the recorded decision.
 *
 * Loud beats silent here in BOTH directions: a property read that throws says
 * "call the method", and a `hasOne` that throws says "you keyed this on one
 * column of a composite key". Neither can answer a question with another
 * dialog's facts.
 *
 * ## FIXTURE DISCIPLINE
 *
 * Child rows are written THROUGH the accessor under test with all three key
 * columns set explicitly. Dialogs are re-read with an explicit composite
 * `where()` chain, never `fresh()` / `find()` — the shipped `$primaryKey` is
 * `account_id`, which is not a candidate key, and `test_fresh_…` pins what that
 * costs.
 *
 * The schema is the SHIPPED migration's own `up()`, so no column name here is
 * transcribed: if the dial changes, this file measures the change.
 */
final class TfDialogRelationsTest extends TestCase
{
    private const ACCOUNT = 4242;

    /** Telegram's `peer_user`; shared by both dialogs so only `peer_id` varies. */
    private const PEER_TYPE = 1;

    /**
     * The six accessors, as `[accessor => [child model, distinguishing column, constructor ctor]]`.
     *
     * `constructor` is non-null only for the three children whose
     * `constructor` column is NOT NULL with no default. The distinguishing
     * column is chosen per child to be a real, non-default value column, so a
     * wrong-row read cannot accidentally agree with the right one.
     *
     * @var array<string, array{class-string<Model>, string, string|null}>
     */
    private const ACCESSORS = [
        'notifySettings' => [TfDialogsNotifySettings::class, 'mute_until', 'peerNotifySettings'],
        'pts' => [TfDialogsPts::class, 'pts', null],
        'draft' => [TfDialogsDraft::class, 'date', 'draftMessage'],
        'folderId' => [TfDialogsFolderId::class, 'folder_id', null],
        'ttlPeriod' => [TfDialogsTtlPeriod::class, 'ttl_period', null],
        'folder' => [TfDialogsFolder::class, 'id', 'folder'],
    ];

    /** @var array<string, int> */
    private const FIRST_VALUES = [
        'notifySettings' => 1410,
        'pts' => 1400,
        'draft' => 1401,
        'folderId' => 1102,
        'ttlPeriod' => 1303,
        'folder' => 1404,
    ];

    /** @var array<string, int> */
    private const SECOND_VALUES = [
        'notifySettings' => 2410,
        'pts' => 2400,
        'draft' => 2401,
        'folderId' => 2202,
        'ttlPeriod' => 2303,
        'folder' => 2404,
    ];

    protected function getPackageProviders($app): array
    {
        return [];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'tf_test');
        $app['config']->set('database.connections.tf_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $migration = require dirname(__DIR__, 3).'/src/Laravel/Migrations/2026_09_14_200004_create_tf_dialogs_tables.php';
        self::assertIsObject($migration);
        self::assertTrue(method_exists($migration, 'up'), 'shipped dialogs migration exposes up()');
        $migration->up();
    }

    // -----------------------------------------------------------------------
    // The supported path: a Builder whose WHERE carries all three key legs.
    // -----------------------------------------------------------------------

    /**
     * THE CONTRACT. Each of the six returns a `Builder` — never a `Relation` —
     * whose query constrains `account_id`, `peer_type` AND `peer_id` to the
     * parent row's own values. Asserted on the SQL and the bindings so the
     * three legs are checked by name and by value.
     */
    public function test_each_accessor_returns_a_builder_constrained_on_the_three_column_key(): void
    {
        [$first, $second] = $this->twoDialogsOneTenant();

        foreach ([$first, $second] as $dialog) {
            foreach (self::ACCESSORS as $accessor => [$model, $column, $constructor]) {
                $builder = $dialog->{$accessor}();

                self::assertInstanceOf(Builder::class, $builder, "TfDialog::{$accessor}() must return a Builder");

                // The SIGNATURE is the stronger claim, and it subsumes "never a
                // Relation": the declared return type is `Builder`, and
                // `Builder` is not `Relation`.
                $returnType = (new \ReflectionMethod($dialog, $accessor))->getReturnType();
                self::assertInstanceOf(\ReflectionNamedType::class, $returnType);
                self::assertSame(
                    Builder::class,
                    $returnType->getName(),
                    "TfDialog::{$accessor}() must be declared to return a Builder, never a Relation",
                );

                $sql = $builder->toSql();
                foreach (['account_id', 'peer_type', 'peer_id'] as $leg) {
                    self::assertStringContainsString($leg, $sql, "TfDialog::{$accessor}() must filter on {$leg}");
                }

                self::assertSame(
                    [self::ACCOUNT, self::PEER_TYPE, (int) $dialog->peer_id],
                    $builder->getBindings(),
                    "TfDialog::{$accessor}() must bind this dialog's own three key columns",
                );
            }
        }
    }

    /**
     * THE CONTRACT, OBSERVED. Every accessor, on both dialogs of the tenant,
     * resolves to exactly one row — and it is that dialog's row, carrying that
     * dialog's value. A dropped leg gives two rows (fails at `sole()`); a
     * crossed leg gives the sibling dialog's value (fails on the assertion).
     */
    public function test_each_accessor_reads_only_its_own_dialogs_row(): void
    {
        [$first, $second] = $this->twoDialogsOneTenant();

        foreach (self::ACCESSORS as $accessor => [$model, $column, $constructor]) {
            $firstRow = $first->{$accessor}()->sole();
            $secondRow = $second->{$accessor}()->sole();

            self::assertInstanceOf($model, $firstRow);
            self::assertInstanceOf($model, $secondRow);

            self::assertSame(
                111,
                (int) $firstRow->getAttribute('peer_id'),
                "TfDialog::{$accessor}() crossed to the other dialog",
            );
            self::assertSame(
                222,
                (int) $secondRow->getAttribute('peer_id'),
                "TfDialog::{$accessor}() crossed to the other dialog",
            );

            self::assertSame(
                self::FIRST_VALUES[$accessor],
                (int) $firstRow->getAttribute($column),
                "TfDialog::{$accessor}() read the wrong {$column}",
            );
            self::assertSame(
                self::SECOND_VALUES[$accessor],
                (int) $secondRow->getAttribute($column),
                "TfDialog::{$accessor}() read the wrong {$column}",
            );
        }
    }

    /**
     * The tenant leg specifically: `peer_type` and `peer_id` are held EQUAL
     * across two accounts, so only `account_id` can separate them. Without that
     * third leg both dialogs would see both rows and `sole()` would raise.
     */
    public function test_the_account_leg_is_not_dropped_when_two_accounts_share_a_peer(): void
    {
        $mine = $this->seedDialog(self::ACCOUNT, 111, self::FIRST_VALUES);
        $theirs = $this->seedDialog(self::ACCOUNT + 1, 111, self::SECOND_VALUES);

        self::assertSame(self::FIRST_VALUES['pts'], (int) $mine->pts()->sole()->getAttribute('pts'));
        self::assertSame(self::SECOND_VALUES['pts'], (int) $theirs->pts()->sole()->getAttribute('pts'));
        self::assertSame(111, (int) $mine->pts()->sole()->getAttribute('peer_id'));
        self::assertSame(111, (int) $theirs->pts()->sole()->getAttribute('peer_id'));
    }

    // -----------------------------------------------------------------------
    // The property read: LOUD.
    // -----------------------------------------------------------------------

    /**
     * THE TRAP, and it is loud. The row exists — `sole()` reads it — and the
     * property read of the same fact nonetheless raises `LogicException` whose
     * message names `TfDialog::pts` and says what is wrong with it.
     */
    public function test_a_property_read_of_pts_throws_a_logic_exception_naming_the_accessor(): void
    {
        [$dialog] = $this->twoDialogsOneTenant();

        // The row is there, proven by the path that IS supported.
        self::assertSame(self::FIRST_VALUES['pts'], (int) $dialog->pts()->sole()->getAttribute('pts'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('TfDialog::pts must return a relationship instance');

        // `Model::__get()` IS `getAttribute()` — `return $this->getAttribute($key);`
        // at Model.php:2747 — so this call IS the property read `$dialog->pts`,
        // taken through the door the analyser can type. Written as the magic
        // fetch it is statically an undefined property, which would be a
        // suppression rather than a measurement.
        $dialog->getAttribute('pts');
    }

    /**
     * The six accessors, one case each.
     *
     * @return iterable<string, array{string}>
     */
    public static function accessorProvider(): iterable
    {
        foreach (array_keys(self::ACCESSORS) as $accessor) {
            yield $accessor => [$accessor];
        }
    }

    /**
     * THE TRAP, for all six, plus the reason it cannot be reasoned away.
     *
     * `Model::isRelation()` returns TRUE for every accessor — it asks only
     * whether a method of that NAME exists, which `method_exists()` answers
     * yes to (`HasAttributes::isRelation`, line 596). So `isRelation()` tells
     * you NOTHING about whether a property read is safe: the return VALUE is
     * what Eloquent rejects, and only after the method has already been called.
     */
    #[DataProvider('accessorProvider')]
    public function test_every_accessor_property_read_raises_although_is_relation_says_yes(string $accessor): void
    {
        [$dialog] = $this->twoDialogsOneTenant();

        self::assertTrue(
            $dialog->isRelation($accessor),
            "isRelation('{$accessor}') is a method-name check, not a return-type check",
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('TfDialog::'.$accessor.' must return a relationship instance');

        $dialog->getAttribute($accessor);
    }

    // -----------------------------------------------------------------------
    // WHY A HasOne CONVERSION IS REFUSED — leg one: ambiguity, stated loudly.
    // -----------------------------------------------------------------------

    /**
     * WHY THE CONVERSION IS REFUSED, leg one.
     *
     * `tf_dialogs` and every `tf_dialogs_*` child share the three-column key,
     * so no single column identifies a child row. A one-column `hasOne` on
     * `account_id` matches EVERY dialog of the tenant: `get()` proves both rows
     * are in scope, and `sole()` then raises `MultipleRecordsFoundException`
     * rather than choosing. That exception IS the argument — the relation
     * cannot know which of the two rows is this dialog's, and it refuses to
     * pretend otherwise.
     */
    public function test_a_one_column_has_one_is_ambiguous_across_the_tenant(): void
    {
        [, $second] = $this->twoDialogsOneTenant();

        $naive = $second->hasOne(TfDialogsPts::class, 'account_id', 'account_id');
        self::assertInstanceOf(HasOne::class, $naive);

        $peerIds = [];
        foreach ($naive->get() as $row) {
            $peerIds[] = (int) $row->peer_id;
        }
        sort($peerIds);

        self::assertSame(
            [111, 222],
            $peerIds,
            'account_id alone must match every dialog of the tenant — that is the ambiguity',
        );

        $this->expectException(MultipleRecordsFoundException::class);
        $this->expectExceptionMessage('2 records were found');

        $naive->sole();
    }

    /**
     * WHY THE CONVERSION IS REFUSED, leg two.
     *
     * The key that WOULD be correct is the three-column composite, and
     * `hasOne()` cannot be handed it: the signature takes `$foreignKey` and
     * `$localKey` as strings, so the array form raises `TypeError` —
     * `str_contains(): Argument #1 must be of type string, array given`, the
     * same raise `PinsDeclaredForeignKeyLegs` records in its docblock.
     *
     * The call below is a deliberate type violation; the raise IS the
     * assertion, so the two argument-type errors it provokes are annotated
     * rather than "fixed" — fixing them would remove the measurement.
     */
    public function test_the_composite_key_cannot_be_handed_to_has_one(): void
    {
        [$dialog] = $this->twoDialogsOneTenant();

        $composite = ['account_id', 'peer_type', 'peer_id'];

        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line 2 */
        $dialog->hasOne(TfDialogsPts::class, $composite, $composite);
    }

    // -----------------------------------------------------------------------
    // The schema both refusals rest on, read off the EXECUTED migration.
    // -----------------------------------------------------------------------

    /**
     * Parent and all six children are keyed by the SAME ordered triple. This
     * is the fact underneath both refusals: no single column identifies a
     * child row, which is why the accessors must be Builders over three
     * constraints and not a `hasOne` over one.
     *
     * Read back out of sqlite's own `PRAGMA table_info`, not transcribed.
     */
    public function test_parent_and_every_child_share_the_three_column_primary_key(): void
    {
        $expected = ['account_id', 'peer_type', 'peer_id'];

        $tables = ['tf_dialogs'];
        foreach (self::ACCESSORS as [$model, $column, $constructor]) {
            $tables[] = (new $model)->getTable();
        }

        self::assertCount(7, $tables, 'the parent plus six children — no table goes unchecked');

        foreach ($tables as $table) {
            self::assertSame(
                $expected,
                $this->primaryKeyOf($table),
                "{$table} must be keyed by (account_id, peer_type, peer_id), in that order",
            );
        }
    }

    /**
     * WHY THIS FILE RE-QUERIES INSTEAD OF CALLING `fresh()`.
     *
     * The shipped `TfDialog::$primaryKey` is `account_id`, which is not a
     * candidate key — every dialog of the tenant has that value. So `fresh()`
     * and `find()` re-read by `account_id` alone and hand back the tenant's
     * FIRST row, whichever dialog you asked about. That is silent: no
     * exception, just the wrong peer. It is the reason every fixture here
     * re-reads through an explicit composite `where()` chain, and the reason a
     * key-based `hasOne` was never viable.
     */
    public function test_fresh_is_keyed_on_account_id_alone_and_returns_the_tenants_first_dialog(): void
    {
        [$first, $second] = $this->twoDialogsOneTenant();

        self::assertSame(111, (int) $first->peer_id);
        self::assertSame(222, (int) $second->peer_id);

        $reloaded = $second->fresh();
        self::assertInstanceOf(TfDialog::class, $reloaded);
        self::assertSame(111, (int) $reloaded->peer_id, '$primaryKey is account_id, so fresh() cannot address one dialog');
        self::assertSame(4242, (int) $reloaded->account_id);
    }

    // -----------------------------------------------------------------------
    // Fixture.
    // -----------------------------------------------------------------------

    /**
     * Two dialogs of the SAME account, each carrying all six child rows.
     *
     * Two accounts alone would prove tenant isolation; two dialogs of one
     * tenant is the sharper case, because `account_id` alone cannot tell them
     * apart either. Tenant isolation across accounts is covered separately.
     *
     * @return array{TfDialog, TfDialog}
     */
    private function twoDialogsOneTenant(): array
    {
        return [
            $this->seedDialog(self::ACCOUNT, 111, self::FIRST_VALUES),
            $this->seedDialog(self::ACCOUNT, 222, self::SECOND_VALUES),
        ];
    }

    /**
     * One dialog plus all six children, read back from sqlite through the
     * explicit composite chain.
     *
     * Child rows are written THROUGH the accessor under test, with the three
     * key columns set explicitly on every row — the accessor supplies the
     * WHERE, this supplies the VALUES, so neither half can hide a mistake in
     * the other.
     *
     * @param  array<string, int>  $values  distinguishing value per accessor name
     */
    private function seedDialog(int $accountId, int $peerId, array $values): TfDialog
    {
        $dialog = new TfDialog([
            'account_id' => $accountId,
            'peer_type' => self::PEER_TYPE,
            'peer_id' => $peerId,
            'constructor' => 'dialog',
        ]);
        $dialog->save();

        foreach (self::ACCESSORS as $accessor => [$model, $column, $ctor]) {
            $row = [
                'account_id' => $accountId,
                'peer_type' => self::PEER_TYPE,
                'peer_id' => $peerId,
                $column => $values[$accessor],
            ];

            if ($ctor !== null) {
                $row['constructor'] = $ctor;
            }

            $dialog->{$accessor}()->create($row);
        }

        return $this->reloadDialog($accountId, $peerId);
    }

    /**
     * Re-read by the composite key — never `find()` / `fresh()`, which are
     * keyed on `account_id` alone (see `test_fresh_…`).
     */
    private function reloadDialog(int $accountId, int $peerId): TfDialog
    {
        $dialog = TfDialog::query()
            ->where('account_id', $accountId)
            ->where('peer_type', self::PEER_TYPE)
            ->where('peer_id', $peerId)
            ->first();

        self::assertInstanceOf(TfDialog::class, $dialog, "dialog ({$accountId}, {$peerId}) was not persisted");

        return $dialog;
    }

    /**
     * The composite primary key, read back off sqlite's own
     * `PRAGMA table_info`: `pk` is the 1-based POSITION within the key, so
     * the order is sqlite's, not this file's.
     *
     * @return list<string>
     */
    private function primaryKeyOf(string $table): array
    {
        $byPosition = [];

        foreach (Schema::getConnection()->select('PRAGMA table_info('.$table.')') as $row) {
            $values = (array) $row;
            $position = (int) $values['pk'];

            if ($position > 0) {
                $byPosition[$position] = (string) $values['name'];
            }
        }

        ksort($byPosition);

        return array_values($byPosition);
    }
}
