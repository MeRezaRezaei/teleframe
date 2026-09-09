# Telegram ID Mirror + Generated Relations + Account Isolation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Status: ✅ COMPLETE** — Tasks 1.1–1.5, 2.1–2.4, 3.1–3.3 all shipped and gate-green (`composer verify`, smoke, `TELEFRAME_PG=1`): Peer/InputPeer refs are canonical int64 longs (`PeerIdTool`), instance+child tables carry denormalized `account_id` with per-account `(account_id, peer, tl_id)` uniques, every generated model boots `AccountScope` (+`PeerResolution` on peer models), generated `belongsTo`/reverse-`hasMany`/vector `hasMany` relations are in place, index names are content-addressed (PG-truncation-collision-proof), `UpdateIngestor` writes tenant + canonical peer columns, and cross-account queries return shared peers across accounts. Implemented with parallel subagents (A: migrations, B: model generator, C: isolation machinery) plus the coordinator's direct reverse-`hasMany` fix.

**Goal:** Mirror Telegram's real ID engineering (int64 ids, canonical peer longs) through the generated schema→migration→model pipeline, emit real Eloquent relations for every schema type reference, index every id/ref column, and give every generated model per-account isolation with opt-in cross-account queries — all regenerable from the TL mirror in seconds on a schema bump.

**Architecture:** Extend the existing deterministic regeneration pipeline (TlParser → MigrationGenerator/ModelGenerator/DtoGenerator). Three decisions carry the whole design: (1) ID fidelity — scalar `long`/`int` id fields already map to bigInteger/integer; the two wrong cases are `Peer`/`InputPeer`-typed refs (from_id, peer_id, saved_peer_id, guestchat_via_from…) which must become **int64 canonical peer longs**, and object refs which stay uuid-anchor FKs; (2) relations — every object-ref param gets a generated `belongsTo`, every vector a typed `hasMany` (already exists), Peer refs get generated **peer scopes + a `peerModel()` resolver** via `PeerIdTool`; (3) tenancy — `account_id` is denormalized onto every generated table (anchor, instance, child), a global `AccountScope` isolates by context by default, and uniqueness keys are `(account_id, canonical telegram id)` so the same update collapses to one row per account (never duplicates) and cross-account queries reveal which accounts share a peer.

**Tech Stack:** PHP 8.4, Laravel Eloquent, MySQL/SQLite/PostgreSQL (migrations + PG golden), PHPStan level 5, existing regenerator (`php bin/regenerate`), PHPUnit. No new runtime dependencies.

**Spec:** This plan implements the enhancement request on top of `docs/superpowers/specs/2026-09-07-teleframe-unification-design.md` (spec §4 class-table-inheritance mirror, §6.2 lazy vector access) and refines the "account_id on every anchor; no global singletons by telegram id" roadmap contract with a canonical-int64 peer dimension (longs are indexable tenant-neutral identifiers — not rows, so tenancy is preserved).

## Global Constraints

- PHP immutable code style; `final class` on generated classes is fine; one class per file.
- Zero-regex rule: `preg_*` is forbidden in `src/Teleframe/` and `src/Schema/`; phpstan `disallowedFunctionCalls` enforces (allow-list: `src/Laravel/*`, `src/Schema/*`, `src/Bot/*`). New code must be regex-free.
- PHPStan level 5 clean is part of `composer verify`; every task ends with the gate green.
- Regeneration is deterministic: `php bin/regenerate` must reproduce `generated/` and `schema-manifest.json` byte-identically; goldens (`tests/Schema/RegenerationGoldenTest`, `ShipDialGoldenTest`) gate it.
- Migrations use the deterministic filename grammar (DATE_TOKEN + 6-digit seq + snake name); cross-type FKs stay DEFERRABLE INITIALLY DEFERRED, bucketed `FK_BUCKET_SIZE = 512` for PG lock budget.
- `account_id` stays on every generated row (denormalized; anchors today, instance+child after Task 1.3). No global PostgreSQL singletons by telegram id — longs are columns/indices, not PK rows.
- No new composer dependencies; no changes to `composer.json` require.
- Every task is test-first; commit at each task end; PG-visible schema changes re-run `composer verify` + `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg`.

## File Structure

- `src/Schema/Eloquent/PeerIdTool.php` (create) — canonical peer long encode/encode + id helpers; dependency-free.
- `src/Schema/Eloquent/AccountContext.php` (create) — container-bound current-account carrier, default from `teleframe.primary_account_id`.
- `src/Schema/Eloquent/AccountScope.php` (create) — Eloquent global scope over `account_id`.
- `src/Schema/Eloquent/AccountScoped.php` (create) — trait applied to every generated model: boots AccountScope, offers `forAccount()`, `acrossAccounts()`, `byAccount()`.
- `src/Schema/Eloquent/PeerResolution.php` (create) — `peerModel()` resolver + peer scopes for models that hold a peer-long column.
- `src/Schema/Generator/MigrationGenerator.php` (modify) — Peer/InputPeer refs → bigInteger canonical long + index; every ref/id column indexed; account_id on instance+child tables; per-account uniqueness; message unique(account_id, peer_id, tl_id).
- `src/Schema/Generator/ModelGenerator.php` (modify) — use `AccountScoped` on every model; emit `belongsTo` for object refs; emit `HasTlPeers`+peer scopes for peer-long columns; reverse `hasMany` on anchors.
- `src/Schema/Generator/CodeWriter.php` (modify) — import helper (peer/relation imports) if needed.
- `src/Teleframe/Ingest/UpdateIngestor.php` (modify) — write account_id to instance/child rows; upsert anchors & messages against `(account_id, peer, id)` unique keys; collapse repeated same-key writes.
- `src/Laravel/Providers/TeleframeServiceProvider.php` (modify) — bind `AccountContext` singleton (default from config).
- `tests/Schema/` (modify) — RegenerationGoldenTest / ShipDialGoldenTest const/goldens updated after regen; new generator unit tests.
- `tests/New/PeerIdToolTest.php`, `tests/New/AccountIsolationTest.php`, `tests/New/RelationGenerationTest.php`, `tests/New/CrossAccountQueryTest.php`, `tests/New/IngestDedupTest.php` (create).

## Task 1: Peer canonical long — the ID mirror for Peer refs

### Task 1.1: PeerIdTool encode/decode

**Files:**
- Create: `src/Schema/Eloquent/PeerIdTool.php`
- Test: `tests/Schema/PeerIdToolTest.php`

**Interfaces:**
- Produces: `PeerIdTool::userLong(int $userId): int`, `voice chat` chat `PeerIdTool::chatLong(int $chatId): int`, `PeerIdTool::channelLong(int $channelId): int`, `PeerIdTool::decode(int $long): array{kind:'user'|'chat'|'channel', id:int}`, `PeerIdTool::isUser(int $long): bool`. Canonical forms (documented Telegram scheme): user → `+id`; chat → `-id`; channel → `-((1<<32) + id)` (i.e. `< -(1<<32)`).

- [x] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool;
use PHPUnit\Framework\TestCase;

final class PeerIdToolTest extends TestCase
{
    public function test_user_long_is_positive_id(): void
    {
        self::assertSame(777, PeerIdTool::userLong(777));
        self::assertTrue(PeerIdTool::isUser(777));
        self::assertSame(['kind' => 'user', 'id' => 777], PeerIdTool::decode(777));
    }

    public function test_chat_long_is_negated_id(): void
    {
        $long = PeerIdTool::chatLong(42);
        self::assertSame(-42, $long);
        self::assertSame(['kind' => 'chat', 'id' => 42], PeerIdTool::decode($long));
    }

    public function test_channel_long_is_below_negative_2to32(): void
    {
        $long = PeerIdTool::channelLong(100);
        self::assertLessThan(-(1 << 31), $long);
        self::assertSame(['kind' => 'channel', 'id' => 100], PeerIdTool::decode($long));
        self::assertFalse(PeerIdTool::isUser($long));
    }

    public function test_roundtrip_all_three(): void
    {
        foreach ([
            PeerIdTool::userLong(7),
            PeerIdTool::chatLong(7),
            PeerIdTool::channelLong(7),
        ] as $long) {
            $decoded = PeerIdTool::decode($long);
            $re = match ($decoded['kind']) {
                'user' => PeerIdTool::userLong($decoded['id']),
                'chat' => PeerIdTool::chatLong($decoded['id']),
                'channel' => PeerIdTool::channelLong($decoded['id']),
            };
            self::assertSame($long, $re);
        }
    }
}
```

- [x] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Schema/PeerIdToolTest.php`
Expected: FAIL with `Class "MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool" not found`.

- [x] **Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

use InvalidArgumentException;

/**
 * Telegram's canonical peer-to-long encoding (documented scheme used by
 * official libs): peerUser(id) -> +id; peerChat(id) -> -id;
 * peerChannel(id) -> -(2^32) - id. Decoding decides by value ranges, so
 * storing one bigint column per Peer ref mirrors Telegram's own "long peer"
 * identity for both indexing and cross-account queries.
 */
final class PeerIdTool
{
    /** Telegram puts channel ids below negative 2^32; chat ids in (-2^32, 0); users above 0. */
    private const CHANNEL_BASE = -(1 << 32);

    public static function userLong(int $userId): int
    {
        return $userId;
    }

    public static function chatLong(int $chatId): int
    {
        return -$chatId;
    }

    public static function channelLong(int $channelId): int
    {
        return self::CHANNEL_BASE - $channelId;
    }

    /** @return array{kind:'user'|'chat'|'channel', id:int} */
    public static function decode(int $long): array
    {
        if ($long > 0) {
            return ['kind' => 'user', 'id' => $long];
        }
        if ($long < self::CHANNEL_BASE) {
            return ['kind' => 'channel', 'id' => -(int) ($long - self::CHANNEL_BASE)];
        }
        if ($long < 0) {
            return ['kind' => 'chat', 'id' => -$long];
        }
        throw new InvalidArgumentException("Invalid peer long: {$long}");
    }

    public static function isUser(int $long): bool
    {
        return $long > 0;
    }
}
```

- [x] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Schema/PeerIdToolTest.php`
Expected: PASS (4 tests).

- [x] **Step 5: Commit**

```bash
git add src/Schema/Eloquent/PeerIdTool.php tests/Schema/PeerIdToolTest.php
git commit -m "feat(schema): PeerIdTool — canonical Telegram peer long encode/decode"
```

### Task 1.2: MigrationGenerator — Peer refs to bigInteger canonical long, index every id/ref column

**Files:**
- Modify: `src/Schema/Generator/MigrationGenerator.php` (`refColumn`, `scalarColumn` caller in `columnLines`)
- Test: `tests/Schema/MigrationGeneratorTest.php` (create if absent, else extend)

**Interfaces:**
- Consumes: `TlParam::kind()`, `TlParam::baseType()`, `TlParam::conditional()`.
- Produces: For a constructor with `from_id:flags.8?Peer`, the instance table emits `$table->bigInteger('from_id')->nullable()->index();` and a `$table->index('from_id');`. For `media:flags.9?MessageMedia`, unchanged uuid + index line `$table->index('media');`. For `user_id:long`, unchanged bigInteger + `$table->index('user_id');`.

- [x] **Step 1: Write the failing test** (extend MigrationGeneratorTest)

```php
public function test_peer_ref_columns_are_bigInteger_canonical_long(): void
{
    $gen = new MigrationGenerator();
    $scheme = (new TeleframeSchemeLoader())->fromString(
        'peerUser{user_id:long} = Peer;'
        . 'message{id:int from_id:flags.8?Peer peer_id:Peer media:flags.9?MessageMedia} = Message;'
        . 'mediaEmpty{} = MessageMedia;',
    );
    $files = $gen->generate($scheme);
    $message = $files['2026_08_28_000002_create_tl_message_table.php'];
    self::assertStringContainsString("\$table->bigInteger('from_id')->nullable();", $message);
    self::assertStringContainsString("\$table->bigInteger('peer_id');", $message);
    self::assertStringContainsString("\$table->index('peer_id');", $message);
    self::assertStringContainsString("\$table->index('from_id');", $message);
    self::assertStringContainsString("\$table->uuid('media');", $message);
    self::assertStringContainsString("\$table->index('media');", $message);
    self::assertStringNotContainsString("uuid('peer_id')", $message);
    $stats = $gen->stats();
    self::assertSame(0, $stats['fk_count']); // Peer refs no longer emit deferred FKs
}
```

- [x] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter peer_ref_columns_are_bigInteger_canonical_long tests/Schema`
Expected: FAIL (peer_id still `uuid`, no `index` for refs).

- [x] **Step 3: Implement**

In `columnLines()` (currently matches on `kind()` only): after computing `$col` and `$nullable`, add a branch — if `$param->kind() === 'ref'` delegate to `refColumn` (unchanged shape), else if `$param->kind() === 'scalar'` and `Naming::dbType($param, precision: true) === 'bigint'` and name ends in `_id` → append `->index()`. In `refColumn`:

```php
private function refColumn(TlParam $param, string $col, bool $nullable, array &$up): void
{
    if (in_array($param->baseType(), ['Peer', 'InputPeer'], true)) {
        $up[] = "    \$table->bigInteger('{$col}')" . ($nullable ? '->nullable()' : '') . ';';
        $up[] = "    \$table->index('{$col}');";
        return;
    }
    $up[] = "    \$table->uuid('{$col}')" . ($nullable ? '->nullable()' : '') . ';';
    $target = $param->baseType();
    if ($this->isFkTargetable($target, $param)) {
        $this->deferredFks[] = ['table' => $this->currentTable, 'column' => $col, 'target_table' => Naming::anchorTable($target)];
    }
    $up[] = "    \$table->index('{$col}');";
}
```

For scalar `*_id` indexing, in the `scalarColumn` match default branch, when `Naming::dbType(...) === 'bigint'` and `str_ends_with($param->name, '_id')`, emit the returned line plus `$table->index(...)`. Implement by having `columnLines` wrap `scalarColumn()` output:

```php
$line = $this->scalarColumn($param, $col, $nullable);
if (str_ends_with($param->name, '_id') && str_contains($line, 'bigInteger')) {
    $line = substr($line, 0, -1) . ")->index();"; // careful with ->nullable() suffix — see below
}
```

NOTE: `scalarColumn` returns e.g. `"    $table->bigInteger('user_id')->nullable();"`. To add an index cleanly, build `$indexLine` separately instead of string surgery:

```php
$line = ltrim($this->scalarColumn($param, $col, $nullable), ' ');
if (str_ends_with($param->name, '_id') && str_contains($line, 'bigInteger')) {
    $up[] = $line;
    $up[] = "    \$table->index('{$col}');";
} else {
    $up[] = $line;
}
```

- [x] **Step 4: Run test to verify it passes** — `vendor/bin/phpunit --filter peer_ref_columns tests/Schema` → PASS.

- [x] **Step 5: Commit** — `git commit -m "feat(schema): Peer refs become bigInteger canonical longs; index every id/ref column"`.

### Task 1.3: account_id denormalized onto instance + child tables

**Files:**
- Modify: `src/Schema/Generator/MigrationGenerator.php` (`instanceTables`, `childTable`, anchor `typeMigration`)
- Modify: `src/Teleframe/Ingest/UpdateIngestor.php` (write account_id on instance/child rows)
- Test: `tests/Schema/MigrationGeneratorTest.php`, `tests/Ingest/NestedIngestTest.php` (extend)

**Interfaces:**
- Produces: every generated table declares `$table->bigInteger('account_id');` + `$table->index('account_id');` (anchors already do; add to instance + child). UpdateIngestor copies the parent anchor's account_id into instance + child rows on write.

- [x] **Step 1: Write the failing test**

```php
// MigrationGeneratorTest
public function test_every_generated_table_has_account_id_column_and_index(): void
{
    $gen = new MigrationGenerator();
    $files = $gen->generate(new TeleframeSchemeLoader()->fromString(
        'user{id:long} = User; message{id:int text:string entities:flags.0?Vector<string>} = Message;',
    ));
    foreach (['tl_message', 'tl_message_message', 'tl_message_message__entities'] as $table) {
        $file = $files['2026_08_28_000001_create_tl_message_table.php'];
        self::assertStringContainsString("\$table->bigInteger('account_id');", $file);
        self::assertStringContainsString("\$table->index('account_id');", $file);
    }
}
```

- [x] **Step 2: Run to verify FAIL** — `vendor/bin/phpunit --filter every_generated_table_has_account_id`.

- [x] **Step 3: Implement** — in `instanceTables()` and `childTable()` add `account_id` + index lines next to `timestamps()`; in `UpdateIngestor`, when writing anchor + instance + child rows pass `account_id` into the child/instance column arrays (the anchor already carries it — copy the same value instead of relying on FK `parent` lookups).

- [x] **Step 4: Verify PASS** — extend `tests/Ingest/NestedIngestTest.php` with `assertSame(self::ACCOUNT, (int) $instance->account_id)` on a nested instance + a child row; run `vendor/bin/phpunit tests/Ingest`.

- [x] **Step 5: Commit** — `git commit -m "feat(schema): account_id denormalized onto instance + child tables; ingestor writes it"`.

### Task 1.4: Per-account uniqueness — (account_id, canonical id) upsert keys

**Files:**
- Modify: `src/Schema/Generator/MigrationGenerator.php` (`instanceTables`) — after columns, when the constructor declares a scalar `id:int|long` param, add a unique: if the table also holds a peer-long column (name in the ctor's Peer refs, e.g. `peer_id`) use `unique(['account_id', 'peer_id', 'tl_id'])` else `unique(['account_id', 'tl_id'])` — column name for the id param is `Naming::column('id')` = `tl_id` (existing behavior) — verify: in `tl_message_message` the schema emits `tl_id` for `id:int` (seen in current migrations). For the Message-type constructor containing `peer_id`, the unique composite is `('account_id', 'peer_id', 'tl_id')`.
- Test: extend `tests/Schema/MigrationGeneratorTest.php`.

**Interfaces:**
- Produces: anchor/instance tables that carry a scalar `id` become idempotent per account: `$table->unique(['account_id', 'tl_id'], 'ux_<sha1(table) short>')`. For `tl_message_message` specifically `$table->unique(['account_id', 'peer_id', 'tl_id'], 'ux_<sha1...>')`. Child vector tables keep their existing `unique(['parent_id','idx'])`.

- [x] **Step 1: Write the failing test**

```php
public function test_message_table_upholds_account_scoped_uniqueness(): void
{
    $gen = new MigrationGenerator();
    $scheme = (new TeleframeSchemeLoader())->fromString(
        'message{id:int peer_id:Peer} = Message;',
    );
    $files = $gen->generate($scheme);
    $migration = $files['2026_08_28_000001_create_tl_message_table.php'];
    self::assertStringContainsString("\$table->unique(['account_id', 'peer_id', 'tl_id']", $migration);
    self::assertStringContainsString("\$table->unique(['account_id', 'tl_id']", $migration);
}
```

- [x] **Step 2: Run to verify FAIL.**

- [x] **Step 3: Implement** — after the column loop and timestamps in `instanceTables`, detect the scalar `id` param `(kind()==='scalar' && name==='id')`; build `$uniqueCols = ['account_id']`; if any ref param has baseType `Peer`, append its column name; append `tl_id` (Naming::column('id')); emit `$table->unique([...], 'ux_'.substr(sha1($instance), 0, 20));`.

- [x] **Step 4: Verify PASS** (unit test).

- [x] **Step 5: Commit** — `git commit -m "feat(schema): per-account unique (account_id, canonical id) keys on instance tables"`.

### Task 1.5: Regenerate the tree; update goldens and affected column asserts

**Files:**
- Modify: `generated/**` (regenerated), `migrations/**` (re-shipped), `tests/Schema/RegenerationGoldenTest.php`, `tests/Schema/ShipDialGoldenTest.php`, any unit tests asserting `uuid('peer_id')`-style columns (grep first).
- Run: `php bin/regenerate` (verify determinism: run twice to temp dirs — RegenerationGoldenTest does this), `php bin/regenerate --ship`.

- [x] **Step 1: Regenerate** — `php bin/regenerate && php bin/regenerate --ship`; expect anchor/instance tables for user/chat/channel/message peers to now be bigInteger canonical-long columns, instance+child tables to carry account_id, uniqueness keys added, and deferred-FK count to drop (Peer refs no longer FK) — update any stat asserts (RegenerationGoldenTest `$counts['fks']`, ShipDialGoldenTest counts) to the new numbers.
- [x] **Step 2: Run full gate** — `composer verify` (must be green), `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg` (PG applies new migrations + ALTERs; watch the reduced FK count), `php bin/standalone-smoke.php`.
- [x] **Step 3: Fix any stragglers** — grep tests for `uuid('peer_id')` / `uuid('from_id')` asserts and update to bigInteger expectations.
- [x] **Step 4: Commit** — `git commit -m "chore(schema): regenerate — peer longs, account_id on all tables, per-account uniques; goldens updated"`.

## Task 2: Generated relations — belongsTo for object refs, peer resolution for Peer refs

### Task 2.1: belongsTo relation methods on instance models for object refs

**Files:**
- Modify: `src/Schema/Generator/ModelGenerator.php` (`ctorModel`) — for each non-vector, non-Peer ref param emit a `belongsTo` method.
- Modify: `src/Schema/Generator/CodeWriter.php` — helper to emit `use MeRezaRezaei\Teleframe\Schema\Generated\Models\Tl<Base>;` and `use Illuminate\Database\Eloquent\Relations\BelongsTo;`.
- Test: `tests/Schema/ModelGeneratorTest.php` (extend).

**Interfaces:**
- Produces: in `tl_message_message` model: `public function media(): BelongsTo { return $this->belongsTo(TlMessageMedia::class, 'media'); }`, `public function fwdFrom(): BelongsTo { return $this->belongsTo(TlMessageFwdHeader::class, 'fwd_from'); }`. Method name = camelCase of param name.

- [x] **Step 1: Write the failing test**

```php
public function test_object_ref_params_generate_belongsTo_methods(): void
{
    $scheme = (new TeleframeSchemeLoader())->fromString(
        'message{id:int media:flags.9?MessageMedia fwd_from:flags.2?MessageFwdHeader} = Message;'
        . 'mediaEmpty{} = MessageMedia; headerEmpty{} = MessageFwdHeader;',
    );
    $models = (new ModelGenerator())->generate($scheme);
    $instance = $models['TlMessageMessage.php'];
    self::assertStringContainsString('public function media(): BelongsTo', $instance);
    self::assertStringContainsString("->belongsTo(TlMessageMedia::class, 'media')", $instance);
    self::assertStringContainsString('use MeRezaRezaei\\Teleframe\\Schema\\Generated\\Models\\TlMessageMedia;', $instance);
    self::assertStringContainsString('fwdFrom', $instance);
}
```

- [x] **Step 2: Run to verify FAIL.**

- [x] **Step 3: Implement** — in `ctorModel`, beside the existing `childMethods`/`childUses` loop, add `$belongsToUses = []` and `$belongsToMethods = []`; for each param where `kind()==='ref'` and baseType not in `['Peer','InputPeer']` and `isFkTargetable`-equivalent (base not any/`Object`/`Type`/`X`/`True`, no `<`), push `use ...Models\Tl{Base};` + `public function {camel(name)}(): BelongsTo\n{ return $this->belongsTo(Tl{Base}::class, '{col}'); }`. Assemble via the existing body array; add `use Illuminate\Database\Eloquent\Relations\BelongsTo;` when methods exist.

- [x] **Step 4: Verify PASS.**

- [x] **Step 5: Commit** — `git commit -m "feat(schema): generated belongsTo relations for object-ref params"`.

### Task 2.2: Peer columns — generated scopes + cross-type peer resolver

**Files:**
- Modify: `src/Schema/Generator/ModelGenerator.php` — for Peer/InputPeer refs emit `use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;` instead of a relation, and mark the column.
- Create: `src/Schema/Eloquent/PeerResolution.php` — trait providing `peerCandidates(string $col)` scopes `scopeWhere{Col}IsUser`, `scopeWhere{Col}IsChat`, `scopeWhere{Col}IsChannel` and `resolvePeerModel(string $col): ?Model`.
- Test: `tests/Schema/PeerResolutionTest.php` + `tests/Schema/ModelGeneratorTest.php`.

**Interfaces:**
- Produces (trait, reused by every generated model that has a peer-long column):
  - `scopeWherePeerIsUser(Builder $q, int $userId): void` → `$q->where('peer_id', PeerIdTool::userLong($userId));`
  - `scopeWherePeerIsChat`, `scopeWherePeerIsChannel` analogously.
  - `resolvePeerModel(string $col): ?TlUser|TlChat|TlChannel` → decode the stored long; query `TlUser::where('tl_id', $id)->first()` (within AccountScope) / `TlChat` / `TlChannel`; returns null when the peer was never seen by the scoped account.

- [x] **Step 1: Write the failing test**

```php
// PeerResolutionTest — uses a temp table `peer_holder` (migrated in-tenant) so the trait
// can be tested on a concrete Eloquent model without the full generated set.
final class PeerHolder extends Model {
    use PeerResolution;
    protected $table = 'peer_holder';
    public $timestamps = false;
}
final class PeerResolutionTest extends TestCase {
    public function test_resolve_peer_model_scoped_to_account(): void
    {
        // seed tl_user + tl_chat rows for account 1 and account 8 via Schema::create in setup
        $holder = new PeerHolder(); $holder->account_id = 1; $holder->peer_id = PeerIdTool::chatLong(7); $holder->save();
        $other = new PeerHolder(); $other->account_id = 8; $other->peer_id = PeerIdTool::chatLong(7); $other->save();
        $model = PeerHolder::where('account_id', 1)->first();
        $peer = $model->resolvePeerModel('peer_id');
        self::assertInstanceOf(TlChat::class, $peer);
        self::assertSame('7', (string) $peer->tl_id); // only account 1's chat; account 8 has separate row
        self::assertSame(1, (int) $peer->account_id);
    }
    public function test_user_never_seen_by_account_resolves_null(): void
    {
        $holder = new PeerHolder(); $holder->account_id = 1; $holder->peer_id = PeerIdTool::userLong(999); $holder->save();
        $model = PeerHolder::where('account_id', 1)->first();
        self::assertNull($model->resolvePeerModel('peer_id'));
    }
}
```

- [x] **Step 2: Run to verify FAIL** (trait + methods missing).

- [x] **Step 3: Implement** — `PeerResolution` trait with the three scopes (delegating to `PeerIdTool`) and `resolvePeerModel`. In `ModelGenerator.ctorModel`, for Peer refs inject `use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;` (once) instead of a belongsTo method.

- [x] **Step 4: Verify PASS** — run `tests/Schema/ModelGeneratorTest.php` filter and `tests/Schema/PeerResolutionTest.php`.

- [x] **Step 5: Commit** — `git commit -m "feat(schema): peer-long columns get generated scopes + account-scoped peer resolver"`.

### Task 2.3: Reverse hasMany on anchors for object refs

**Files:**
- Modify: `src/Schema/Generator/ModelGenerator.php` (`anchorModel`) — for every `ref` param pointing at this type from any constructor/child, add `hasMany` with param-plural method name.
- Test: `tests/Schema/ModelGeneratorTest.php`.

**Interfaces:**
- Produces: `TlMessageMedia::messages(): HasMany { return $this->hasMany(TlMessageMessage::class, 'media'); }` (only when a ref exists). Method name = plural snake of originating param, camelized.

- [x] **Step 1: Write the failing test**

```php
public function test_anchor_models_get_reverse_has_many_for_incoming_refs(): void
{
    $scheme = (new TeleframeSchemeLoader())->fromString(
        'message{id:int media:flags.9?MessageMedia} = Message; mediaEmpty{} = MessageMedia;',
    );
    $models = (new ModelGenerator())->generate($scheme);
    self::assertStringContainsString('public function messages(): HasMany', $models['TlMessageMedia.php']);
    self::assertStringContainsString("->hasMany(TlMessageMessage::class, 'media')", $models['TlMessageMedia.php']);
}
```

- [x] **Step 2: FAIL → Step 3: Implement** — during `anchorModel`, scan all types' constructors for refs whose baseType === this type name; collect `[table, col]` pairs; emit one `hasMany` per pair.

- [x] **Step 4: PASS → Step 5: Commit** — `git commit -m "feat(schema): generated reverse hasMany on anchors"`.

### Task 2.4: AccountScoped trait on every generated model

**Files:**
- Create: `src/Schema/Eloquent/AccountContext.php`, `src/Schema/Eloquent/AccountScope.php`, `src/Schema/Eloquent/AccountScoped.php`
- Modify: `src/Schema/Generator/ModelGenerator.php` (anchor + ctor + child models: add `use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;` + trait)
- Modify: `src/Laravel/Providers/TeleframeServiceProvider.php` (bind AccountContext)
- Test: `tests/Schema/AccountIsolationTest.php`

**Interfaces:**
- `AccountContext::current(): ?int` (container singleton; default from `config('teleframe.primary_account_id')`), `AccountContext::for(int $id, callable $cb): mixed` (scoped run), `AccountContext::reset(): void`.
- `AccountScoped` trait: `bootAccountScoped()` adds global `AccountScope` (filter `account_id = AccountContext::current()` when non-null); provides `scopeForAccount($q, int $accountId)`, `scopeAcrossAccounts($q)` (drops the global scope), and `forAccount()`/`acrossAccounts()` query entry points.
- Every generated model (`Tl*`, anchor/instance/child) applies the trait automatically so NEW relations and queried data respect isolation by default.

- [x] **Step 1: Write the failing test**

```php
final class AccountIsolationTest extends TestCase {
    public function test_default_scope_filters_by_primary_account(): void
    {
        AccountContext::for(1, function (): void {
            // seed tl_user rows for account 1 and 8; default (primary) sees only account 1
            self::assertSame(1, TlUser::count());
            self::assertNull(TlUser::where('tl_id', 88)->first()); // account 8's user hidden
        });
    }
    public function test_across_accounts_reveals_all_and_accounts_are_identifiable(): void
    {
        AccountContext::for(1, function (): void {
            $seen = TlUser::acrossAccounts()->where('tl_id', 88)->get();
            self::assertCount(1, $seen);
            self::assertSame(8, (int) $seen->first()->account_id);
        });
    }
}
```

- [x] **Step 2: FAIL → Step 3: Implement** — the three Schema\Eloquent classes; provider singleton binding `AccountContext` with default `config('teleframe.primary_account_id')`; `ModelGenerator` emits the trait import + use on every generated model body.

- [x] **Step 4: PASS → Step 5: Commit** — `git commit -m "feat(schema): AccountContext + AccountScoped global scope on all generated models"`.

## Task 3: Cross-account queries + ingest dedup collapse

### Task 3.1: Cross-account query patterns (which accounts hold a peer / message set)

**Files:**
- Test: `tests/New/CrossAccountQueryTest.php` (create) — pure query-pattern tests over generated tables with seed data for two accounts.

**Interfaces:**
- Consumes: `AccountContext`, `AccountScoped`, `PeerIdTool`, unique upsert keys from Task 1.4.
- Produces: proven query pattern to return `account_id` list for a telegram user id: `TlMessage::acrossAccounts()->where('peer_id', PeerIdTool::userLong($userId))->distinct()->orderBy('account_id')->pluck('account_id')->all()`.

- [x] **Step 1: Write the failing-context test (seeds two accounts, asserts the pattern)**

```php
public function test_which_accounts_have_messages_from_user(): void
{
    AccountContext::for(1, function (): void {
        $accounts = TlMessage::acrossAccounts()
            ->where('peer_id', PeerIdTool::userLong(777))
            ->distinct()->orderBy('account_id')->pluck('account_id')->all();
        self::assertSame([1, 8], $accounts); // both accounts exchanged messages with 777
    });
}
```

- [x] **Step 2: Run — must PASS once Task 2.4 is landed** (if it fails, the gap is `acrossAccounts()`; fix trait). This task exists so the pattern is *documented and locked* before the ingestor changes.
- [x] **Step 3: Commit** — `git commit -m "test(schema): cross-account message query patterns locked"`.

### Task 3.2: Ingest upsert collapse — one row per (account, canonical key), deduped

**Files:**
- Modify: `src/Teleframe/Ingest/UpdateIngestor.php` (write path)
- Test: `tests/Ingest/UpdateIngestorTest.php`, `tests/Ingest/NestedIngestTest.php` (extend)

**Interfaces:**
- Produces: anchor rows upsert on `account_id` + the type's canonical id(s): first `SELECT` by unique key within account, else `INSERT`, else `UPDATE` (single statement per entity per key). Message instance rows upsert on `(account_id, peer_id, tl_id)` — the SAME telegram message arriving from two account streams yields exactly one row per account (never 2 per account), and re-delivery (replay) never duplicates. Shared group/channel entity arriving from multiple accounts keeps N account-scoped rows (tenancy preserved) but each is a single keyed upsert — no cross-account INSERT amplification.

- [x] **Step 1: Write the failing test**

```php
public function test_same_message_via_two_account_streams_yields_one_row_per_account(): void
{
    // ingest message id=5 peer=userLong(999) twice for account 1 and once for account 8
    UpdateIngestor::for(self::ACCOUNT)->ingest($updateA1);
    UpdateIngestor::for(self::ACCOUNT)->ingest($updateA1Clone); // replay
    UpdateIngestor::for(8)->ingest($updateA8);
    self::assertSame(1, TlMessage::forAccount(self::ACCOUNT)->where('tl_id', 5)->count());
    self::assertSame(1, TlMessage::forAccount(8)->where('tl_id', 5)->count());
    self::assertSame(2, TlMessage::acrossAccounts()->where('tl_id', 5)->count());
}
```

- [x] **Step 2: Run to verify FAIL** (today: re-delivery probably duplicates anchor rows).
- [x] **Step 3: Implement** — in the message/anchor write path, before INSERT run an EXISTS lookup on the unique key (account-scoped); if present, `UPDATE` the content columns instead. Guard child-table inserts with the existing `unique(parent_id, idx)` (already upsert-safe via unique constraint + catch).
- [x] **Step 4: Run to verify PASS** — `vendor/bin/phpunit tests/Ingest` green.
- [x] **Step 5: Commit** — `git commit -m "feat(ingest): upsert collapse keyed on (account, canonical id) — replay-safe, per-account dedup"`.

### Task 3.3: Regenerate + goldens + full gate + docs

**Files:**
- Modify: `generated/**`, `migrations/**`, `tests/Schema/RegenerationGoldenTest.php`, `tests/Schema/ShipDialGoldenTest.php`, `docs/superpowers/specs/2026-09-07-teleframe-unification-design.md` (status line → enhancement COMPLETE), `docs/` relation/isolation sections if present.

- [x] **Step 1: `php bin/regenerate && php bin/regenerate --ship`**; update goldens/stat asserts (FK count drops again with relation changes; anchor files now carry trait imports).
- [x] **Step 2: Full gate** — `composer verify` (phpstan level 5 clean, all 940+ tests), `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg`, `php bin/standalone-smoke.php` exit 0.
- [x] **Step 3: Docs** — spec status line + a short "IDs & relations & tenancy" section: canonical peer longs, generated belongsTo/hasMany, AccountContext isolation, cross-account query pattern.
- [x] **Step 4: Commit** — `git commit -m "feat(schema): relations + peer longs + tenancy landed — regenerate, goldens, docs"`.
- [x] **Step 5: Push** — `git push origin main`.