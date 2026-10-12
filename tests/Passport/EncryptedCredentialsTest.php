<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\EncryptedCredentials;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * EncryptedCredentialsTest — the G1 intake contract (design §2/§9, spec
 * §5): `fromUpdate` extracts the Bot API `message.passport_data.credentials`
 * shape AND the MTProto `SecureCredentialsEncrypted` root shape, and is
 * QUIET (null) on missing/empty fields — a non-passport webhook must never
 * throw. `fromArray` is the loud round-trip side of `toArray()`.
 */
final class EncryptedCredentialsTest extends TestCase
{
    public function test_from_update_bot_api_message_passport_data_shape(): void
    {
        $credentials = EncryptedCredentials::fromUpdate([
            'update_id' => 1,
            'message' => [
                'from' => ['id' => 42],
                'passport_data' => [
                    'credentials' => [
                        'data' => 'dGhlLWRhdGE=',
                        'hash' => 'dGhlLWhhc2g=',
                        'secret' => 'dGhlLXNlY3JldA==',
                    ],
                ],
            ],
        ]);

        self::assertNotNull($credentials);
        self::assertSame('dGhlLWRhdGE=', $credentials->data);
        self::assertSame('dGhlLWhhc2g=', $credentials->hash);
        self::assertSame('dGhlLXNlY3JldA==', $credentials->secret);
    }

    public function test_from_update_mtproto_secure_credentials_encrypted_root_shape(): void
    {
        $credentials = EncryptedCredentials::fromUpdate([
            '_' => 'secureCredentialsEncrypted',
            'data' => 'cm9vdC1kYXRh',
            'hash' => 'cm9vdC1oYXNo',
            'secret' => 'cm9vdC1zZWNyZXQ=',
        ]);

        self::assertNotNull($credentials);
        self::assertSame('cm9vdC1kYXRh', $credentials->data);
        self::assertSame('cm9vdC1oYXNo', $credentials->hash);
        self::assertSame('cm9vdC1zZWNyZXQ=', $credentials->secret);
    }

    public function test_from_update_returns_null_when_update_carries_no_credentials(): void
    {
        self::assertNull(EncryptedCredentials::fromUpdate([]));
        self::assertNull(EncryptedCredentials::fromUpdate(['update_id' => 1]));
        self::assertNull(EncryptedCredentials::fromUpdate([
            'message' => ['from' => ['id' => 1], 'text' => 'hello'],
        ]));
        self::assertNull(EncryptedCredentials::fromUpdate([
            'message' => ['passport_data' => []],
        ]));
        // A message with a partial root data/hash/secret (missing one key) is
        // NOT a well-formed credentials array → null (G1 quiet contract).
        self::assertNull(EncryptedCredentials::fromUpdate([
            'data' => 'only-data',
            'hash' => 'no-secret',
        ]));
    }

    public function test_from_update_is_quiet_on_empty_required_fields(): void
    {
        self::assertNull(EncryptedCredentials::fromUpdate([
            'message' => ['passport_data' => ['credentials' => ['data' => '', 'hash' => 'x', 'secret' => 'y']]],
        ]));
        self::assertNull(EncryptedCredentials::fromUpdate([
            'message' => ['passport_data' => ['credentials' => ['data' => 'x', 'hash' => 42, 'secret' => 'y']]],
        ]));
    }

    public function test_from_array_is_loud_on_malformed_credentials(): void
    {
        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('Malformed passport credentials');

        EncryptedCredentials::fromArray(['data' => 'x', 'hash' => 'y']);
    }

    public function test_from_array_rejects_empty_and_non_string_fields(): void
    {
        foreach ([
            ['data' => '', 'hash' => 'y', 'secret' => 'z'],
            ['data' => 'x', 'hash' => '', 'secret' => 'z'],
            ['data' => 'x', 'hash' => 'y', 'secret' => ''],
            ['data' => 1, 'hash' => 'y', 'secret' => 'z'],
            [],
        ] as $malformed) {
            try {
                EncryptedCredentials::fromArray($malformed);
                self::fail('fromArray must throw on: '.json_encode($malformed, JSON_THROW_ON_ERROR));
            } catch (PassportLoginException $e) {
                self::assertStringContainsString('Malformed passport credentials', $e->getMessage());
            }
        }
    }

    public function test_to_array_round_trips_exactly(): void
    {
        $credentials = EncryptedCredentials::fromArray([
            'data' => 'dGhlLWRhdGE=',
            'hash' => 'dGhlLWhhc2g=',
            'secret' => 'dGhlLXNlY3JldA==',
        ]);

        self::assertSame([
            'data' => 'dGhlLWRhdGE=',
            'hash' => 'dGhlLWhhc2g=',
            'secret' => 'dGhlLXNlY3JldA==',
        ], $credentials->toArray());
    }

    // ── the security predicate behind the 5cbbde5b refusal (F5) ───────

    /**
     * `updateCarriesCredentials()` is the predicate that decides whether an
     * update with NO bound session is REFUSED loudly or quietly ignored, so an
     * incomplete arm in it is a security difference, not a bug report.
     *
     * FAIL-OPEN direction: an action that carries `credentials` + `values` and
     * NO constructor must NOT be accepted. Key presence standing in for
     * identity is this module's own recurring defect ("a coordinate that looks
     * right and is not") — and `peerUser` is the standing proof that a bag of
     * keys can be a channel or a chat. A TL object is the object it names.
     */
    public function test_a_constructor_less_action_is_not_a_passport_response(): void
    {
        $update = [
            '_' => 'updateNewMessage',
            'messageService' => [
                'action' => [
                    // no `_` at all — only the two documented KEYS
                    'values' => [[
                        '_' => 'secureValue',
                        'type' => ['_' => 'secureValueTypeEmail'],
                        'plain_data' => ['_' => 'securePlainEmail', 'email' => 'attacker@example.test'],
                    ]],
                    'credentials' => ['data' => 'd', 'hash' => 'h', 'secret' => 's'],
                ],
            ],
        ];

        self::assertFalse(
            EncryptedCredentials::updateCarriesCredentials($update),
            'a constructor-less action is not messageActionSecureValuesSentMe'
        );
        self::assertNull(EncryptedCredentials::fromUpdate($update), 'so it yields no credentials and no claim');
    }

    /**
     * The same update handed over directly as `$update['action']` — the third
     * documented coordinate — is refused on the same rule.
     */
    public function test_a_constructor_less_action_is_refused_on_the_unwrapped_coordinate_too(): void
    {
        $update = [
            'action' => [
                'values' => [],
                'credentials' => ['data' => 'd', 'hash' => 'h', 'secret' => 's'],
            ],
        ];

        self::assertFalse(EncryptedCredentials::updateCarriesCredentials($update));
        self::assertNull(EncryptedCredentials::fromUpdate($update));
    }

    /**
     * FAIL-CLOSED direction: an action that IS this constructor and DOES carry
     * credentials is credential-bearing even when it has no `values` key.
     *
     * `values` is the §6 CLAIM channel, not the credentials envelope, and
     * `messageActionSecureValuesSentMe.values` is a `Vector<SecureValue>` that
     * a host may legitimately have omitted. Requiring the key meant such an
     * update was reported as carrying NOTHING: `handleUpdate()` returned null
     * and the unbound-session refusal never fired — a silent path around the
     * account-takeover fix. What the missing channel must produce is "no
     * claim", which the claim-based login then refuses loudly.
     */
    public function test_a_values_less_credential_action_is_still_recognised_and_carries_no_claim(): void
    {
        $update = [
            '_' => 'updateNewMessage',
            'messageService' => [
                'action' => [
                    '_' => 'messageActionSecureValuesSentMe',
                    'credentials' => ['data' => 'd', 'hash' => 'h', 'secret' => 's'],
                ],
            ],
        ];

        self::assertTrue(
            EncryptedCredentials::updateCarriesCredentials($update),
            'a credential-bearing update must always reach the session refusal'
        );

        $credentials = EncryptedCredentials::fromUpdate($update);

        self::assertNotNull($credentials, 'the credentials are real and well-formed');
        self::assertSame([], $credentials->claims->types(), 'no values channel means no claim');
    }

    /** Another `MessageAction` is still not a passport response. */
    public function test_a_foreign_action_with_the_same_keys_is_refused(): void
    {
        $update = [
            'action' => [
                '_' => 'messageActionPhoneCall',
                'values' => [],
                'credentials' => ['data' => 'd', 'hash' => 'h', 'secret' => 's'],
            ],
        ];

        self::assertFalse(EncryptedCredentials::updateCarriesCredentials($update));
        self::assertNull(EncryptedCredentials::fromUpdate($update));
    }

    /**
     * An action whose `credentials` is not an object is not credential-bearing
     * — and the predicate is quiet either way, because a routing question must
     * not throw on a webhook.
     */
    public function test_an_action_whose_credentials_is_not_an_object_carries_nothing(): void
    {
        $update = [
            'action' => [
                '_' => 'messageActionSecureValuesSentMe',
                'values' => [],
                'credentials' => 'not-an-object',
            ],
        ];

        self::assertFalse(EncryptedCredentials::updateCarriesCredentials($update));
        self::assertNull(EncryptedCredentials::fromUpdate($update));
    }

    /**
     * The three coordinates still behave, so the docblock's "the list cannot
     * exist twice and drift" is about a list that actually covers the cases.
     *
     * @return iterable<string, array{0: array<string, mixed>}>
     */
    public static function credentialCoordinates(): iterable
    {
        yield 'bot api message.passport_data.credentials' => [[
            'message' => ['passport_data' => ['credentials' => ['data' => 'd', 'hash' => 'h', 'secret' => 's']]],
        ]];

        yield 'mtproto messageActionSecureValuesSentMe' => [[
            'messageService' => ['action' => [
                '_' => 'messageActionSecureValuesSentMe',
                'values' => [],
                'credentials' => ['data' => 'd', 'hash' => 'h', 'secret' => 's'],
            ]],
        ]];

        yield 'a bare SecureCredentialsEncrypted' => [[
            'data' => 'd', 'hash' => 'h', 'secret' => 's',
        ]];

        yield 'the snake_case message_service spelling' => [[
            'message_service' => ['action' => [
                '_' => 'messageActionSecureValuesSentMe',
                'values' => [],
                'credentials' => ['data' => 'd', 'hash' => 'h', 'secret' => 's'],
            ]],
        ]];
    }

    /**
     * @param  array<string, mixed>  $update
     */
    #[DataProvider('credentialCoordinates')]
    public function test_every_documented_coordinate_is_recognised(array $update): void
    {
        self::assertTrue(EncryptedCredentials::updateCarriesCredentials($update));
        self::assertNotNull(EncryptedCredentials::fromUpdate($update));
    }
}
