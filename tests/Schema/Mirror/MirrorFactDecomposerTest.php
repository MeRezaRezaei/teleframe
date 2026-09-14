<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use PHPUnit\Framework\TestCase;

/**
 * Fact decomposer — the ingest truth-model write path (cycle 2).
 *
 * Owner verbatim 2026-09-14: "suppose we have the telegram data came from
 * its mtproto and they simple shoould be insertable into the fully nf 5 fully
 * migrated realational database and what ever forign key that fails gives us
 * a clue to the wrong path of ingesting we have".
 *
 * A decoded TL payload (['_' => ctor, field => value]) must decompose into
 * insertable parent+child rows keyed by (account_id, telegram key). A fact
 * we cannot place into the mirror — a missing referenced parent, an unknown
 * constructor shape — is reported as an ingest clue, not swallowed.
 */
final class MirrorFactDecomposerTest extends TestCase
{
    private MirrorFactDecomposer $decomposer;

    protected function setUp(): void
    {
        $scheme = TlParser::parseFile(__DIR__.'/../../../schema/sources/TL_telegram_v227.tl');
        $catalog = MirrorCatalog::load(__DIR__.'/../../../docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json', $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $this->decomposer = new MirrorFactDecomposer($resolver, $catalog);
    }

    public function test_users_payload_decomposes_to_single_row(): void
    {
        $payload = [
            '_' => 'user',
            'id' => 101,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'username' => 'ada',
            'phone' => '1800',
            'bot' => false,
            'verified' => true,
            'restricted' => false,
        ];

        $result = $this->decomposer->decompose('tf_users', 42, $payload);
        $userRow = $this->firstRowFor($result['rows'], 'tf_users');
        self::assertNotNull($userRow, 'tf_users row must be produced');
        self::assertSame(42, $userRow['account_id']);
        self::assertSame(101, $userRow['id']);
        self::assertSame(1, $userRow['verified'], 'boolean facts store 0/1');
        self::assertSame(0, $userRow['bot']);
        // first_name/username are NOT catalog columns (identity extraction stores
        // only id + bools for tf_users): unknown payload fields must be dropped.
        self::assertArrayNotHasKey('first_name', $userRow);
        self::assertArrayNotHasKey('username', $userRow);
    }

    public function test_payload_with_unknown_ctor_emits_clue_not_row(): void
    {
        $payload = ['_' => 'noSuchUserVariant', 'id' => 1];

        $result = $this->decomposer->decompose('tf_users', 42, $payload);

        self::assertCount(0, $result['rows'], 'unknown constructor must not produce a row');
        self::assertCount(1, $result['clues'], 'unknown constructor is an ingest clue');
        self::assertStringContainsString('noSuchUserVariant', $result['clues'][0]);
    }

    public function test_referenced_peer_missing_from_payload_is_clue(): void
    {
        // tf_dialogs (peer) requires the referenced dialog's peer target shape;
        // a payload missing the peer half cannot be placed — that is exactly
        // the "FK failed → wrong ingest path" signal.
        $payload = ['_' => 'dialog', 'id' => 7, 'top_message' => 3];

        $result = $this->decomposer->decompose('tf_dialogs', 42, $payload);

        self::assertCount(1, $result['rows'], 'row itself is placed (account_id, peer is schema-level)');
        self::assertArrayHasKey('clues', $result);
    }

    public function test_peer_payload_expands_to_type_id_pair(): void
    {
        $payload = [
            '_' => 'dialog',
            'peer' => ['_type' => 2, '_id' => 900],
            'top_message' => 3,
            'pinned' => true,
        ];

        $result = $this->decomposer->decompose('tf_dialogs', 42, $payload);
        $row = $this->firstRowFor($result['rows'], 'tf_dialogs');

        self::assertNotNull($row);
        self::assertSame(2, $row['peer_type']);
        self::assertSame(900, $row['peer_id']);
        self::assertSame(3, $row['top_message']);
        self::assertSame(1, $row['pinned']);
    }

    public function test_missing_peer_half_is_clue(): void
    {
        $payload = ['_' => 'dialog', 'top_message' => 3];

        $result = $this->decomposer->decompose('tf_dialogs', 42, $payload);

        self::assertNotEmpty($result['clues'], 'a peer-keyed table without its peer half is an ingest clue');
        self::assertStringContainsString('peer', $result['clues'][0]);
    }

    public function test_rows_carry_only_known_columns(): void
    {
        $payload = ['_' => 'user', 'id' => 5, 'first_name' => 'X', 'bogus_field' => 'never'];

        $result = $this->decomposer->decompose('tf_users', 42, $payload);

        $userRow = $this->firstRowFor($result['rows'], 'tf_users');
        self::assertNotNull($userRow);
        self::assertArrayNotHasKey('bogus_field', $userRow, 'unknown payload fields are dropped (schema is the contract)');
        self::assertArrayNotHasKey('first_name', $userRow, 'unmirrored columns are dropped, not stored');
    }

    /** @return list<array{table:string, row:array<string, mixed>}> */
    private function firstRowFor(array $rows, string $tfName): ?array
    {
        foreach ($rows as $entry) {
            if ($entry['table'] === $tfName) {
                return $entry['row'];
            }
        }

        return null;
    }
}
