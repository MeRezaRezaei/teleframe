<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;
use MeRezaRezaei\Teleframe\Laravel\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Schema\Generator\CodeWriter;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\Ddl\MirrorColumnType;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCtorSplitter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFieldDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorSchemaException;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * W-20: Schema/Mirror + CodeWriter + TfMirrorModel previously had ZERO
 * direct test references. This suite exercises the parse-invariant seams —
 * driven from the committed TL source + mirror-catalog spec, never from a
 * full regen run (which is the regeneration gate's job).
 */
final class MirrorCoreCoverageTest extends TestCase
{
    private MirrorCatalog $catalog;

    protected function setUp(): void
    {
        parent::setUp();

        $scheme = TlParser::parseFile(dirname(__DIR__, 3).'/schema/sources/TL_telegram_v227.tl');
        $this->catalog = MirrorCatalog::load(
            SchemaArtifacts::catalog(),
            $scheme,
        );
    }

    // ---- MirrorFieldDecomposer: shape rendering (pure, no TL) ----

    public function test_peer_shape_expands_to_type_id_columns(): void
    {
        $columns = MirrorFieldDecomposer::fromShape('peer_type TINYINT + peer_id BIGINT', 'reply_to_peer_id');

        self::assertCount(2, $columns);
        self::assertSame('reply_to_peer_id_type', $columns[0]->name);
        self::assertSame(MirrorColumnType::TinyInt, $columns[0]->type);
        self::assertSame('reply_to_peer_id_id', $columns[1]->name);
        self::assertSame(MirrorColumnType::BigInt, $columns[1]->type);
    }

    public function test_canonical_peer_id_keeps_half_naming(): void
    {
        $columns = MirrorFieldDecomposer::fromShape('peer_type TINYINT + peer_id BIGINT', 'peer_id');

        self::assertCount(2, $columns);
        self::assertSame('peer_type', $columns[0]->name);
        self::assertSame('peer_id', $columns[1]->name);
        self::assertTrue($columns[0]->peer);
        self::assertTrue($columns[1]->peer);
    }

    public function test_fk_and_child_shapes_return_null(): void
    {
        self::assertNull(MirrorFieldDecomposer::fromShape('FK→User', 'author_id'));
        self::assertNull(MirrorFieldDecomposer::fromShape('1:N child', 'participants'));
        self::assertNull(MirrorFieldDecomposer::fromShape('FK→Document', 'media'));
    }

    public function test_scalar_shape_mapping(): void
    {
        $varchar = MirrorFieldDecomposer::fromShape('VARCHAR(64) NOT NULL', 'title');
        self::assertSame('title', $varchar[0]->name);
        self::assertSame(MirrorColumnType::String, $varchar[0]->type);
        self::assertSame(64, $varchar[0]->length);

        $double = MirrorFieldDecomposer::fromShape('DOUBLE PRECISION NOT NULL', 'rate');
        self::assertSame(MirrorColumnType::Double, $double[0]->type);

        $bool = MirrorFieldDecomposer::fromShape('BOOLEAN NOT NULL DEFAULT FALSE', 'verified');
        self::assertSame(MirrorColumnType::Boolean, $bool[0]->type);

        $big = MirrorFieldDecomposer::fromShape('BIGINT NOT NULL', 'size');
        self::assertSame(MirrorColumnType::BigInt, $big[0]->type);
    }

    // ---- MirrorColumnType DDL ----

    public function test_column_type_sql_mapping(): void
    {
        self::assertSame('BIGINT', MirrorColumnType::BigInt->sql());
        self::assertSame('INTEGER', MirrorColumnType::Integer->sql());
        self::assertSame('BOOLEAN', MirrorColumnType::Boolean->sql());
        self::assertSame('TEXT', MirrorColumnType::String->sql());
        self::assertSame('TINYINT', MirrorColumnType::TinyInt->sql());
        self::assertSame('DOUBLE PRECISION', MirrorColumnType::Double->sql());
    }

    // ---- MirrorCatalog invariants + resolution (parse-invariant, committed TL) ----

    public function test_catalog_loads_from_committed_spec(): void
    {
        self::assertTrue($this->catalog->has('tf_messages'));
        self::assertTrue($this->catalog->has('tf_users'));
        self::assertTrue($this->catalog->has('tf_chats'));
        self::assertNotEmpty($this->catalog->tableNames());
    }

    public function test_ctor_resolves_to_owning_table(): void
    {
        // Plain message ctor → tf_messages; the splitter's messageService →
        // its own tf_messages_service sibling (catalog's own split survives
        // regeneration — the whole point of MirrorCtorSplitter).
        self::assertSame('tf_messages', $this->catalog->tableForCtor('message')?->tfName);
        self::assertSame('tf_messages_service', $this->catalog->tableForCtor('messageService')?->tfName);
        self::assertSame('tf_users', $this->catalog->tableForCtor('user')?->tfName);
        self::assertNull($this->catalog->tableForCtor('noSuchCtor'));
    }

    public function test_tl_type_resolves_to_table(): void
    {
        // Abstract Message type is split across two tables (tf_messages +
        // tf_messages_service); the catalog maps the type to one of them.
        self::assertContains(
            $this->catalog->tableForTlType('Message')?->tfName,
            ['tf_messages', 'tf_messages_service'],
        );
        self::assertSame('tf_users', $this->catalog->tableForTlType('User')?->tfName);
    }

    public function test_assert_invariants_passes_on_committed_catalog(): void
    {
        // Must not throw: ctors non-empty, no nullable shapes, no banned types.
        $this->catalog->assertInvariants();
        self::assertTrue(true);
    }

    public function test_assert_invariants_rejects_empty_first_base_column(): void
    {
        $this->expectException(MirrorSchemaException::class);
        $this->expectExceptionMessage('tf_adversarial');
        $this->expectExceptionMessage("''");

        // fromEntries() asserts the populated catalog before returning.
        MirrorCatalog::fromEntries([
            'tf_adversarial' => [
                'tl' => 'Foo',
                'ctors' => ['foo'],
                'base' => [['', 'VARCHAR(64) NOT NULL']],
                'bools' => [],
                'children' => [],
            ],
        ]);
    }

    public function test_assert_invariants_rejects_1n_child_first_base_column(): void
    {
        $this->expectException(MirrorSchemaException::class);
        $this->expectExceptionMessage('tf_adversarial');
        $this->expectExceptionMessage('parent_participants');
        $this->expectExceptionMessage('1:N child');

        // fromEntries() asserts the populated catalog before returning.
        MirrorCatalog::fromEntries([
            'tf_adversarial' => [
                'tl' => 'Foo',
                'ctors' => ['foo'],
                'base' => [['parent_participants', '1:N child']],
                'bools' => [],
                'children' => [],
            ],
        ]);
    }

    public function test_assert_invariants_accepts_natural_key_first_base_column(): void
    {
        $catalog = MirrorCatalog::fromEntries([
            'tf_links' => [
                'tl' => 'WebLink',
                'ctors' => ['webLink'],
                'base' => [
                    ['link', 'TEXT NOT NULL'],
                    ['title', 'TEXT NOT NULL'],
                ],
                'bools' => ['has_thumb'],
                'children' => [],
            ],
        ]);

        $catalog->assertInvariants();
        self::assertTrue($catalog->has('tf_links'));
        self::assertSame('tf_links', $catalog->tableForCtor('webLink')?->tfName);
    }

    public function test_unknown_table_throws(): void
    {
        $this->expectException(MirrorSchemaException::class);
        $this->expectExceptionMessage('Unknown mirror table');
        $this->catalog->table('tf_nope');
    }

    // ---- MirrorCtorSplitter: Message/MessageService disjoint split ----

    public function test_ctor_splitter_produces_service_sibling(): void
    {
        $scheme = TlParser::parseFile(dirname(__DIR__, 3).'/schema/sources/TL_telegram_v227.tl');
        $splitter = new MirrorCtorSplitter($scheme);

        $entries = json_decode(
            (string) file_get_contents(SchemaArtifacts::catalog()),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $split = $splitter->apply($entries);

        // messageService must be pulled OUT of tf_messages and into its own table.
        self::assertArrayHasKey('tf_messages_service', $split);
        self::assertSame(['messageService'], $split['tf_messages_service']['ctors']);
        self::assertNotContains('messageService', $split['tf_messages']['ctors']);
        self::assertContains('message', $split['tf_messages']['ctors']);
    }

    // ---- CodeWriter (pure string generation) ----

    public function test_php_file_wraps_namespace_and_banner(): void
    {
        $out = CodeWriter::phpFile('App\\Mirror', ['$x = 1;']);

        self::assertStringContainsString('<?php', $out);
        self::assertStringContainsString('namespace App\\Mirror;', $out);
        self::assertStringContainsString('$x = 1;', $out);
        self::assertStringStartsWith('<?php'.PHP_EOL, $out);
    }

    public function test_migration_file_wraps_up_and_down(): void
    {
        $out = CodeWriter::migrationFile(
            ['Schema::create("tf_x", function (Blueprint $t) { $t->id(); });'],
            ['Schema::dropIfExists("tf_x");'],
        );

        self::assertStringContainsString('public function up(): void', $out);
        self::assertStringContainsString('public function down(): void', $out);
        self::assertStringContainsString('Schema::create("tf_x"', $out);
        self::assertStringContainsString('Schema::dropIfExists("tf_x")', $out);
    }

    // ---- TfMirrorModel base class contract ----

    public function test_mirror_model_disables_incrementing_and_timestamps(): void
    {
        $model = new class extends TfMirrorModel
        {
            protected $table = 'tf_whatever';
        };

        self::assertFalse($model->getIncrementing());
        self::assertFalse($model->usesTimestamps());
        self::assertSame('id', $model->getKeyName());
    }
}
