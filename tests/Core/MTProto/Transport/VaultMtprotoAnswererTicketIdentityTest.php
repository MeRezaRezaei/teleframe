<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\MTProto\Transport;

use MeRezaRezaei\Teleframe\Core\MTProto\Transport\VaultMtprotoAnswerer;
use PHPUnit\Framework\TestCase;

/**
 * VaultMtprotoAnswerer::normalizeTicketGrant — the value normalization the
 * answerer applies to the relay's raw ticket grant before the dispatch
 * policy sees it: a bare int (the legacy validator shape) becomes an
 * account-only identity, an identity array {account_id, user_id} keeps the
 * Laravel user captured at mint time, and anything that cannot name an
 * account is null (which the ticketRequired gate treats as unauthenticated).
 */
final class VaultMtprotoAnswererTicketIdentityTest extends TestCase
{
    public function test_legacy_int_grant_normalizes_to_account_only_identity(): void
    {
        self::assertSame(['account_id' => 42, 'user_id' => null], VaultMtprotoAnswerer::normalizeTicketGrant(42));
    }

    public function test_identity_array_grant_keeps_account_and_user(): void
    {
        self::assertSame(
            ['account_id' => 7, 'user_id' => 5],
            VaultMtprotoAnswerer::normalizeTicketGrant(['account_id' => 7, 'user_id' => 5]),
        );
    }

    public function test_string_user_id_is_preserved_verbatim(): void
    {
        self::assertSame(
            ['account_id' => 7, 'user_id' => 'u-5'],
            VaultMtprotoAnswerer::normalizeTicketGrant(['account_id' => 7, 'user_id' => 'u-5']),
        );
    }

    public function test_absent_user_id_binds_none(): void
    {
        self::assertSame(
            ['account_id' => 7, 'user_id' => null],
            VaultMtprotoAnswerer::normalizeTicketGrant(['account_id' => 7]),
        );
    }

    public function test_empty_string_user_id_is_cleaned_to_null(): void
    {
        self::assertSame(
            ['account_id' => 7, 'user_id' => null],
            VaultMtprotoAnswerer::normalizeTicketGrant(['account_id' => 7, 'user_id' => '']),
        );
    }

    public function test_shapes_that_cannot_name_an_account_are_null(): void
    {
        foreach ([
            null,
            0,
            -1,
            [],
            ['user_id' => 5],
            ['account_id' => 0, 'user_id' => 5],
            ['account_id' => -3, 'user_id' => 5],
            ['account_id' => '42', 'user_id' => 5],
        ] as $grant) {
            self::assertNull(
                VaultMtprotoAnswerer::normalizeTicketGrant($grant),
                var_export($grant, true).' must not name an account',
            );
        }
    }
}
