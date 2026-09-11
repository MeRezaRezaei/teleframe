<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5Catalog;
use MeRezaRezaei\Teleframe\Schema\Nf5\Nf5SchemaException;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class Nf5CatalogTest extends TestCase
{
    private function catalog(): Nf5Catalog
    {
        $scheme = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        return Nf5Catalog::load(__DIR__.'/../../../docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
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

    #[DataProvider('bannedShapeProvider')]
    public function test_invariants_reject_banned_json_blob_binary_shapes(string $shape): void
    {
        $this->expectException(Nf5SchemaException::class);
        Nf5Catalog::fromEntries([
            'tf_bad' => ['tl' => 'X', 'ctors' => ['x'], 'base' => [['payload', $shape]], 'bools' => [], 'children' => []],
        ]);
    }

    /** @return array<string, array{string}> */
    public static function bannedShapeProvider(): array
    {
        return [
            'JSON NOT NULL'    => ['JSON NOT NULL'],
            'BLOB NOT NULL'    => ['BLOB NOT NULL'],
            'VARBINARY NOT NULL' => ['VARBINARY(255) NOT NULL'],
            'json lowercase'   => ['json NOT NULL'],
            'blob lowercase'   => ['blob NOT NULL'],
        ];
    }

    public function test_invariants_accept_peer_first_table(): void
    {
        $this->expectNotToPerformAssertions();

        $catalog = Nf5Catalog::fromEntries([
            'tf_dialogs' => [
                'tl'       => 'Dialog',
                'ctors'    => ['dialog'],
                'base'     => [
                    ['peer', 'peer_type TINYINT + peer_id BIGINT'],
                ],
                'bools'    => [],
                'children' => [],
            ],
        ]);

        // assertInvariants() is void; if it throws the test fails automatically
        $catalog->assertInvariants();
    }

    public function test_invariants_reject_neither_id_nor_peer_first_column(): void
    {
        $this->expectException(Nf5SchemaException::class);
        $catalog = Nf5Catalog::fromEntries([
            'tf_bad' => [
                'tl'       => 'X',
                'ctors'    => ['x'],
                'base'     => [
                    ['title', 'TEXT NOT NULL'],
                ],
                'bools'    => [],
                'children' => [],
            ],
        ]);
        $catalog->assertInvariants();
    }
}
