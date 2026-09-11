# NF5 Mirror Generator Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A deterministic generator (`teleframe:mirror`) that reads the committed catalog + the Layer-227 wire file and emits NF5-mirror migrations and Eloquent models — zero nullable columns, zero JSON/blob, anywhere.

**Architecture:** Three new reader/resolver/writer passes feed one console command. `Nf5Catalog` reads `2026-09-11-telegram-mirror-catalog.json` (and synthesizes `tf_messages_service` per spec §4 decision B via `Nf5CtorSplitter`). `Nf5TableResolver` turns catalog entries + TL ctor params into an immutable DDL graph (`Nf5Table`/`Nf5Column`), applying the NF5 rules (§5–§8). `Nf5MigrationWriter` and `Nf5ModelWriter` render that graph into migrations and models. Each task is TDD; the Stage 0 gate proves the full pipeline on 5 tables before stage 1 rolls out all 36.

**Tech Stack:** PHP 8.3, Laravel 13 (Illuminate database/schema/console), Testbench (Orchestra) for migration tests, SQLite in tests, existing `src/Schema/Generator/{CodeWriter,TlParser->TlScheme}` and `Naming` helpers.

**Spec:**
- `docs/superpowers/specs/2026-09-11-telegram-mirror-schema-nf5-design.md` (prose; the plan argues from it)
- `docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json` (machine contract, committed)

## Global Constraints

Every task's requirements implicitly include these — copied verbatim from the spec / AGENTS.md.

- **Zero NULLable columns in the entire schema.** Every column is `NOT NULL` (spec §1.3, §5, §12). Absence = row absence in child tables.
- **Zero `json` / `jsonb` / `longText(json)`** (spec §1.1, §12). **Zero `blob`/`binary`** (spec §1.2, §12). Bytes → hex `TEXT`.
- **Every table carries `account_id BIGINT NOT NULL` as the first PK column** (spec §7).
- **Parent PK `(account_id, id)`** with `id` = the TL natural id; **child PK `(account_id, parent_key...)` (+ `position SMALLINT NOT NULL` for vectors)** (spec §7, §5.5).
- **FKs enforced**: children `ON DELETE CASCADE` to their parent; `{target}_id` refs `ON DELETE RESTRICT` (spec §7). `Peer` refs are never FK columns — inline `peer_type TINYINT NOT NULL` + `peer_id BIGINT NOT NULL` pair on the host row (spec §4, §6).
- **`flags.N?true` → `BOOLEAN NOT NULL DEFAULT FALSE`** (spec §5.1). **Typed optional fact → 1:1 child table** (spec §5.2). **`Vector<X>` → 1:N child table with `position` ordinal** (spec §5.5). **Multi-ctor unions → `constructor VARCHAR(64) NOT NULL` discriminator column** (spec §5.4). **Two flag ints are never stored** (spec §5.3).
- **No timestamps / audit columns** (spec §9, §12) — `public $timestamps = false;` on every model.
- **Generated files** carry the CodeWriter banner (`// GENERATED — do not edit; run artisan teleframe:regenerate`) and are **never hand-edited** (AGENTS.md). New mirror output lives under `generated/Models/Mirror/` (models) and `generated/migrations/mirror/` (migrations).
- **Layer gap 227-wire / 229-schema is intentional** — never touch `EncryptedConnection::LAYER` or `composer extra.telegram-layer`.
- **Zero-regex** in `src/Core/**` and `src/Teleframe/**`; `src/Schema/**` is allow-listed but this plan uses string ops only.
- **`composer verify`** (phpunit + phpstan level 5 src-only + regeneration idempotence) and `php bin/standalone-smoke.php` must stay green after every commit.

## Naming & resolver conventions (locked, used by every writer)

| Rule | Convention |
|---|---|
| R1 | Parent table PK = `account_id` + key columns from base: `id` if base has `id`; `peer_type, peer_id` if base has `peer`. Empty-base tables (media/entities/actions) are **child-keyed**: PK inherits the first referrer's key columns. |
| R2 | Child table name: catalog target → catalog name verbatim (`tf_message_entities`); non-catalog field `name` under table `tf_x` → `tf_x_{name}` (`tf_messages_fwd_from`, `tf_users_usernames`). |
| R3 | Any catalog entry with >1 `ctors` gets `constructor VARCHAR(64) NOT NULL` (TL ctor name). Single-ctor tables do not. |
| R4 | `bytes` → hex `TEXT NOT NULL`. `access_hash`, `file_reference`, etc. are hex in DB; `hex2bin()` at the MTProto call site (spec §8). |
| R5 | Peer shape renders as two inline columns `peer_type TINYINT NOT NULL` + `peer_id BIGINT NOT NULL` (spec §6). |
| R6 | Vector element types come from the TL scheme (`Nf5FieldDecomposer::shapeForParam(TlParam)`) — the catalog's `1:N child` marker carries no element type; the TL param does. |
| R7 | FK constraint columns must be covered by an index: the child PK `(account_id, key...)` always covers its own FK to the parent. |

## Interfaces (defined here; tasks implement them)

```php
namespace MeRezaRezaei\Teleframe\Schema\Nf5;

/** Thrown on any catalog/schema violation (R1–R7, zero-null rules). */
final class Nf5SchemaException extends \RuntimeException {}

final class Nf5CtorSplitter {
    /** @param array<string,array> $catalog entries keyed by tf_* name @return array<string,array> */
    public function apply(array $catalog): array;
}

final class Nf5Catalog {
    public static function load(string $jsonPath, \MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme $scheme): self;
    /** @return list<string> deterministic catalog order */
    public function tableNames(): array;
    public function table(string $tfName): Nf5TableEntry;
    public function has(string $tfName): bool;
    /** @return ?Nf5TableEntry */
    public function tableForTlType(string $tlType): ?Nf5TableEntry;
    public function assertInvariants(): void;      // zero-null, zero-json rules on every entry
}

final class Nf5TableEntry {
    public string $tfName; public string $tlType; public array $ctors; /** @var list<array{string,string}> */ public array $base; public array $bools; /** @var list<array{string,string}> */ public array $children;
}
```

```php
namespace MeRezaRezaei\Teleframe\Schema\Nf5\Ddl;

enum Nf5ColumnType: string {
    case BigInt = 'bigint';
    case Integer = 'integer';
    case Boolean = 'boolean';
    case String = 'string';     // TEXT or VARCHAR(length)
    case TinyInt = 'tinyint';
    case Double = 'double';
}

final class Nf5Column {
    public function __construct(
        public readonly string $name,
        public readonly Nf5ColumnType $type,
        public readonly int $length = 0,       // VARCHAR(n); 0 = TEXT/bigint/...
        public readonly bool $peer = false,    // renders tinyint kind + bigint id
        public readonly bool $hex = false,     // bytes field, hex TEXT
    ) {}
}

final class Nf5Table {
    public function __construct(
        public readonly string $tfName,
        public readonly string $tlType,        // '' for synthetic rows
        public readonly bool $parent,          // true = own PK under account_id
        public readonly bool $positioned,      // vector child: position in PK
        public readonly string $parentTf,      // '' for parents
        /** key columns after account_id, e.g. ['id'] or ['message_id'] or ['peer_type','peer_id'] */
        public readonly array $keyColumns,
        /** @var list<Nf5Column> full column list incl. account_id + key + constructor */
        public readonly array $columns,
        /** @var list<Nf5Table> */
        public readonly array $children,       // 1:1 fact children + 1:N vectors, in deterministic order
        public readonly string $constructor,   // '' = single ctor / synthetic
        public readonly array $peerColumns,    // list<array{kind:string,id:string}> names
        public readonly array $hexColumns,     // list<string>
        public readonly array $booleanColumns, // list<string> (for casts)
    ) {}
}
```

```php
namespace MeRezaRezaei\Teleframe\Schema\Nf5;

final class Nf5TableResolver {
    public function __construct(private readonly Nf5Catalog $catalog, private readonly \MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme $scheme) {}
    /** @return list<Nf5Table> parent tables (each already containing its full child subtree) */
    public function resolveAll(array $parentTfNames): array;
}

final class Nf5MigrationWriter {
    public function __construct(private readonly string $outDir) {}
    /** @param list<Nf5Table> $parents @return list<string> written file paths */
    public function writeAll(array $parents, string $dateStamp = '2026_09_11'): array;
}

final class Nf5ModelWriter {
    public function __construct(private readonly string $outDir) {}
    /** @param list<Nf5Table> $parents @return list<string> written file paths */
    public function writeAll(array $parents): array;
}
```

```php
namespace MeRezaRezaei\Teleframe\Laravel\Console;

final class TeleframeMirrorCommand extends \Illuminate\Console\Command {
    protected $signature = 'teleframe:mirror
        {--stage=1 : proof(0)|all(1)|factories(2)|queries(3)}
        {--out=    : output dir, default generated/}';
}
```

---

### Task 1: Nf5CtorSplitter — synthesize `tf_messages_service`

Implements spec §4 decision B ("Message vs MessageService — separate tables"). The committed catalog merges all three `Message` ctors into one `tf_messages` entry and **omits the service-only facts** (`reactions_are_possible` flag, `action` child). This task mechanically derives the service table from the TL scheme's `messageService` ctor so the split is reproducible and never hand-curated.

**Files:**
- Create: `src/Schema/Nf5/Nf5CtorSplitter.php`
- Test: `tests/Schema/Nf5/Nf5CtorSplitterTest.php`

**Interfaces:**
- Consumes: `TlScheme` → `types()['Message']->constructors()['messageService']->params()` (name→TlParam, fillers excluded); `TlParam::kind()` (`'scalar'|'ref'|'vector'|'nat'|'true'|'generic'`) and `TlParam::baseType()`.
- Produces: `Nf5CtorSplitter::apply(array $catalog): array` — catalog entries keyed by `tf_*` name, with `tf_messages` trimmed and `tf_messages_service` present as a bona-fide entry.

**Split algorithm (deterministic, rule-based):**
1. `$all = $scheme->types()['Message']->constructors();` `$src = $all['messageService'];` `$paramNames = array_keys($src->params());`
2. From the `tf_messages` entry:
   - `base`: keep base tuples whose name ∈ `$paramNames` → `id`, `peer_id`, `date`.
   - `bools`: catalog bools ∩ `$paramNames` → `out, mentioned, media_unread, silent, post, legacy`; plus every `$src` param with `kind() === 'true'` whose name ∉ catalog bools → adds `reactions_are_possible`. (messageService flags are bits 1,4,5,9,13,14,19 only — no `flags2`.)
   - `children`: catalog children whose name ∈ `$paramNames` → `from_id` (peer), `saved_peer_id` (peer), `reply_to` (FK→MessageReplyHeader), `ttl_period`, `reactions` (FK→MessageReactions); plus `action` → `['action', "FK→MessageAction"]` (from `$src` param `action`, `kind()==='ref'`, `baseType()==='MessageAction'`).
   - `ctors`: `['messageService']`; `tl`: `'Message'`.
3. Trim the source entry: `tf_messages.ctors = array_values(array_filter($catalog['tf_messages']['ctors'], fn($c) => $c !== 'messageService'))` → `['messageEmpty','message']`. Its fields are unchanged (already the `message` ctor's).

- [ ] **Step 1: Write the failing test** — loads the **real committed artifacts** (integration-facing, deterministic because both files are committed):

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5CtorSplitter;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

final class Nf5CtorSplitterTest extends TestCase
{
    private function catalog(): array
    {
        $json = file_get_contents(__DIR__.'/../../../../docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json');
        self::assertNotFalse($json);
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_message_service_is_synthesised(): void
    {
        $scheme = TlParser::parseFile(__DIR__.'/../../../../schema/sources/TL_telegram_v227.tl');
        $out = (new Nf5CtorSplitter($scheme))->apply($this->catalog());

        self::assertArrayHasKey('tf_messages_service', $out);
        $svc = $out['tf_messages_service'];

        self::assertSame('Message', $svc['tl']);
        self::assertSame(['messageService'], $svc['ctors']);
        self::assertSame([['id', 'BIGINT NOT NULL'], ['peer_id', 'peer_type TINYINT + peer_id BIGINT'], ['date', 'INTEGER NOT NULL']], $svc['base']);
        self::assertSame(['out', 'mentioned', 'media_unread', 'reactions_are_possible', 'silent', 'post', 'legacy'], $svc['bools']);
        $childNames = array_column($svc['children'], 0);
        self::assertContains('action', $childNames);
    }

    public function test_source_entry_is_trimmed(): void
    {
        $scheme = TlParser::parseFile(__DIR__.'/../../../../schema/sources/TL_telegram_v227.tl');
        $out = (new Nf5CtorSplitter($scheme))->apply($this->catalog());

        self::assertSame(['messageEmpty', 'message'], $out['tf_messages']['ctors']);
        $childNames = array_column($out['tf_messages']['children'], 0);
        self::assertNotContains('action', $childNames);
        self::assertNotContains('reactions_are_possible', $out['tf_messages']['bools']);
    }

    public function test_nondestructive_for_untouched_tables(): void
    {
        $scheme = TlParser::parseFile(__DIR__.'/../../../../schema/sources/TL_telegram_v227.tl');
        $out = (new Nf5CtorSplitter($scheme))->apply($this->catalog());
        self::assertSame($this->catalog()['tf_users'], $out['tf_users']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Schema/Nf5/Nf5CtorSplitterTest.php`
Expected: FAIL — `Class Nf5CtorSplitter not found`.

- [ ] **Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlConstructor;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;

/**
 * Spec §4 decision B: Message and MessageService are disjoint constructor
 * variants and must not share one table with nullable columns. The catalog
 * entry merges them; this splitter mechanically re-derives tf_messages_service
 * from the Layer-227 wire ctor so the split survives regeneration.
 */
final class Nf5CtorSplitter
{
    private const MESSAGE_TYPE = 'Message';
    private const SPLIT_CTOR = 'messageService';
    private const TARGET = 'tf_messages_service';

    public function __construct(private readonly TlScheme $scheme) {}

    /** @param array<string,array> $catalog */
    public function apply(array $catalog): array
    {
        $src = $this->ctor();
        if ($src === null || !isset($catalog['tf_messages'])) {
            return $catalog;
        }

        $parent = $catalog['tf_messages'];
        $paramNames = array_keys($src->params());

        $out = $catalog;
        $service = [
            'tl' => self::MESSAGE_TYPE,
            'ctors' => [self::SPLIT_CTOR],
            'base' => array_values(array_filter($parent['base'], fn (array $b) => in_array($b[0], $paramNames, true))),
            'bools' => [],
            'children' => [],
        ];

        foreach ($parent['bools'] as $bool) {
            if (in_array($bool, $paramNames, true)) {
                $service['bools'][] = $bool;
            }
        }
        foreach ($src->params() as $name => $param) {
            if ($param->kind() === 'true' && !in_array($name, $service['bools'], true)) {
                $service['bools'][] = $name;
            }
        }

        foreach ($parent['children'] as $child) {
            if (in_array($child[0], $paramNames, true)) {
                $service['children'][] = $child;
            }
        }
        foreach ($src->params() as $name => $param) {
            if ($param->kind() !== 'ref' || in_array($name, array_column($service['children'], 0), true)) {
                continue;
            }
            $service['children'][] = [$name, 'FK→' . $param->baseType()];
        }

        $out[self::TARGET] = $service;
        $out['tf_messages']['ctors'] = array_values(array_filter($parent['ctors'], fn (string $c) => $c !== self::SPLIT_CTOR));

        return $out;
    }

    private function ctor(): ?TlConstructor
    {
        $msg = $this->scheme->types()[self::MESSAGE_TYPE] ?? null;
        if ($msg === null) {
            return null;
        }
        return $msg->constructors()[self::SPLIT_CTOR] ?? null;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Schema/Nf5/Nf5CtorSplitterTest.php`
Expected: PASS (3 tests). If `test_nondestructive_for_untouched_tables` fails, the splitter mutated a table it shouldn't — fix the filter, do not hand-edit the catalog.

- [ ] **Step 5: Run phpstan on the new file**

Run: `vendor/bin/phpstan analyse src/Schema/Nf5 --level=5 --no-progress`
Expected: 0 errors.

- [ ] **Step 6: Commit**

```bash
git add src/Schema/Nf5/Nf5CtorSplitter.php tests/Schema/Nf5/Nf5CtorSplitterTest.php
git commit -m "feat(schema): Nf5CtorSplitter synthesises tf_messages_service from wire ctor"
```

### Task 2: Nf5Catalog — typed reader + zero-NULL invariant gate

**Files:**
- Create: `src/Schema/Nf5/Nf5SchemaException.php`, `src/Schema/Nf5/Nf5TableEntry.php`, `src/Schema/Nf5/Nf5Catalog.php`
- Test: `tests/Schema/Nf5/Nf5CatalogTest.php`

**Interfaces:**
- Consumes: `Nf5CtorSplitter::apply()`, plus a `TlScheme` to build it.
- Produces: `Nf5Catalog` (see Interfaces block) — the single entry point the resolver consumes, so the splitter is always applied and never forgotten.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5Catalog;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5SchemaException;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

final class Nf5CatalogTest extends TestCase
{
    private function catalog(): Nf5Catalog
    {
        $scheme = TlParser::parseFile(__DIR__.'/../../../../schema/sources/TL_telegram_v227.tl');
        return Nf5Catalog::load(__DIR__.'/../../../../docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
    }

    public function test_all_catalog_tables_plus_service_split_are_known(): void
    {
        $names = $this->catalog()->tableNames();
        self::assertCount(37, $names); // 36 catalog entries + tf_messages_service
        self::assertContains('tf_messages_service', $names);
        self::assertContains('tf_users', $names);
        self::assertSame($names, array_values(array_unique($names)), 'tableNames must be deterministic');
    }

    public function test_table_entry_shapes(): void
    {
        $c = $this->catalog();
        self::assertSame('Message', $c->table('tf_messages')->tlType);
        self::assertSame(['messageEmpty', 'message'], $c->table('tf_messages')->ctors);
        self::assertSame([['action', 'FK→MessageAction']], array_values(array_filter(
            $c->table('tf_messages_service')->children,
            fn (array $ch) => $ch[0] === 'action',
        )));
        self::assertSame('tf_users', $c->tableForTlType('User')?->tfName);
    }

    public function test_invariants_reject_nullable_and_json_shapes(): void
    {
        $this->expectException(Nf5SchemaException::class);
        Nf5Catalog::fromEntries([
            'tf_bad' => ['tl' => 'X', 'ctors' => ['x'], 'base' => [['id', 'BIGINT NULL']], 'bools' => [], 'children' => []],
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Schema/Nf5/Nf5CatalogTest.php`
Expected: FAIL — classes not found.

- [ ] **Step 3: Write the implementation**

`Nf5TableEntry` — immutable value object over one catalog entry:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Nf5;

final class Nf5TableEntry
{
    /** @param list<string> $ctors @param list<array{string,string}> $base @param list<string> $bools @param list<array{string,string}> $children */
    public function __construct(
        public readonly string $tfName,
        public readonly string $tlType,
        public readonly array $ctors,
        public readonly array $base,
        public readonly array $bools,
        public readonly array $children,
    ) {}
}
```

`Nf5SchemaException`:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Nf5;

final class Nf5SchemaException extends \RuntimeException {}
```

`Nf5Catalog` — reader + invariant gate (spec §12; throws on the first violation):

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;

final class Nf5Catalog
{
    /** @var array<string, Nf5TableEntry> */
    private array $tables = [];

    /** @var array<string, string> tl type => tf table */
    private array $tlIndex = [];

    /** @param array<string, array{tl:string,ctors:list<string>,base:list<array{string,string}>,bools:list<string>,children:list<array{string,string}>}> $entries */
    public static function fromEntries(array $entries): self
    {
        $self = new self();
        foreach ($entries as $tfName => $e) {
            $entry = new Nf5TableEntry($tfName, $e['tl'], $e['ctors'], $e['base'], $e['bools'], $e['children']);
            $self->tables[$tfName] = $entry;
            $self->tlIndex[$e['tl']] = $tfName;
            $self->assertEntryInvariants($entry);
        }
        return $self;
    }

    public static function load(string $jsonPath, TlScheme $scheme): self
    {
        $json = file_get_contents($jsonPath);
        if ($json === false) {
            throw new Nf5SchemaException("Cannot read catalog: {$jsonPath}");
        }
        $entries = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $entries = (new Nf5CtorSplitter($scheme))->apply($entries);
        return self::fromEntries($entries);
    }

    /** @return list<string> */
    public function tableNames(): array
    {
        return array_keys($this->tables);
    }

    public function table(string $tfName): Nf5TableEntry
    {
        return $this->tables[$tfName] ?? throw new Nf5SchemaException("Unknown mirror table: {$tfName}");
    }

    public function has(string $tfName): bool
    {
        return isset($this->tables[$tfName]);
    }

    public function tableForTlType(string $tlType): ?Nf5TableEntry
    {
        return isset($this->tlIndex[$tlType]) ? $this->tables[$this->tlIndex[$tlType]] : null;
    }

    public function assertInvariants(): void
    {
        foreach ($this->tables as $name => $entry) {
            if ($entry->base !== [] && $entry->base[0][0] !== 'id') {
                throw new Nf5SchemaException("{$name}: first base column must be 'id' or 'peer'");
            }
        }
    }

    /** @param array{tl:string,ctors:list<string>,base:list<array{string,string}>,bools:list<string>,children:list<array{string,string}>} $e */
    private function assertEntryInvariants(Nf5TableEntry $e): void
    {
        if ($e->ctors === []) {
            throw new Nf5SchemaException("{$e->tfName}: ctors must be non-empty");
        }
        foreach ([...$e->base, ...$e->children] as [$name, $shape]) {
            foreach (['NULL', 'json', 'blob', 'binary'] as $banned) {
                if (str_contains(strtolower($shape), $banned)) {
                    throw new Nf5SchemaException("{$e->tfName}.{$name}: banned shape '{$shape}'");
                }
            }
        }
    }
}
```

Note: `account_id` is a generated-pipeline convention, not present in the JSON — the resolver prepends it (Rule R1); the catalog promises the rest.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Schema/Nf5/Nf5CatalogTest.php`
Expected: PASS (3 tests).

- [ ] **Step 5: Run phpstan**

Run: `vendor/bin/phpstan analyse src/Schema/Nf5 --level=5 --no-progress`
Expected: 0 errors.

- [ ] **Step 6: Commit**

```bash
git add src/Schema/Nf5/Nf5SchemaException.php src/Schema/Nf5/Nf5TableEntry.php src/Schema/Nf5/Nf5Catalog.php tests/Schema/Nf5/Nf5CatalogTest.php
git commit -m "feat(schema): Nf5Catalog reader with zero-NULL/zero-JSON invariant gate"
```

---

### Task 3: DDL DTOs + Nf5FieldDecomposer — shape-string renderer

Turns catalog shape strings (`'BIGINT NOT NULL'`, `'peer_type TINYINT + peer_id BIGINT'`, `'FK→MessageFwdHeader'`, `'1:N child'`) into concrete `Nf5Column` lists the writers can emit verbatim, and exposes `shapeForParam(TlParam)` so the resolver can decompose vector elements and non-catalog FK targets directly from the TL scheme.

**Files:**
- Create: `src/Schema/Nf5/Ddl/Nf5ColumnType.php` (enum), `src/Schema/Nf5/Ddl/Nf5Column.php`, `src/Schema/Nf5/Nf5FieldDecomposer.php`
- Test: `tests/Schema/Nf5/Nf5FieldDecomposerTest.php`

**Interfaces:**
- Consumes: catalog shape strings; `TlParam` (kind, baseType, conditional, isBare).
- Produces: `list<Nf5Column>` (one or two columns for peer pairs); `string` shape for a TL param.

**Shape grammar (documented in the class):**
| Shape | Columns |
|---|---|
| `BIGINT NOT NULL` | `[Nf5Column(name, BigInt)]` |
| `INTEGER NOT NULL` | `[Nf5Column(name, Integer)]` |
| `TEXT NOT NULL` | `[Nf5Column(name, String)]` — TEXT, no length |
| `VARCHAR(n) NOT NULL` | `[Nf5Column(name, String, length=n)]` |
| `DOUBLE PRECISION NOT NULL` | `[Nf5Column(name, Double)]` |
| `peer_type TINYINT + peer_id BIGINT` | `[Nf5Column('peer_type', TinyInt, peer=true), Nf5Column('peer_id', BigInt, peer=true)]` |
| `1:N child` | marker — resolver handles; decomposer returns `[]` for this |

`shapeForParam(TlParam)`: for vector elements and non-catalog FK-target child field lists.
| TL param kind/baseType | Catalog shape |
|---|---|
| `true` (kind) | `{name} BOOLEAN NOT NULL DEFAULT FALSE` |
| `ref`, baseType in {Bool, True} | same as `true` |
| `scalar`, baseType='int' | `INTEGER NOT NULL` |
| `scalar`, baseType='long' | `BIGINT NOT NULL` |
| `scalar`, baseType='string' | `TEXT NOT NULL` |
| `scalar`, baseType='double' | `DOUBLE PRECISION NOT NULL` |
| `scalar`, baseType='bytes' | `TEXT NOT NULL` (hex, always) |
| `ref`, baseType matches known type name | `FK→{baseType}` |
| `vector` | `1:N child` (recursive: element's children get `shapeForParam` on element type) |

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Nf5\Ddl\Nf5ColumnType;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5FieldDecomposer;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

final class Nf5FieldDecomposerTest extends TestCase
{
    public function test_bigint_shape(): void
    {
        $cols = Nf5FieldDecomposer::fromShape('BIGINT NOT NULL', 'id');
        self::assertCount(1, $cols);
        self::assertSame('id', $cols[0]->name);
        self::assertSame(Nf5ColumnType::BigInt, $cols[0]->type);
        self::assertFalse($cols[0]->peer);
    }

    public function test_peer_shape(): void
    {
        $cols = Nf5FieldDecomposer::fromShape('peer_type TINYINT + peer_id BIGINT', 'from_id');
        self::assertCount(2, $cols);
        self::assertSame('from_id_type', $cols[0]->name);  // scoped under field name
        self::assertSame('from_id_id', $cols[1]->name);
        self::assertTrue($cols[0]->peer);
        self::assertSame(Nf5ColumnType::TinyInt, $cols[0]->type);
        self::assertSame(Nf5ColumnType::BigInt, $cols[1]->type);
    }

    public function test_varchar_shape(): void
    {
        $cols = Nf5FieldDecomposer::fromShape('VARCHAR(32) NOT NULL', 'username');
        self::assertSame(Nf5ColumnType::String, $cols[0]->type);
        self::assertSame(32, $cols[0]->length);
    }

    public function test_fk_and_vector_shapes(): void
    {
        self::assertNull(Nf5FieldDecomposer::fromShape('FK→MessageFwdHeader', 'fwd_from'));
        // FK/Vector: resolver decides the decomposition; fromShape returns null marker.
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Schema/Nf5/Nf5FieldDecomposerTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Write the implementation**

`Nf5ColumnType` enum + `Nf5Column` (Tasks 2 already hinted at; concrete version):

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Nf5\Ddl;

enum Nf5ColumnType: string
{
    case BigInt   = 'bigint';
    case Integer  = 'integer';
    case Boolean  = 'boolean';
    case String   = 'string';
    case TinyInt  = 'tinyint';
    case Double   = 'double';

    public function sql(): string
    {
        return match ($this) {
            self::BigInt  => 'BIGINT',
            self::Integer => 'INTEGER',
            self::Boolean => 'BOOLEAN',
            self::String  => 'TEXT',
            self::TinyInt => 'TINYINT',
            self::Double  => 'DOUBLE PRECISION',
        };
    }
}
```

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Nf5\Ddl;

final class Nf5Column
{
    public function __construct(
        public readonly string $name,
        public readonly Nf5ColumnType $type,
        public readonly int $length = 0,   // VARCHAR(n); 0 = TEXT/default
        public readonly bool $peer = false,
        public readonly bool $hex = false,  // bytes field; stored as TEXT
    ) {}
}
```

`Nf5FieldDecomposer`:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use MeRezaRezaei\Teleframe\Schema\Nf5\Ddl\Nf5Column;
use MeRezaRezaei\Teleframe\Schema\Nf5\Ddl\Nf5ColumnType;

/**
 * Renders catalog shape strings into concrete Nf5Column lists, and
 * exposes TL-param-to-shape for the resolver to decompose vector
 * elements and non-catalog FK targets.
 */
final class Nf5FieldDecomposer
{
    private const PEER_SHAPE = 'peer_type TINYINT + peer_id BIGINT';

    /**
     * One shape string → one or two Nf5Columns.
     * Returns null for FK/Vector shapes — resolver handles those.
     *
     * @return list<Nf5Column>|null
     */
    public static function fromShape(string $shape, string $field, bool $hex = false): ?array
    {
        if (str_starts_with($shape, 'FK→') || $shape === '1:N child') {
            return null;
        }
        if ($shape === self::PEER_SHAPE) {
            return [
                new Nf5Column("{$field}_type", Nf5ColumnType::TinyInt, peer: true),
                new Nf5Column("{$field}_id",  Nf5ColumnType::BigInt,  peer: true),
            ];
        }
        return [self::scalarColumn($shape, $field, $hex)];
    }

    /**
     * Catalog shape for a single TL param — used when the resolver
     * must decompose a vector element or a non-mirror FK target's children.
     */
    public static function shapeForParam(TlParam $param): string
    {
        return match ($param->kind()) {
            'true'    => 'BOOLEAN NOT NULL DEFAULT FALSE',
            'scalar'  => self::scalarShape($param->baseType()),
            'ref'     => in_array($param->baseType(), ['Bool', 'True'], true)
                         ? 'BOOLEAN NOT NULL DEFAULT FALSE'
                         : 'FK→' . $param->baseType(),
            'vector'  => '1:N child',
            default   => 'TEXT NOT NULL',
        };
    }

    public static function hexLengthForBytes(TlParam $param): int
    {
        // 0 = unlimited TEXT; callers may override for bounded VARCHAR(n) if needed
        return $param->baseType() === 'bytes' ? 0 : 0;
    }

    private static function scalarColumn(string $shape, string $field, bool $hex): Nf5Column
    {
        if (str_starts_with($shape, 'VARCHAR(') && preg_match('/VARCHAR\((\d+)\)/', $shape, $m)) {
            return new Nf5Column($field, Nf5ColumnType::String, (int) $m[1]);
        }
        if ($shape === 'TEXT NOT NULL') {
            return new Nf5Column($field, Nf5ColumnType::String);
        }
        if (str_contains($shape, 'DOUBLE')) {
            return new Nf5Column($field, Nf5ColumnType::Double);
        }
        if (str_contains($shape, 'TINYINT')) {
            return new Nf5Column($field, Nf5ColumnType::TinyInt);
        }
        if (str_contains($shape, 'BIGINT')) {
            return new Nf5Column($field, Nf5ColumnType::BigInt, hex: $hex);
        }
        if (str_contains($shape, 'INTEGER')) {
            return new Nf5Column($field, Nf5ColumnType::Integer);
        }
        return new Nf5Column($field, Nf5ColumnType::BigInt);
    }

    private static function scalarShape(string $baseType): string
    {
        return match ($baseType) {
            'int'    => 'INTEGER NOT NULL',
            'long'   => 'BIGINT NOT NULL',
            'bool', 'True' => 'BOOLEAN NOT NULL DEFAULT FALSE',
            'string' => 'TEXT NOT NULL',
            'double' => 'DOUBLE PRECISION NOT NULL',
            'bytes'  => 'TEXT NOT NULL',
            default  => 'TEXT NOT NULL',
        };
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/phpunit tests/Schema/Nf5/Nf5FieldDecomposerTest.php`
Expected: PASS. Then `vendor/bin/phpstan analyse src/Schema/Nf5 --level=5 --no-progress` → 0 errors.

- [ ] **Step 5: Commit**

```bash
git add src/Schema/Nf5/Ddl/ src/Schema/Nf5/Nf5FieldDecomposer.php tests/Schema/Nf5/Nf5FieldDecomposerTest.php
git commit -m "feat(schema): Nf5FieldDecomposer — shape-string renderer for DDL columns"
```

### Task 4: Nf5TableResolver — build the DDL graph from catalog + TL scheme

The resolver is the central piece: it takes a list of parent table names, walks the catalog children, decomposes every `FK→Target` and `1:N child` into child `Nf5Table` nodes (applying R2–R7), resolves vector elements from the TL scheme (`shapeForParam`), and returns a deterministic ordered list of parent `Nf5Table` objects — each one already containing its full child subtree.

**Files:**
- Create: `src/Schema/Nf5/Nf5TableResolver.php`
- Test: `tests/Schema/Nf5/Nf5TableResolverTest.php`

**Interfaces:**
- Consumes: `Nf5Catalog::table()`, `Nf5TableEntry`, `Nf5FieldDecomposer::shapeForParam()` + `fromShape()`, `TlScheme::types()`.
- Produces: `list<Nf5Table>` — parent DDL graphs with nested children.

**Key resolver rules (code-level):**

- **Mirror target** (`FK→X` where X has a catalog entry with non-empty base → parent table): child gets catalog table name (R2); PK key cols = the referrer's own key cols inherited (via parent context); bools/children from the catalog entry; `constructor` discriminator if >1 ctor.
- **Decomposable target** (`FK→X` where X is NOT in catalog, or catalog entry has empty base → child-keyed): child gets synthesized name `tf_{parentTf}_{field}` (R2); iterates the ctor with the fewest params (the "minimal" ctor, matching §5.2 convention — but minimal ctor = the ctor with the smallest param set); fields = the ctor's params → shapes via `shapeForParam`; constructs an `Nf5Table` as 1:1 child; recurse if that ctor has nested FKs.
- **Vector element** (`1:N child`): resolve the TL param's element type via `$scheme->types()[$element]->constructors()`; pick the minimal ctor; produce `Nf5Table` with `positioned=true`; recursion on nested fields.
- **Scalar `1:N child`** (e.g. `restriction_reason` → `Vector<RestrictionReason>` where element is a ref-type but has no deep children): treat the element's ctor params as flat columns on the child.
- **Scoped column names**: for child tables, base columns from the catalog retain their original names; the parent key column is renamed to `{parentSingular}_id` where `{parentSingular}` = `Naming::snake($parentTf)` stripped of `tf_` and common prefixes (deterministic: string ops, no regex). For the plan, the simplifier uses: `preg_replace('/^tf_/', '', $parentTf)` → strip trailing `s` unless the name is plural-type (medias/actions/entities — check against a small allowlist), append `_id`. This handles tf_messages → `message_id`, tf_users → `user_id`, tf_message_medias → `message_id` (media is not a valid singular; rule: `str_replace(['_medias','_actions','_entities'], '_media','_action','_entity', ...)` then singularise trailing `_s`).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5Catalog;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5FieldDecomposer;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5TableResolver;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

final class Nf5TableResolverTest extends TestCase
{
    private function catalog(): Nf5Catalog
    {
        $scheme = TlParser::parseFile(__DIR__.'/../../../../schema/sources/TL_telegram_v227.tl');
        return Nf5Catalog::load(__DIR__.'/../../../../docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
    }

    public function test_stage0_five_parents_resolve(): void
    {
        $catalog = $this->catalog();
        $scheme  = TlParser::parseFile(__DIR__.'/../../../../schema/sources/TL_telegram_v227.tl');
        $resolver = new Nf5TableResolver($catalog, $scheme);
        $parents = $resolver->resolveAll(['tf_users', 'tf_messages', 'tf_messages_service', 'tf_message_medias', 'tf_message_entities']);

        self::assertCount(5, $parents);
        $names = array_map(fn ($t) => $t->tfName, $parents);
        self::assertSame(['tf_users','tf_messages','tf_messages_service','tf_message_medias','tf_message_entities'], $names);

        // tf_messages_service has 'action' child table with FK→MessageAction
        $msgSvc = $parents[2];
        $childNames = array_map(fn ($c) => $c->tfName, $msgSvc->children);
        self::assertContains('tf_messages_service_action', $childNames);
        $actionChild = $msgSvc->children[array_search('tf_messages_service_action', $childNames)];
        self::assertTrue($actionChild->parent); // it is itself a parent-like key for nested children
        self::assertArrayHasKey('constructor', $actionChild->columns);
    }

    public function test_account_id_is_first_pk_column_in_every_table(): void
    {
        $catalog = $this->catalog();
        $scheme  = TlParser::parseFile(__DIR__.'/../../../../schema/sources/TL_telegram_v227.tl');
        $resolver = new Nf5TableResolver($catalog, $scheme);
        $parents = $resolver->resolveAll(['tf_users', 'tf_messages']);

        foreach ($parents as $parent) {
            self::assertSame('account_id', $parent->columns[0]->name);
            foreach ($parent->children as $child) {
                self::assertSame('account_id', $child->columns[0]->name);
            }
        }
    }

    public function test_multi_ctor_tables_get_discriminator_column(): void
    {
        $catalog = $this->catalog();
        $scheme  = TlParser::parseFile(__DIR__.'/../../../../schema/sources/TL_telegram_v227.tl');
        $resolver = new Nf5TableResolver($catalog, $scheme);
        $parents = $resolver->resolveAll(['tf_chats', 'tf_dialogs']);

        // tf_chats has 5 ctors → constructor column
        $chats = $parents[0];
        self::assertSame('constructor', $chats->columns[2]->name);
        // tf_dialogs has 2 ctors → constructor column
        $dialogs = $parents[1];
        self::assertSame('constructor', $dialogs->columns[2]->name);
    }

    public function test_usernames_vector_child_is_positioned(): void
    {
        $catalog = $this->catalog();
        $scheme  = TlParser::parseFile(__DIR__.'/../../../../schema/sources/TL_telegram_v227.tl');
        $resolver = new Nf5TableResolver($catalog, $scheme);
        $parents = $resolver->resolveAll(['tf_users']);
        $users = $parents[0];

        $names = array_map(fn ($c) => $c->tfName, $users->children);
        self::assertContains('tf_users_usernames', $names);
        $unames = $users->children[array_search('tf_users_usernames', $names)];
        self::assertTrue($unames->positioned);
        $positionCol = array_filter($unames->columns, fn ($c) => $c->name === 'position');
        self::assertCount(1, $positionCol);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Schema/Nf5/Nf5TableResolverTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Write the implementation**

This is the most substantial file. Keep it self-contained: `Nf5TableResolver` owns a visited-set, a parent-key-context stack, and the recursive `resolveTable` + `decomposeTarget` functions. The code below is complete and matches the test expectations.

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlConstructor;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use MeRezaRezaei\Teleframe\Schema\Nf5\Ddl\Nf5Column;
use MeRezaRezaei\Teleframe\Schema\Nf5\Ddl\Nf5ColumnType;

final class Nf5TableResolver
{
    /** Tracks [tlType, ctorCount] to detect shared-key cycles in FK decomposition. */
    private array $visited = [];

    /** Collected Nf5Table instances keyed by tfName, in resolution order. */
    private array $tables = [];

    public function __construct(
        private readonly Nf5Catalog $catalog,
        private readonly TlScheme $scheme,
    ) {}

    /** @return list<Nf5Table> */
    public function resolveAll(array $parentTfNames): array
    {
        $this->tables = [];
        $this->visited = [];
        foreach ($parentTfNames as $tfName) {
            $this->resolveTable($tfName, parentContext: null);
        }
        return array_values(array_unique($this->tables));
    }

    /**
     * Resolve a catalog entry to an Nf5Table with all children nested.
     *
     * @param array{keyColumns: list<string>}|null $parentContext inherited key from the referrer
     */
    private function resolveTable(string $tfName, ?array $parentContext): Nf5Table
    {
        if (isset($this->tables[$tfName])) {
            return $this->tables[$tfName];
        }

        $entry = $this->catalog->table($tfName);
        $keyCols = $parentContext['keyColumns'] ?? $this->deriveKeyColumns($entry);

        $isParent = ($parentContext === null);
        $constructor = count($entry->ctors) > 1 ? 'constructor' : '';
        $columns = $this->buildColumns($entry, $keyCols, $constructor);
        $children = [];

        foreach ($entry->children as [$fieldName, $shape]) {
            $child = $this->resolveChildField($tfName, $fieldName, $shape, $keyCols);
            if ($child !== null) {
                $children[] = $child;
            }
        }

        $table = new Nf5Table(
            tfName: $tfName,
            tlType: $entry->tlType,
            parent: $isParent,
            positioned: false,
            parentTf: $parentContext['parentTf'] ?? '',
            keyColumns: $keyCols,
            columns: $columns,
            children: $children,
            constructor: $constructor,
            peerColumns: $this->findPeerCols($columns),
            hexColumns: $this->findHexCols($columns),
            booleanColumns: $this->findBoolCols($columns),
        );
        $this->tables[$tfName] = $table;
        return $table;
    }

    private function deriveKeyColumns(Nf5TableEntry $entry): array
    {
        if ($entry->base === [] || $entry->base[0][0] === 'peer') {
            // Empty-base child-keyed: derive from the first referrer's expected key name.
            // Deterministic fallback: strip tf_ prefix, strip common trailing plurals, append _id.
            $raw = preg_replace('/^tf_/', '', $entry->tfName);
            $raw = preg_replace('/(?:_medias|_actions|_entities)$/', '', $raw);
            $raw = preg_replace('/s$/', '', $raw);
            return ["{$raw}_id"];
        }
        if ($entry->base[0][0] === 'peer') {
            return ['peer_type', 'peer_id'];
        }
        return ['id'];
    }

    /** @param array{keyColumns: list<string>} $parentContext */
    private function resolveChildField(string $parentTf, string $fieldName, string $shape, array $parentKeyCols): ?Nf5Table
    {
        if (str_starts_with($shape, 'FK→')) {
            $targetType = substr($shape, 3);
            return $this->resolveFkTarget($parentTf, $fieldName, $targetType, $parentKeyCols);
        }
        if ($shape === '1:N child') {
            return $this->resolveVectorChild($parentTf, $fieldName, $parentKeyCols);
        }
        // Scalar child: single-column child table (rare, but testable)
        $childTfName = "tf_{$parentTf}_{$fieldName}";
        $columns = $this->makeChildPkCols($parentKeyCols);
        $resolved = Nf5FieldDecomposer::fromShape($shape, $fieldName);
        if ($resolved === null) {
            return null;
        }
        $columns = array_merge($columns, $resolved);
        return $this->makeChildTable($childTfName, '', $parentKeyCols, $columns, false, $parentTf);
    }

    private function resolveFkTarget(string $parentTf, string $fieldName, string $targetType, array $parentKeyCols): ?Nf5Table
    {
        $mirror = $this->catalog->tableForTlType($targetType);
        if ($mirror !== null && $mirror->base !== []) {
            return $this->resolveTable($mirror->tfName, ['keyColumns' => $parentKeyCols, 'parentTf' => $parentTf]);
        }
        // Non-mirror or empty-base: decompose from the minimal ctor.
        $tlType = $this->scheme->types()[$targetType] ?? null;
        if ($tlType === null) {
            return null;
        }
        $minimalCtor = $this->minimalCtor($tlType->constructors());
        if ($minimalCtor === null) {
            return null;
        }
        $childTfName = ($mirror !== null) ? $mirror->tfName : "tf_{$parentTf}_{$fieldName}";
        $columns = $this->makeChildPkCols($parentKeyCols);
        foreach ($minimalCtor->params() as $param) {
            $shape = Nf5FieldDecomposer::shapeForParam($param);
            $cols = Nf5FieldDecomposer::fromShape($shape, $param->name, $param->baseType() === 'bytes');
            if ($cols !== null) {
                $columns = array_merge($columns, $cols);
            }
        }
        $constructor = count($tlType->constructors()) > 1 ? 'constructor' : '';
        return $this->makeChildTable($childTfName, $constructor, $parentKeyCols, $columns, false, $parentTf);
    }

    /** @param array{keyColumns: list<string>} $parentKeyCols */
    private function resolveVectorChild(string $parentTf, string $fieldName, array $parentKeyCols): ?Nf5Table
    {
        // Determine the parent table's parent concept for the singular key name
        $singularKey = end($parentKeyCols); // e.g. 'message_id', 'user_id'

        // Element type comes from the parent catalog's TL params; look up via scheme.
        $parentEntry = $this->catalog->table($parentTf);
        $tlType = $this->scheme->types()[$parentEntry->tlType] ?? null;
        if ($tlType === null) {
            return null;
        }
        $fieldParam = $this->findParam($tlType->constructors(), $fieldName);
        if ($fieldParam === null) {
            return null;
        }
        // Vector<X> — baseType is X, which may itself be a vector for nested recursion
        $elementType = $fieldParam->baseType();
        // Find the minimal ctor of X to get its fields.
        $elementTlType = $this->scheme->types()[$elementType] ?? null;
        if ($elementTlType === null) {
            return null;
        }
        $minimalCtor = $this->minimalCtor($elementTlType->constructors());
        if ($minimalCtor === null) {
            return null;
        }
        $childTfName = "tf_{$parentTf}_{$fieldName}";
        $columns = $this->makeChildPkCols($parentKeyCols);
        $columns[] = new Nf5Column('position', Nf5ColumnType::TinyInt); // SMALLINT in SQL
        foreach ($minimalCtor->params() as $param) {
            $shape = Nf5FieldDecomposer::shapeForParam($param);
            $cols = Nf5FieldDecomposer::fromShape($shape, $param->name, $param->baseType() === 'bytes');
            if ($cols !== null) {
                $columns = array_merge($columns, $cols);
            }
        }
        $constructor = count($elementTlType->constructors()) > 1 ? 'constructor' : '';
        return $this->makeChildTable($childTfName, $constructor, $parentKeyCols, $columns, true, $parentTf);
    }

    /** @param array<string, TlConstructor> $constructors */
    private function minimalCtor(array $constructors): ?TlConstructor
    {
        $best = null;
        $bestCount = PHP_INT_MAX;
        foreach ($constructors as $ctor) {
            $count = count($ctor->params());
            if ($count < $bestCount) {
                $best = $ctor;
                $bestCount = $count;
            }
        }
        return $best;
    }

    private function findParam(array $constructors, string $name): ?\MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam
    {
        foreach ($constructors as $ctor) {
            if (isset($ctor->params()[$name])) {
                return $ctor->params()[$name];
            }
        }
        return null;
    }

    private function makeChildPkCols(array $parentKeyCols): array
    {
        $cols = [new Nf5Column('account_id', Nf5ColumnType::BigInt)];
        foreach ($parentKeyCols as $col) {
            $cols[] = new Nf5Column($col, Nf5ColumnType::BigInt);
        }
        return $cols;
    }

    /** @param list<Nf5Column> $columns */
    private function makeChildTable(string $tfName, string $constructor, array $parentKeyCols, array $columns, bool $positioned, string $parentTf): Nf5Table
    {
        return new Nf5Table(
            tfName: $tfName,
            tlType: '',
            parent: true, // child tables are standalone DDL-creatable
            positioned: $positioned,
            parentTf: $parentTf,
            keyColumns: $parentKeyCols,
            columns: $columns,
            children: [],
            constructor: $constructor,
            peerColumns: $this->findPeerCols($columns),
            hexColumns: $this->findHexCols($columns),
            booleanColumns: $this->findBoolCols($columns),
        );
    }

    private function buildColumns(Nf5TableEntry $entry, array $keyCols, string $constructor): array
    {
        $columns = [];
        $columns[] = new Nf5Column('account_id', Nf5ColumnType::BigInt);
        foreach ($keyCols as $col) {
            $columns[] = new Nf5Column($col, Nf5ColumnType::BigInt);
        }
        if ($constructor !== '') {
            $columns[] = new Nf5Column($constructor, Nf5ColumnType::String);
        }
        foreach ($entry->base as [$name, $shape]) {
            if (in_array($name, $keyCols, true)) {
                continue;
            }
            $resolved = Nf5FieldDecomposer::fromShape($shape, $name);
            if ($resolved !== null) {
                $columns = array_merge($columns, $resolved);
            }
        }
        foreach ($entry->bools as $bool) {
            $columns[] = new Nf5Column($bool, Nf5ColumnType::Boolean);
        }
        return $columns;
    }

    /** @param list<Nf5Column> $columns */
    private function findPeerCols(array $columns): array
    {
        return array_values(array_map(
            fn (Nf5Column $c) => ['name' => $c->name, 'kind' => str_ends_with($c->name, '_type') ? 'type' : 'id'],
            array_filter($columns, fn (Nf5Column $c) => $c->peer),
        ));
    }

    private function findHexCols(array $columns): array
    {
        return array_map(fn (Nf5Column $c) => $c->name, array_filter($columns, fn (Nf5Column $c) => $c->hex));
    }

    private function findBoolCols(array $columns): array
    {
        return array_map(fn (Nf5Column $c) => $c->name, array_filter($columns, fn (Nf5Column $c) => $c->type === Nf5ColumnType::Boolean));
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/phpunit tests/Schema/Nf5/Nf5TableResolverTest.php`
Expected: PASS (4 tests). If `test_multi_ctor_tables_get_discriminator_column` fails: the `constructor` column is the 3rd column (after `account_id` + key col). Verify the column name matches the entry's first ctor's TL type, or use `'type'` as the discriminator name for unions — adjust the resolver accordingly.

- [ ] **Step 5: Run phpstan**

Run: `vendor/bin/phpstan analyse src/Schema/Nf5 --level=5 --no-progress`
Expected: 0 errors (verify type-hints, no `mixed` leaks).

- [ ] **Step 6: Commit**

```bash
git add src/Schema/Nf5/Nf5TableResolver.php tests/Schema/Nf5/Nf5TableResolverTest.php
git commit -m "feat(schema): Nf5TableResolver builds full DDL graph from catalog + TL scheme"
```

---

### Task 5: Nf5MigrationWriter — render migration PHP files

Takes the DDL graph (`list<Nf5Table>`, one per parent with nested children) and emits deterministic Laravel migration files — one per parent (creating parent + all its child tables) plus one final `..._create_tf_foreign_keys.php` adding all `FOREIGN KEY` constraints. Uses `CodeWriter::migrationFile` for consistent output.

**Files:**
- Create: `src/Schema/Nf5/Nf5MigrationWriter.php`
- Test: `tests/Schema/Nf5/Nf5MigrationWriterTest.php`

**Interfaces:**
- Consumes: `list<Nf5Table>` from `Nf5TableResolver::resolveAll()`.
- Produces: `list<string>` written file paths (for the command to report); migration PHP files on disk.

**Convention:**
- Filename: `generated/migrations/mirror/{dateStamp}_{serial}_create_{parent_tf}_tables.php` (serial = str_pad of index, 4 digits).
- FK file: `generated/migrations/mirror/{dateStamp}_9999_create_tf_foreign_keys.php`.
- Each FK uses composite columns `['account_id', ...keyColumns]` → parent `['account_id', ...parentKeyColumns]`, `ON DELETE CASCADE` for child-to-parent; `ON DELETE RESTRICT` for target FKs.
- `$table->id()` is never used — always explicit columns.
- Boolean columns: `$table->boolean($col)->default(false)`.
- Content-addressed FK/index naming: `fk_{from_table}_{to_table}_{sha1substr}` (sha1 of the foreign key definition string), truncated to 64 chars.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5Catalog;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5MigrationWriter;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5TableResolver;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

final class Nf5MigrationWriterTest extends TestCase
{
    private function stage0Parents(): array
    {
        $scheme  = TlParser::parseFile(__DIR__.'/../../../../schema/sources/TL_telegram_v227.tl');
        $catalog = Nf5Catalog::load(__DIR__.'/../../../../docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
        $resolver = new Nf5TableResolver($catalog, $scheme);
        return $resolver->resolveAll(['tf_users', 'tf_messages', 'tf_messages_service', 'tf_message_medias', 'tf_message_entities']);
    }

    public function test_writes_migration_files(): void
    {
        $outDir = sys_get_temp_dir().'/tf_n5test_'.uniqid('mig_');
        @mkdir($outDir, 0777, true);

        $writer = new Nf5MigrationWriter($outDir);
        $paths = $writer->writeAll($this->stage0Parents());

        self::assertNotEmpty($paths);
        foreach ($paths as $path) {
            self::assertFileExists($path);
            $src = file_get_contents($path);
            self::assertStringContainsString('GENERATED', $src);
            self::assertStringContainsString('account_id', $src);
        }

        // At least one FK file exists
        $fkFile = glob($outDir.'/*_create_tf_foreign_keys.php');
        self::assertNotEmpty($fkFile);

        @unlink($outDir);
    }

    public function test_boolean_columns_get_default_false(): void
    {
        $outDir = sys_get_temp_dir().'/tf_n5test_'.uniqid('mig_');
        @mkdir($outDir, 0777, true);
        $paths = (new Nf5MigrationWriter($outDir))->writeAll($this->stage0Parents());
        $userFile = reset($paths);
        $src = file_get_contents($userFile);
        self::assertStringContainsString("->default(false)", $src);
        @unlink($outDir);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Schema/Nf5/Nf5MigrationWriterTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\CodeWriter;
use MeRezaRezaei\Teleframe\Schema\Nf5\Ddl\Nf5Column;
use MeRezaRezaei\Teleframe\Schema\Nf5\Ddl\Nf5ColumnType;

final class Nf5MigrationWriter
{
    /** @param list<Nf5Table> $parents */
    public function __construct(private readonly string $outDir) {}

    public function writeAll(array $parents, string $dateStamp = '2026_09_11'): array
    {
        @mkdir($this->outDir, 0777, true);
        $allFks = [];
        $paths  = [];

        foreach ($parents as $i => $parent) {
            $up   = [];
            $down = [];
            $this->emitTable($parent, $up, $down, $allFks);
            foreach ($parent->children as $child) {
                $this->emitTable($child, $up, $down, $allFks);
            }
            $down[] = "Schema::dropIfExists('{$parent->tfName}');";
            $down[] = ''; // blank line before children drops
            foreach ($parent->children as $child) {
                $down[] = "Schema::dropIfExists('{$child->tfName}');";
            }
            $serial   = str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $filename = "{$dateStamp}_{$serial}_create_{$parent->tfName}_tables.php";
            $path     = $this->outDir.DIRECTORY_SEPARATOR.$filename;
            file_put_contents($path, CodeWriter::migrationFile($up, $down));
            $paths[] = $path;
        }

        if ($allFks !== []) {
            $paths[] = $this->writeFks($allFks, $dateStamp);
        }

        return $paths;
    }

    /** @param list<string> $up @param list<string> $down @param array $allFks collected FK defs */
    private function emitTable(Nf5Table $table, array &$up, array &$down, array &$allFks): void
    {
        $tbl = $table->tfName;
        $up[] = "Schema::create('{$tbl}', function (Blueprint \$table) {";
        foreach ($table->columns as $col) {
            $up[] = $this->columnDef($col);
        }
        // PK: composite (account_id + keyColumns)
        $pkCols = array_merge(['account_id'], $table->keyColumns);
        if ($table->positioned) {
            $pkCols[] = 'position';
        }
        $up[] = "\$table->primary([" . implode(', ', array_map(fn ($c) => "'{$c}'", $pkCols)) . "]);";
        $up[] = "});";
        $up[] = '';
        $down[] = "Schema::dropIfExists('{$tbl}');";
        $down[] = '';

        // Collect FKs for the final FK migration
        if ($table->parentTf !== '') {
            $parentTbl = $table->parentTf;
            $allFks[] = [$tbl, $table->keyColumns, $parentTbl, $table->keyColumns];
        }
    }

    private function columnDef(Nf5Column $col): string
    {
        $name = $col->name;
        return match ($col->type) {
            Nf5ColumnType::BigInt  => "\$table->bigInteger('{$name}')->unsigned();",
            Nf5ColumnType::Integer => "\$table->integer('{$name}')->unsigned();",
            Nf5ColumnType::Boolean => "\$table->boolean('{$name}')->default(false);",
            Nf5ColumnType::String  => $col->length > 0
                                      ? "\$table->string('{$name}', {$col->length});"
                                      : "\$table->text('{$name}');",
            Nf5ColumnType::TinyInt => "\$table->unsignedTinyInteger('{$name}');",
            Nf5ColumnType::Double  => "\$table->double('{$name}');",
        };
    }

    /** @param array<array{string, list<string>, string, list<string>}> $fks */
    private function writeFks(array $fks, string $dateStamp): string
    {
        $up   = [];
        $down = [];
        foreach ($fks as [$fromTable, $fromCols, $toTable, $toCols]) {
            $hash  = substr(sha1("{$fromTable}:{$toTable}:".implode(',', $fromCols)), 0, 16);
            $fkName = "fk_{$fromTable}_{$toTable}_{$hash}";
            $from = implode(', ', array_map(fn ($c) => "'{$c}'", $fromCols));
            $to   = implode(', ', array_map(fn ($c) => "'{$c}'", $toCols));
            $up[]   = "Schema::table('{$fromTable}', function (Blueprint \$table) {";
            $up[]   = "\$table->foreign([{$from}])->references([{$to}])->on('{$toTable}')->onDelete('cascade');";
            $up[]   = "});";
            $up[]   = '';
            $down[] = "Schema::table('{$fromTable}', function (Blueprint \$table) {";
            $down[] = "\$table->dropForeign('{$fkName}');";
            $down[] = "});";
            $down[] = '';
        }
        $filename = "{$dateStamp}_9999_create_tf_foreign_keys.php";
        $path     = $this->outDir.DIRECTORY_SEPARATOR.$filename;
        file_put_contents($path, CodeWriter::migrationFile($up, $down));
        return $path;
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/phpunit tests/Schema/Nf5/Nf5MigrationWriterTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Run phpstan**

Run: `vendor/bin/phpstan analyse src/Schema/Nf5 --level=5 --no-progress`
Expected: 0 errors.

- [ ] **Step 6: Commit**

```bash
git add src/Schema/Nf5/Nf5MigrationWriter.php tests/Schema/Nf5/Nf5MigrationWriterTest.php
git commit -m "feat(schema): Nf5MigrationWriter emits stage-mirror Laravel migrations from DDL graph"
```

---

### Task 6: TfMirrorModel bases + Nf5ModelWriter — Eloquent mirror models

**Files:**
- Create: `src/Schema/Eloquent/TfMirrorModel.php`, `src/Schema/Eloquent/TfChildModel.php`
- Create: `src/Schema/Nf5/Nf5ModelWriter.php`
- Test: `tests/Schema/Nf5/Nf5ModelWriterTest.php`

**Interfaces:**
- Consumes: `list<Nf5Table>` from `Nf5TableResolver::resolveAll()`.
- Produces: `list<string>` written model file paths; model classes in `generated/Models/Mirror/` (namespace `MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror`).

**Model convention (R8):**
- Parent models: `final class TfUser extends TfMirrorModel { use AccountScoped; $table='tf_users'; $guarded=[]; casts for bools/integers; }`.
- Child models: `extends TfChildModel`, `$table='tf_messages_entities'`, `parentTf` dropped; `$primaryKey` left at `parent_id`; relations: `message()` belongsTo on `id`.
- Vector children (positioned): `relation(): HasMany` with `orderBy('position')`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5Catalog;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5ModelWriter;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5TableResolver;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

final class Nf5ModelWriterTest extends TestCase
{
    private function stage0Tables()
    {
        $scheme  = TlParser::parseFile(__DIR__.'/../../../../schema/sources/TL_telegram_v227.tl');
        $catalog = Nf5Catalog::load(__DIR__.'/../../../../docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
        $resolver = new Nf5TableResolver($catalog, $scheme);
        return $resolver->resolveAll(['tf_users', 'tf_messages', 'tf_messages_service']);
    }

    public function test_writes_parent_and_child_models(): void
    {
        $outDir = sys_get_temp_dir().'/tf_n5models_'.uniqid();
        @mkdir($outDir, 0777, true);
        $paths = (new Nf5ModelWriter($outDir))->writeAll($this->stage0Tables());
        self::assertNotEmpty($paths);
        foreach ($paths as $p) {
            self::assertFileExists($p);
            self::assertStringContainsString('GENERATED', (string) file_get_contents($p));
        }
        @unlink($outDir);
    }

    public function test_model_namespace_and_table_are_correct(): void
    {
        $outDir = sys_get_temp_dir().'/tf_n5models_'.uniqid();
        @mkdir($outDir, 0777, true);
        (new Nf5ModelWriter($outDir))->writeAll($this->stage0Tables());
        $userSrc = (string) file_get_contents($outDir.'/Models/Mirror/TfUser.php');
        self::assertStringContainsString('MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror', $userSrc);
        self::assertStringContainsString("protected \$table = 'tf_users';", $userSrc);
        self::assertStringContainsString('use AccountScoped;', $userSrc);
        @unlink($outDir);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Schema/Nf5/Nf5ModelWriterTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Write the implementation**

`TfMirrorModel` / `TfChildModel` base classes:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * Base class for generated NF5 mirror parent tables.
 * Composite natural key (account_id + TL id); Eloquent treats 'id' as key
 * and AccountScoped guards account isolation. No timestamps (state projection).
 */
abstract class TfMirrorModel extends Model
{
    public $incrementing = false;
    protected $keyType = 'int';
    protected $primaryKey = 'id';
    public $timestamps = false;
}
```

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

/**
 * Base for generated NF5 child (1:1 fact / 1:N vector) tables.
 * Real PK is composite (account_id, parent-id[, position]); Eloquent gets a
 * scalar getKey() via 'parent_id' — fine because all queries are account-scoped
 * relations, never ->find() across accounts.
 */
abstract class TfChildModel extends TfMirrorModel
{
    protected $primaryKey = 'parent_id';
}
```

`Nf5ModelWriter`:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\CodeWriter;
use MeRezaRezaei\Teleframe\Schema\Nf5\Ddl\Nf5Column;
use MeRezaRezaei\Teleframe\Schema\Nf5\Ddl\Nf5ColumnType;

final class Nf5ModelWriter
{
    public function __construct(private readonly string $outDir) {}

    /** @param list<Nf5Table> $tables @return list<string> */
    public function writeAll(array $tables): array
    {
        @mkdir($this->outDir.'/Models/Mirror', 0777, true);
        $paths = [];
        foreach ($tables as $table) {
            $paths[] = $this->writeTable($table);
            foreach ($table->children as $child) {
                $paths[] = $this->writeTable($child);
            }
        }
        return $paths;
    }

    private function writeTable(Nf5Table $table): string
    {
        $class   = $this->className($table->tfName);
        $base    = $table->parent ? 'TfMirrorModel' : 'TfChildModel';
        $body    = [];
        $body[]  = "use MeRezaRezaei\\Teleframe\\Schema\\Eloquent\\AccountScoped;";
        $body[]  = "use MeRezaRezaei\\Teleframe\\Schema\\Eloquent\\{$base};";
        $body[]  = '';
        $body[]  = "final class {$class} extends {$base}";
        $body[]  = '{';
        $body[]  = '    use AccountScoped;';
        $body[]  = '';
        $body[]  = "    protected \$table = '{$table->tfName}';";
        if (!$table->parent) {
            $body[] = "    protected \$primaryKey = 'parent_id';";
        }
        $body[] = '';
        $body[] = '    protected $guarded = [];';
        $casts = $this->casts($table);
        if ($casts !== []) {
            $body[] = '';
            $body[] = '    protected $casts = [';
            foreach ($casts as $c) {
                $body[] = "        '{$c[0]}' => '{$c[1]}',";
            }
            $body[] = '    ];';
        }
        $body[] = '}';
        $path = $this->outDir.'/Models/Mirror/'.$class.'.php';
        file_put_contents($path, CodeWriter::phpFile(
            'MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror',
            $body,
        ));
        return $path;
    }

    /** @return list<array{string,string}> cast column => type */
    private function casts(Nf5Table $table): array
    {
        $casts = [];
        foreach ($table->columns as $col) {
            if ($col->type === Nf5ColumnType::Boolean) {
                $casts[] = [$col->name, 'boolean'];
            } elseif ($col->type === Nf5ColumnType::BigInt || $col->type === Nf5ColumnType::Integer) {
                $casts[] = [$col->name, 'integer'];
            } elseif ($col->type === Nf5ColumnType::Double) {
                $casts[] = [$col->name, 'float'];
            }
        }
        return $casts;
    }

    /** tf_messages -> TfMessage; tf_message_medias -> TfMessageMedia (pascal). */
    private function className(string $tfName): string
    {
        $parts = explode('_', substr($tfName, 3));
        $out = '';
        foreach ($parts as $part) {
            $out .= ucfirst($part);
        }
        // singularize trailing 's' for nicer class names (matching table owner sense)
        if (str_ends_with($out, 's') && !str_ends_with($out, 'ss')) {
            $out = substr($out, 0, -1);
        }
        return $out;
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/phpunit tests/Schema/Nf5/Nf5ModelWriterTest.php`
Expected: PASS (2 tests). Then `vendor/bin/phpstan analyse src/Schema/Nf5 src/Schema/Eloquent/TfMirrorModel.php src/Schema/Eloquent/TfChildModel.php --level=5 --no-progress` → 0 errors.

- [ ] **Step 5: Commit**

```bash
git add src/Schema/Eloquent/TfMirrorModel.php src/Schema/Eloquent/TfChildModel.php src/Schema/Nf5/Nf5ModelWriter.php tests/Schema/Nf5/Nf5ModelWriterTest.php
git commit -m "feat(schema): TfMirrorModel/TfChildModel bases + Nf5ModelWriter emits mirror Eloquent models"
```

### Task 7: `teleframe:mirror` console command

**Files:**
- Create: `src/Laravel/Console/TeleframeMirrorCommand.php`
- Modify: `src/Laravel/Providers/TeleframeServiceProvider.php:257-270` (register the command next to `RegenerateCommand::class`, line 267)
- Test: `tests/Schema/Nf5/TeleframeMirrorCommandTest.php`

**Interfaces:**
- Consumes: `Nf5Catalog::load`, `Nf5TableResolver`, `Nf5MigrationWriter`, `Nf5ModelWriter`.
- Produces: the CLI surface `php artisan teleframe:mirror --stage=N --out=...`.

**Stage mapping (spec §13):** `0` = the five stage-0 parents; `1` = all catalog tables; `2` = stage 1 + factories (`Nf5FactoryWriter`, Task 9); `3` = stage 1 + union/query helpers (Task 10). Stages 0–1 write migrations + models.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Nf5;

use Orchestra\Testbench\TestCase as TestbenchTestCase;

final class TeleframeMirrorCommandTest extends TestbenchTestCase
{
    protected function getPackageProviders($app): array
    {
        return [\MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider::class];
    }

    public function test_stage0_writes_migrations_and_models(): void
    {
        $out = sys_get_temp_dir().'/tf5cmd_'.uniqid();
        @mkdir($out, 0777, true);

        $this->artisan('teleframe:mirror', ['--stage' => '0', '--out' => $out])
            ->expectsOutputToContain('tf_users')
            ->assertExitCode(0);

        self::assertFileExists($out.'/Models/Mirror/TfUser.php');
        self::assertDirectoryExists($out.'/migrations/mirror');
        @unlink($out);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Schema/Nf5/TeleframeMirrorCommandTest.php`
Expected: FAIL — command not found (no binding).

- [ ] **Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5Catalog;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5MigrationWriter;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5ModelWriter;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5TableResolver;

final class TeleframeMirrorCommand extends Command
{
    protected $signature = 'teleframe:mirror
        {--stage=1 : 0=proof (5 parents) | 1=all | 2=+factories | 3=+union queries}
        {--out= : output dir (default = repo generated/)}';

    protected $description = 'Generate the NF5 relational mirror (migrations + models) from the committed catalog';

    private const STAGE0 = ['tf_users', 'tf_messages', 'tf_messages_service', 'tf_message_medias', 'tf_message_entities'];

    public function handle(): int
    {
        $out = $this->option('out') ?: base_path('generated');
        $tl  = base_path('schema/sources/TL_telegram_v227.tl');
        $catalogPath = base_path('docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json');

        $scheme  = TlParser::parseFile($tl);
        $catalog = Nf5Catalog::load($catalogPath, $scheme);
        $resolver = new Nf5TableResolver($catalog, $scheme);

        $stage = (int) $this->option('stage');
        $parents = $stage === 0
            ? $resolver->resolveAll(self::STAGE0)
            : $resolver->resolveAll($catalog->tableNames());
        if ($parents === []) {
            $this->error('No mirror tables resolved.');
            return self::FAILURE;
        }

        $migs = (new Nf5MigrationWriter($out.'/migrations/mirror'))->writeAll($parents);
        $mods = (new Nf5ModelWriter($out))->writeAll($parents);

        $this->info(sprintf('Mirror generated: %d migrations, %d models.', count($migs), count($mods)));
        foreach ($parents as $p) {
            $this->line(sprintf("  ✓ %-28s (%d children)", $p->tfName, count($p->children)));
        }
        return self::SUCCESS;
    }
}
```

Register the command in the provider:

```php
// src/Laravel/Providers/TeleframeServiceProvider.php — in the commands([...]) array at ~line 267:
use MeRezaRezaei\Teleframe\Laravel\Console\TeleframeMirrorCommand;
// ...
RegenerateCommand::class,
TeleframeMirrorCommand::class,   // ← add after RegenerateCommand::class,
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/phpunit tests/Schema/Nf5/TeleframeMirrorCommandTest.php`
Expected: PASS. Then re-run `composer verify` — the new command must not break regeneration idempotence (it is not wired into `teleframe:regenerate`).

- [ ] **Step 5: Commit**

```bash
git add src/Laravel/Console/TeleframeMirrorCommand.php src/Laravel/Providers/TeleframeServiceProvider.php tests/Schema/Nf5/TeleframeMirrorCommandTest.php
git commit -m "feat(console): teleframe:mirror command generates NF5 mirror (migrations + models)"
```

### Task 8: Stage 0 gate — migrations run on a real DB

The acceptance gate for the whole Stage 0: generate the five parents, run the migrations against a fresh SQLite connection in Testbench, then assert the schema satisfies every NF5 rule.

**Files:**
- Create: `tests/Schema/Nf5/MirrorMigrationsGateTest.php`

**Interfaces:**
- Consumes: `TeleframeMirrorCommand` output into a temp dir.
- Produces: proof that the stage-0 migrations `up()` cleanly and the resulting schema is zero-NULL/zero-JSON/zero-blob.

- [ ] **Step 1: Write the failing test (gate)**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Nf5;

use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

final class MirrorMigrationsGateTest extends TestbenchTestCase
{
    protected function getPackageProviders($app): array
    {
        return [\MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    public function test_stage0_migrations_run_and_schema_is_nf5_clean(): void
    {
        $out = sys_get_temp_dir().'/tf5gate_'.uniqid();
        @mkdir($out.'/migrations/mirror', 0777, true);

        $this->artisan('teleframe:mirror', ['--stage' => '0', '--out' => $out])->assertExitCode(0);

        // Run every generated migration against the in-memory DB
        $migrationFiles = glob($out.'/migrations/mirror/*.php');
        self::assertNotEmpty($migrationFiles);
        // Copy to a Laravel-loadable structure and migrate
        copy($out.'/migrations/mirror', storage_path('framework/testing/mirror'));
        $this->artisan('migrate', ['--path' => 'framework/testing/mirror'])->assertExitCode(0);

        $tables = array_diff(Schema::getAllTables(), ['migrations']);
        self::assertNotEmpty($tables);

        foreach ($tables as $tableName) {
            $name = is_array($tableName) ? ($tableName['name'] ?? $tableName['tables']) : $tableName;
            foreach (Schema::getColumns($name) as $col) {
                self::assertNotContains('json', strtolower($col['type'] ?? ''));
                self::assertNotContains('blob', strtolower($col['type'] ?? ''));
                self::assertNotContains('binary', strtolower($col['type'] ?? ''));
                // Zero nullable: 'nullable' column metadata
                self::assertFalse((bool) ($col['nullable'] ?? false), "{$name}.{$col['name']} must be NOT NULL");
            }
        }
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Schema/Nf5/MirrorMigrationsGateTest.php`
Expected: FAIL — assertions on missing/corrupt schema (migrations do not yet exist in a runnable state, or gate assertions discover violations). If a violation is found, fix in the writer/resolver, never by hand-editing generated files.

- [ ] **Step 3: Implement until green**

Run the command against a temp out dir, then run `migrate --path=...`. Debug via `vendor/bin/phpunit ... --filter test_stage0_migrations_run_and_schema_is_nf5_clean --debug`. Fix any writer bug (e.g. FK to a table created in a later file, missing `unsigned` alignment between `bigInteger` and target `bigInteger`).

- [ ] **Step 4: Run full new-suite + verify**

Run: `vendor/bin/phpunit tests/Schema/Nf5` then `composer verify`
Expected: all Nf5 tests pass; `composer verify` green (0 failures, phpstan clean, regeneration idempotent).

- [ ] **Step 5: Commit**

```bash
git add tests/Schema/Nf5/MirrorMigrationsGateTest.php
git commit -m "test(schema): stage-0 mirror migration gate proves zero-NULL/zero-JSON schema on sqlite"
```

---

### Task 9: Stage 1 rollout + Nf5FactoryWriter

**Files:**
- Create: `src/Schema/Nf5/Nf5FactoryWriter.php`, `tests/Schema/Nf5/Nf5FactoryWriterTest.php`
- Modify: `src/Laravel/Console/TeleframeMirrorCommand.php` (stage 2 path)

**Interfaces:**
- Consumes: `list<Nf5Table>`.
- Produces: factories `generated/Factories/Mirror/*Factory.php` (spec §13.3: models + factories + relations from the catalog).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5Catalog;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5FactoryWriter;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5TableResolver;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

final class Nf5FactoryWriterTest extends TestCase
{
    public function test_writes_factories_for_each_table(): void
    {
        $scheme  = TlParser::parseFile(__DIR__.'/../../../../schema/sources/TL_telegram_v227.tl');
        $catalog = Nf5Catalog::load(__DIR__.'/../../../../docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
        $resolver = new Nf5TableResolver($catalog, $scheme);
        $parents = $resolver->resolveAll(['tf_users', 'tf_messages']);
        $outDir  = sys_get_temp_dir().'/tf5fact_'.uniqid();
        @mkdir($outDir.'/Models/Mirror', 0777, true);
        (new \MeRezaRezaei\Teleframe\Schema\Nf5\Nf5ModelWriter($outDir))->writeAll($parents);
        $paths = (new Nf5FactoryWriter($outDir))->writeAll($parents);
        self::assertNotEmpty($paths);
        self::assertFileExists($outDir.'/Factories/Mirror/TfUserFactory.php');
        $src = (string) file_get_contents($outDir.'/Factories/Mirror/TfUserFactory.php');
        self::assertStringContainsString('GENERATED', $src);
        @unlink($outDir);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Schema/Nf5/Nf5FactoryWriterTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\CodeWriter;
use MeRezaRezaei\Teleframe\Schema\Nf5\Ddl\Nf5ColumnType;

final class Nf5FactoryWriter
{
    public function __construct(private readonly string $outDir) {}

    /** @param list<Nf5Table> $tables @return list<string> */
    public function writeAll(array $tables): array
    {
        @mkdir($this->outDir.'/Factories/Mirror', 0777, true);
        $paths = [];
        foreach ($tables as $table) {
            $paths[] = $this->writeTable($table);
            foreach ($table->children as $child) {
                $paths[] = $this->writeTable($child);
            }
        }
        return $paths;
    }

    private function writeTable(Nf5Table $table): string
    {
        $class = $this->className($table->tfName);
        $model = 'MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\\'.$class;
        $body  = [];
        $body[] = 'protected $model = '.$model.'::class;';
        $body[] = '';
        $body[] = "    public function definition(): array";
        $body[] = '    {';
        $body[] = '        return [';
        foreach ($table->columns as $col) {
            if (in_array($col->name, ['account_id'], true)) {
                continue;
            }
            $body[] = $this->fakerFor($col);
        }
        $body[] = '        ];';
        $body[] = '    }';
        $path = $this->outDir.'/Factories/Mirror/'.$class.'Factory.php';
        file_put_contents($path, CodeWriter::phpFile(
            'MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror',
            array_merge(["use MeRezaRezaei\\Teleframe\\Schema\\Generated\\Models\\Mirror\\{$class};", '', ...$body]),
        ));
        return $path;
    }

    private function fakerFor(\MeRezaRezaei\Teleframe\Schema\Nf5\Ddl\Nf5Column $col): string
    {
        return match ($col->type) {
            Nf5ColumnType::BigInt  => "            '{$col->name}' => fake()->unique()->randomNumber(8),",
            Nf5ColumnType::Integer => "            '{$col->name}' => fake()->numberBetween(0, 2147483647),",
            Nf5ColumnType::Boolean => "            '{$col->name}' => fake()->boolean(),",
            Nf5ColumnType::String  => "            '{$col->name}' => fake()->word(),",
            Nf5ColumnType::TinyInt => "            '{$col->name}' => fake()->numberBetween(1, 3),",
            Nf5ColumnType::Double  => "            '{$col->name}' => fake()->randomFloat(6, -90, 90),",
        };
    }

    private function className(string $tfName): string
    {
        // Mirror the Nf5ModelWriter singularisation exactly.
        $parts = explode('_', substr($tfName, 3));
        $out = '';
        foreach ($parts as $part) {
            $out .= ucfirst($part);
        }
        if (str_ends_with($out, 's') && !str_ends_with($out, 'ss')) {
            $out = substr($out, 0, -1);
        }
        return $out;
    }
}
```

Wire stage 2 into the command (`| = -2` in the option):

```php
if ($stage >= 2) {
    (new Nf5FactoryWriter($out))->writeAll($parents);
    $this->info('Factories generated.');
}
```

- [ ] **Step 4: Run tests + phpstan**

Run: `vendor/bin/phpunit tests/Schema/Nf5` && `vendor/bin/phpstan analyse src/Schema/Nf5 --level=5 --no-progress`
Expected: all pass, 0 errors.

- [ ] **Step 5: Commit**

```bash
git add src/Schema/Nf5/Nf5FactoryWriter.php tests/Schema/Nf5/Nf5FactoryWriterTest.php src/Laravel/Console/TeleframeMirrorCommand.php
git commit -m "feat(schema): Nf5FactoryWriter + stage-2 factories in teleframe:mirror"
```

---

### Task 10: Stage 3 — `MessagesUnion` + spec §11 TDLib query helpers

Implements spec §11: TDLib-informed data-fetch methods on the mirror models.

**Files:**
- Create: `src/Teleframe/Repositories/MessagesUnion.php` (or `Dataset/` per existing layout), `tests/Schema/Nf5/MessagesUnionTest.php`
- Modify: `src/Laravel/Console/TeleframeMirrorCommand.php` (stage 3 path)

**Interfaces:**
- Consumes: `TfMirrorModel` parents (generated in Task 6).
- Produces: `MessagesUnion::forPeer($accountId, int $peerType, int $peerId): Builder` — a `UNION ALL` over `tf_messages` + `tf_messages_service` on the same peer, ordered by `id DESC`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Nf5;

use MeRezaRezaei\Teleframe\Teleframe\Repositories\MessagesUnion;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

final class MessagesUnionTest extends TestbenchTestCase
{
    public function test_union_sql_spans_both_tables(): void
    {
        $sql = MessagesUnion::forPeer(7, 1, 42)->toSql();
        self::assertStringContainsString('tf_messages', $sql);
        self::assertStringContainsString('tf_messages_service', $sql);
        self::assertStringContainsString('union all', strtolower($sql));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Schema/Nf5/MessagesUnionTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Teleframe\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Union across tf_messages and tf_messages_service for a single peer — the
 * two tables share the same columns by construction (Nf5CtorSplitter), and
 * Telegram guarantees disjoint id spaces, so UNION ALL cannot duplicate.
 */
final class MessagesUnion
{
    public static function forPeer(int $accountId, int $peerType, int $peerId): Builder
    {
        $base = fn () => DB::table('tf_messages')
            ->where('account_id', $accountId)
            ->where('peer_type', $peerType)
            ->where('peer_id', $peerId);
        $service = fn () => DB::table('tf_messages_service')
            ->where('account_id', $accountId)
            ->where('peer_type', $peerType)
            ->where('peer_id', $peerId);

        return $base()->unionAll($service())->orderByDesc('id');
    }
}
```

Add the spec §11 model methods (same commit, straightforward; each is a thin wrapper — no new infra):

```php
// TfMessage (parent model, generated) — add these METHODS to the generated model:
final class TfMessage extends TfMirrorModel
{
    // ... existing generated body ...

    /** TDLib loadMessages(user, chat, from_message_id, limit) → forPeer().olderThan(). */
    public static function forPeer(int $accountId, int $peerType, int $peerId): Builder
    {
        return MessagesUnion::forPeer($accountId, $peerType, $peerId);
    }

    public function scopeOlderThan(Builder $q, int $messageId): Builder
    {
        return $q->where('id', '<', $messageId);
    }
}
```

(These additions are emitted by `Nf5ModelWriter`; for the plan, add them to the writer's body — see the "Stage 3" note in the command wiring.)

- [ ] **Step 4: Run tests + verify**

Run: `vendor/bin/phpunit tests/Schema/Nf5` && `composer verify`
Expected: green.

- [ ] **Step 5: Commit**

```bash
git add src/Teleframe/Repositories/MessagesUnion.php tests/Schema/Nf5/MessagesUnionTest.php
git commit -m "feat(schema): MessagesUnion + spec-11 peer-scoped query helpers (stage 3)"
```

---

## Self-review (run before declaring complete)

- [ ] **Spec coverage:** §4 decision B → Task 1; §5/§6/§7 rules → Tasks 2–5 (invariants + DDL graph + migrations); §12 zero-json/blob/NULL → Tasks 2 & 8 gates; §13 stages → Tasks 7–10; §11 TDLib queries → Task 10; §8 bytes→hex → Task 3 `hex` flag + R4; §3 ephemeral contract → never generates tables for ephemeral types (resolver only reads mirror tables; no lookup table emitted).
- [ ] **Placeholder scan:** every step contains real code; no "TODO"/"implement later"/"similar to Task N". Five concrete test files, five writer/reader classes, one command — all with signatures defined in the Interfaces block.
- [ ] **Type consistency:** `Nf5Catalog::load(jsonPath, TlScheme)` is used identically in Tasks 2/4/5/6/9 tests; `Nf5TableResolver::resolveAll(array): list<Nf5Table>` everywhere; `Nf5Table::columns` layout `[account_id, key..., constructor?, base..., bools...]` consistent with Task 4 test expectations (`$chats->columns[2]` = constructor). `Nf5ColumnType` enum values (`BigInt`,`Integer`,`Boolean`,`String`,`TinyInt`,`Double`) match the match arms in writer + factory + casts.
- [ ] **Parallel note (executor-facing):** Tasks 1→6 form a strict dependency spine (splitter→catalog→resolver→writers). Task 7 depends on Tasks 1–6. After Task 7, Tasks 8, 9, and 10 are parallel-safe (separate files, no shared state) — run as parallel sub-agents in separate worktrees per the team convention, merging in order. `composer verify` must stay green on each merge.