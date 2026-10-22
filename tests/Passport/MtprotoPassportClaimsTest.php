<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Passport\EncryptedCredentials;
use MeRezaRezaei\Teleframe\Passport\FieldIdentityResolver;
use MeRezaRezaei\Teleframe\Passport\LoginResult;
use MeRezaRezaei\Teleframe\Passport\MemoryBindingWriter;
use MeRezaRezaei\Teleframe\Passport\MemoryNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\MemoryPendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportAnonymousLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportKeyLoader;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportPayload;
use MeRezaRezaei\Teleframe\Passport\SenderUserId;
use MeRezaRezaei\Teleframe\Passport\SessionFingerprint;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageActionSecureValuesSentMeData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PeerUserData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecureCredentialsEncryptedData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecureDataData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecurePlainEmailData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecurePlainPhoneData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecureValueData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecureValueTypeEmailData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecureValueTypePersonalDetailsData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecureValueTypePhoneData;
use PHPUnit\Framework\TestCase;

/**
 * MtprotoPassportClaimsTest — the §6 MTProto claim channel, end to end from a
 * REAL `messageActionSecureValuesSentMe` through the intake, the payload and a
 * real login chain.
 *
 * WHAT USED TO BE TRUE, AND WAS THE POINT OF THIS FILE'S EXISTENCE:
 * `PassportVerifiedClaims` read ONLY the Bot API element plaintext, and its
 * docblock said the MTProto `values: Vector<SecureValue>` sibling was an
 * unimplemented traversal. So an MTProto-shaped claim-bearing response carried
 * no claim, and a field-mode login on it was refused — honestly, but uselessly.
 * These tests pin that the traversal now exists, that it reads the SAME claim
 * the Bot API shape does, and — the part that must not move — that it changes
 * NOTHING about what a shape with no claim is allowed to do.
 *
 * THE POLARITY RULE, inherited from `SpecCredentialFactory`: fixtures are built
 * from the specification, and where the spec and the implementation disagree
 * the FIXTURE IS RIGHT. Every update below is assembled from the GENERATED
 * schema types for the two constructors §1/§7 name —
 * `MessageActionSecureValuesSentMeData` (api.tl:167,
 * `values:Vector<SecureValue> credentials:SecureCredentialsEncrypted`) and its
 * `SecureValue` members — and the array spelling the intake consumes is written
 * out by `MtprotoAction::toWire()` below, which is typed on those generated
 * classes, so it cannot express an invented shape.
 *
 * THE TWO PROPERTIES THAT ARE NOT ABOUT FEATURES, and that this file exists to
 * protect, whatever the traversal does:
 *
 *   1. A NULLABLE SESSION ID NEVER SKIPS THE NONCE/SESSION COMPARISON. The
 *      first account takeover in this module (5cbbde5b) was a
 *      `?int $sessionUserId = null` whose null skipped the comparison. The
 *      traversal is a NEW input to the intake, so it is exactly the kind of
 *      change that can quietly re-open that door, and the tests below are
 *      written to fail if the comparison becomes skippable again.
 *   2. A FIELD-MODE LOGIN ON A SHAPE WITH NO CLAIM IS STILL REFUSED LOUDLY.
 *      Not downgraded onto another coordinate, not resolved from a value nobody
 *      vouched for, and never with a silent success.
 */
final class MtprotoPassportClaimsTest extends TestCase
{
    /**
     * The sender every fixture in this file speaks as: a real `peerUser`
     * `user_id`, in the MTProto spelling the chain reads (`from_id`, not
     * `from.id`). Tests that need a different sender write their own.
     */
    private const SENDER_TG_ID = 1724372757;

    private string $pemFile = '';

    private SpecCredentialFactory $factory;

    protected function setUp(): void
    {
        MtprotoIntakeUser::$resolved = null;
        MtprotoIntakeUser::$lookups = [];

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($key);

        $private = '';
        self::assertTrue(openssl_pkey_export($key, $private));
        $details = openssl_pkey_get_details($key);
        self::assertIsArray($details);
        $this->factory = SpecCredentialFactory::withKeys($private, (string) $details['key']);

        $this->pemFile = (string) tempnam(sys_get_temp_dir(), 'teleframe-passport-mtproto-');
        self::assertNotSame('', $this->pemFile);
        file_put_contents($this->pemFile, $private);
        chmod($this->pemFile, 0o600);
    }

    protected function tearDown(): void
    {
        if ($this->pemFile !== '' && is_file($this->pemFile)) {
            unlink($this->pemFile);
        }
        MtprotoIntakeUser::$resolved = null;
        MtprotoIntakeUser::$lookups = [];
    }

    // ── the claim channel, from the real action ────────────────────────

    public function test_the_intake_reads_the_verified_claims_off_the_values_sibling(): void
    {
        $credentials = EncryptedCredentials::fromUpdate($this->update(
            $this->seal(['secure_data' => [], 'nonce' => 'n-1']),
            MtprotoAction::sentMe(['email' => 'ada@example.test', 'phone' => '+15551234567']),
        ));

        self::assertNotNull($credentials);
        self::assertSame('ada@example.test', $credentials->claims->email());
        self::assertSame('+15551234567', $credentials->claims->phone());
        self::assertSame(['email', 'phone'], $credentials->claims->types());
    }

    public function test_the_mtproto_shape_yields_the_same_claims_the_bot_api_shape_does(): void
    {
        $envelope = $this->seal(['secure_data' => [], 'nonce' => 'n-1']);

        $mtproto = EncryptedCredentials::fromUpdate($this->update(
            $envelope,
            MtprotoAction::sentMe(['email' => 'ada@example.test', 'phone' => '+15551234567']),
        ));
        $botApi = EncryptedCredentials::fromUpdate([
            'update_id' => 1,
            'message' => [
                'from' => ['id' => 4242],
                'passport_data' => PassportElementFactory::passportData(
                    $envelope,
                    ['email' => 'ada@example.test', 'phone_number' => '+15551234567'],
                ),
            ],
        ]);

        self::assertNotNull($mtproto);
        self::assertNotNull($botApi);
        self::assertSame($botApi->claims->email(), $mtproto->claims->email());
        self::assertSame($botApi->claims->phone(), $mtproto->claims->phone());
        self::assertSame(
            $botApi->claims->types(),
            $mtproto->claims->types(),
            '§6 is one guarantee in two shapes; the claims must not differ',
        );
    }

    // ── the coordinate is pinned, including the invented one ───────────

    /**
     * A `values` list BESIDE the credentials at the root of the update is a
     * coordinate Telegram never writes: §1 puts it inside
     * `messageActionSecureValuesSentMe`, and nowhere else. Reading it would be
     * the same class of defect as `secure_data.email` and as the removed
     * `PassportData.email` sibling — green against a fixture, unreachable
     * against real Telegram.
     */
    public function test_a_values_list_beside_the_credentials_is_not_read(): void
    {
        // Hand-built on purpose: `MtprotoAction` cannot express this shape,
        // which is what makes the assertion possible.
        $update = [
            '_' => 'secureCredentialsEncrypted',
            'data' => 'raw-bytes',
            'hash' => 'hash',
            'secret' => 'raw-secret',
            'values' => [[
                '_' => 'secureValue',
                'type' => ['_' => 'secureValueTypeEmail'],
                'plain_data' => ['_' => 'securePlainEmail', 'email' => 'attacker@example.test'],
            ]],
        ];

        $credentials = EncryptedCredentials::fromUpdate($update);

        self::assertNotNull($credentials);
        self::assertNull($credentials->claims->email(), 'only the action coordinate is read');
    }

    /** An action that is NOT `messageActionSecureValuesSentMe` is not a passport response. */
    public function test_another_message_action_never_yields_a_claim(): void
    {
        $credentials = EncryptedCredentials::fromUpdate([
            '_' => 'updateNewMessage',
            'messageService' => [
                'action' => [
                    '_' => 'messageActionPhoneCall',
                    'values' => [[
                        '_' => 'secureValue',
                        'type' => ['_' => 'secureValueTypeEmail'],
                        'plain_data' => ['_' => 'securePlainEmail', 'email' => 'attacker@example.test'],
                    ]],
                    'credentials' => ['data' => 'd', 'hash' => 'h', 'secret' => 's'],
                ],
            ],
        ]);

        self::assertNull($credentials, 'a foreign action is not a passport response at all');
    }

    // ── no claim is still a LOUD refusal, never a downgrade ────────────

    /**
     * A document-only `values` list yields no claim, so the claim-based
     * resolver finds nothing, and the chain refuses. The property is the
     * refusal: a traversal that found nothing must not resolve a login from
     * some other coordinate.
     */
    public function test_a_mtproto_shape_with_no_claim_resolves_no_identity(): void
    {
        $credentials = EncryptedCredentials::fromUpdate($this->update(
            $this->seal(['secure_data' => ['personal_details' => ['first_name' => 'Ada']], 'nonce' => 'n-1']),
            MtprotoAction::sentMe([]),
        ));

        self::assertNotNull($credentials);
        self::assertNull($credentials->claims->email());
        self::assertNull($credentials->claims->phone());

        // Straight into the claim-based resolver with a session id: null is the
        // refusal, and no users-table lookup is issued for it.
        MtprotoIntakeUser::$resolved = MtprotoIntakeUser::forId(42);
        $payload = new PassportPayload(
            ['secure_data' => ['personal_details' => ['first_name' => 'Ada']], 'nonce' => 'n-1'],
            $credentials->claims,
        );

        $resolved = (new FieldIdentityResolver(
            userClass: MtprotoIntakeUser::class,
            emailColumn: 'email',
            phoneColumn: 'phone',
        ))->resolve($payload, 42);

        self::assertNull($resolved);
        self::assertSame([], MtprotoIntakeUser::$lookups, 'the users table was never consulted');
    }

    /**
     * And the same refusal, through a REAL chain: a document-only MTProto
     * update on the field-mode attach path is refused, with no lookup and
     * nothing bound. The update carries a sender id because the chain reads
     * `message.from.id`, so this test is about the CLAIM and nothing else.
     */
    public function test_a_field_mode_login_on_a_claimless_mtproto_update_is_refused_loudly(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $service = $this->attachService($nonces, $writer);
        $nonce = $nonces->issue(42);
        MtprotoIntakeUser::$resolved = MtprotoIntakeUser::forId(42);

        $update = $this->update(
            $this->seal(['secure_data' => ['personal_details' => ['first_name' => 'Ada']], 'nonce' => $nonce]),
            MtprotoAction::sentMe([]),
        );

        try {
            $service->login($update, 42);
            self::fail('a response with no verified claim must not resolve a login');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('carries no Telegram-verified field', $e->getMessage());
            self::assertStringNotContainsString('ada@example.test', $e->getMessage());
        }

        self::assertSame([], MtprotoIntakeUser::$lookups);
        self::assertSame([], $writer->rows(), 'and nothing is bound');
    }

    // ── the takeover property: the comparison is NOT skippable ─────────

    /**
     * THE REGRESSION TEST. A NULLABLE session id must NEVER skip the
     * nonce/session comparison (5cbbde5b). `handleUpdate()` has no default that
     * can be omitted into a null; a null is refused at the boundary, BEFORE
     * anything is decrypted or resolved.
     *
     * This test fails the moment a `?int $sessionUserId = null` default comes
     * back, or the moment the `=== null` refusal is softened — the exact shape
     * of the first account takeover in this module.
     */
    public function test_a_missing_session_id_is_refused_rather_than_skipping_the_comparison(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $service = $this->attachService($nonces, $writer);
        $nonce = $nonces->issue(42);
        MtprotoIntakeUser::$resolved = MtprotoIntakeUser::forId(42);

        $update = $this->update(
            $this->seal(['secure_data' => [], 'nonce' => $nonce]),
            MtprotoAction::sentMe(['email' => 'ada@example.test']),
        );

        try {
            // No session id at all — the caller could not produce one.
            $service->handleUpdate($update);
            self::fail('a sessionless presenter must be refused, not waved through');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('handleUpdate', $e->getMessage());
        }

        self::assertSame([], $writer->rows(), 'and nothing is bound');
    }

    /**
     * The other half: a nonce ISSUED TO ANOTHER SESSION is refused, with a
     * claim present and a matching user. This is the comparison itself, and it
     * is unconditional — a claim on the payload cannot stand in for it.
     */
    public function test_a_nonce_issued_to_another_session_is_refused_even_with_a_valid_claim(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $service = $this->attachService($nonces, $writer);
        $nonce = $nonces->issue(42); // issued to user 42
        MtprotoIntakeUser::$resolved = MtprotoIntakeUser::forId(42);

        $update = $this->update(
            $this->seal(['secure_data' => [], 'nonce' => $nonce]),
            MtprotoAction::sentMe(['email' => 'ada@example.test']),
        );

        try {
            // A DIFFERENT session presents it. The claim resolves to a real
            // user, and the nonce is genuine — the only thing wrong is who is
            // presenting it, which is the whole point.
            $service->login($update, 4242);
            self::fail('a nonce issued to another session must be refused');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('different session', $e->getMessage());
            self::assertStringNotContainsString('ada@example.test', $e->getMessage());
        }

        self::assertSame([], $writer->rows());
    }

    /**
     * And the positive control for the two tests above: the SAME update, with
     * the SAME claim, presented by the session the nonce was issued to, DOES
     * log in. Without this the two refusals could be green for the wrong
     * reason — a chain that refuses everything is not a chain that enforces a
     * comparison.
     */
    public function test_the_same_update_logs_in_for_the_session_the_nonce_was_issued_to(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $service = $this->attachService($nonces, $writer);
        $nonce = $nonces->issue(42);
        MtprotoIntakeUser::$resolved = MtprotoIntakeUser::forId(42);

        $result = $service->login($this->update(
            $this->seal(['secure_data' => [], 'nonce' => $nonce]),
            MtprotoAction::sentMe(['email' => 'ada@example.test']),
        ), 42);

        self::assertInstanceOf(LoginResult::class, $result);
        self::assertSame(
            self::SENDER_TG_ID,
            $result->tgUserId,
            'the sender came from the MTProto `from_id` peerUser — the update carries no `message.from.id`',
        );
        self::assertSame(
            [['key' => 'email', 'value' => 'ada@example.test']],
            MtprotoIntakeUser::$lookups,
            'the §6 plain_data leaf is the value the users table is asked for',
        );
        self::assertSame(['email'], $result->fieldsVerified);
        self::assertCount(1, $writer->rows());
    }

    // ── the SENDER coordinate is a real MTProto one (F1) ───────────────

    /**
     * The defect this file's fixture used to hide: the chain read
     * `message.from.id`, which is the Bot API `Message.from` — a shape a real
     * `Message` does not have. api.tl:121/122 spell the sender
     * `from_id:flags.8?Peer`, so a genuine `updateNewMessage` resolved to 0
     * and every MTProto login was refused no matter how correct its claims
     * were.
     *
     * Both spellings of the Peer are accepted — the generated DTO and the raw
     * wire object — and both name the user through the CONSTRUCTOR
     * `peerUser`, never through key presence.
     */
    public function test_the_mtproto_sender_resolves_from_from_id_in_both_spellings(): void
    {
        $dtoSpelling = ['_name' => 'updateNewMessage', 'message' => [
            '_' => 'messageService',
            'from_id' => new PeerUserData(self::SENDER_TG_ID),
            'action' => ['_name' => 'unused'],
        ]];

        $wireSpelling = ['_name' => 'updateNewMessage', 'message' => [
            '_' => 'messageService',
            'from_id' => ['_' => 'peerUser', 'user_id' => (string) self::SENDER_TG_ID],
            'action' => ['_name' => 'unused'],
        ]];

        self::assertSame(self::SENDER_TG_ID, SenderUserId::resolve($dtoSpelling));
        self::assertSame(self::SENDER_TG_ID, SenderUserId::resolve($wireSpelling));
    }

    /**
     * Only a `peerUser` names a USER, and passport is user-only — so the
     * MTProto spelling of the `sender_chat` refusal the Bot API side already
     * had: `peerChat` (a chat, not a user) and `peerChannel` (a broadcast) are
     * 0, which the chain turns into a LOUD refusal.
     */
    public function test_a_non_user_peer_is_refused_loudly_never_downgraded(): void
    {
        $nonUserPeers = [
            'peerChat' => ['_' => 'peerChat', 'chat_id' => 12345],
            'peerChannel' => ['_' => 'peerChannel', 'channel_id' => 12345],
            // key presence alone — no constructor at all, so it is not known
            // to be a peerUser and must not be read as one
            'unnamed' => ['user_id' => 999],
            // a bare bag of keys is never a peer
            'bare' => 'peerUser',
        ];

        foreach ($nonUserPeers as $label => $peer) {
            $update = ['message' => ['from_id' => $peer]];

            self::assertSame(0, SenderUserId::resolve($update), 'refused: '.$label);
        }

        // And through the real chain, the refusal is loud and names the type.
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->attachService($nonces);
        $nonce = $nonces->issue(42);
        MtprotoIntakeUser::$resolved = MtprotoIntakeUser::forId(42);

        $update = $this->update(
            $this->seal(['secure_data' => [], 'nonce' => $nonce]),
            MtprotoAction::sentMe(['email' => 'ada@example.test']),
        );
        $update['message']['from_id'] = ['_' => 'peerChat', 'chat_id' => 12345];

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('requires the sender telegram user id');

        $service->login($update, 42);
    }

    /**
     * A `peerChat` sender is refused on the ANONYMOUS chain too, from the same
     * single-sourced reader — the two chains cannot drift into reading
     * different coordinates.
     */
    public function test_the_anonymous_chain_refuses_a_non_user_peer_on_the_same_rule(): void
    {
        $update = ['message' => ['from_id' => ['_' => 'peerChannel', 'channel_id' => 7]]];

        self::assertSame(0, SenderUserId::resolve($update));
    }

    // ── a credential-bearing update ALWAYS meets the session refusal ────

    /**
     * The `values`-less credential action, end to end through `handleUpdate()`:
     * `values` is the §6 CLAIM channel, so its absence must not make the
     * update look credential-free. If the predicate said "no credentials",
     * `handleUpdate()` would return null here and the unbound-session refusal
     * would never fire — a silent path around the 5cbbde5b fix.
     */
    public function test_a_values_less_credential_action_still_reaches_the_session_refusal(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->attachService($nonces);

        $update = [
            '_' => 'updateNewMessage',
            'message' => [
                '_' => 'messageService',
                'from_id' => new PeerUserData(self::SENDER_TG_ID),
                'action' => [
                    '_' => 'messageActionSecureValuesSentMe',
                    // no `values` key at all
                    'credentials' => ['data' => 'd', 'hash' => 'h', 'secret' => 's'],
                ],
            ],
        ];

        try {
            $service->handleUpdate($update);
            self::fail('a credential-bearing update with no bound session must be refused');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('no session is bound', $e->getMessage());
        }

        // And it is refused, not returned as null: the intake saw credentials.
        self::assertTrue(EncryptedCredentials::updateCarriesCredentials($update));
    }

    /**
     * The same update with a session bound is refused one step later, loudly,
     * on "no verified claim" — the missing `values` channel is not silently
     * downgraded onto another coordinate.
     */
    public function test_a_values_less_credential_action_with_a_session_is_refused_on_the_claim(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->attachService($nonces);
        $nonce = $nonces->issue(42);
        MtprotoIntakeUser::$resolved = MtprotoIntakeUser::forId(42);

        $update = $this->update(
            $this->seal(['secure_data' => [], 'nonce' => $nonce]),
            // an action whose `values` vector is stripped after the fact
            MtprotoAction::sentMe(['email' => 'ada@example.test']),
        );
        $wire = $update['message']['action'];
        unset($wire['values']);
        $update['message']['action'] = $wire;

        try {
            $service->login($update, 42);
            self::fail('no claim channel means no claim, and no claim means a refusal');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('no Telegram-verified field', $e->getMessage());
        }

        self::assertSame([], MtprotoIntakeUser::$lookups, 'the users table was never consulted');
    }

    /**
     * The anonymous flow's own binding, on the MTProto shape: the marker is
     * consumed one-time and the credentials must have been produced for THIS
     * trip. A credentials blob minted for another flow is refused even with a
     * valid `plain_data` claim and a real user to find.
     */
    public function test_the_anonymous_flow_refuses_mtproto_credentials_minted_for_another_trip(): void
    {
        $pending = new MemoryPendingLoginRegistry(900);
        $ticket = $pending->issue(SessionFingerprint::of('session-a'));
        MtprotoIntakeUser::$lookups = [];

        $service = new PassportAnonymousLoginService(
            config: new PassportConfig([
                'enabled' => true,
                'anonymous_login' => true,
                'private_key_path' => $this->pemFile,
                'account_id' => 7,
                'allowed_types' => ['personal_details'],
                // Passport is a BOT API feature, so the chain refuses a host
                // with no bot behind it before the marker is spent. This test
                // is about the CREDENTIALS/TRIP binding, so it configures one.
                'bot_id' => 7_700_000_04,
                'bot_username' => 'teleframe_mtproto_test_bot',
            ]),
            keys: new PassportKeyLoader($this->pemFile),
            pending: $pending,
            writer: new MemoryBindingWriter,
            userClass: '',
            finder: static function (string $column, string $value): ?Model {
                MtprotoIntakeUser::$lookups[] = ['key' => $column, 'value' => $value];

                return MtprotoIntakeUser::$resolved;
            },
        );

        MtprotoIntakeUser::$resolved = MtprotoIntakeUser::forId(42);

        try {
            $service->login(
                $this->update(
                    $this->seal(['secure_data' => [], 'nonce' => 'a-nonce-from-another-flow']),
                    MtprotoAction::sentMe(['email' => 'ada@example.test']),
                ),
                $ticket->token(),
                SessionFingerprint::of('session-a'),
            );
            self::fail('credentials from another flow must be refused');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('this login flow', $e->getMessage());
            self::assertStringNotContainsString('ada@example.test', $e->getMessage());
        }

        self::assertSame([], MtprotoIntakeUser::$lookups, 'the users table is never reached');
    }

    // ── helpers ───────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $payload
     * @return array{data: string, hash: string, secret: string}
     */
    private function seal(array $payload): array
    {
        return $this->factory->seal($payload);
    }

    /**
     * A REAL MTProto update in the §1 shape: `updateNewMessage` →
     * `messageService` → `messageActionSecureValuesSentMe`, whose
     * `credentials` are the encrypted envelope and whose `values` sibling
     * carries the §6 claims.
     *
     * THE SENDER IS THE REAL ONE, and that is the point of the fixture's
     * history: this helper used to write `'message' => ['from' => ['id' => …]]`
     * — the Bot API `Message.from` spelling — onto an MTProto frame, and a
     * frame no Telegram client can produce. It made the suite green over a
     * chain that REFUSES every genuine MTProto update, because the chain read
     * `message.from.id` and a real `Message`/`messageService` spells its
     * sender `from_id:flags.8?Peer` (api.tl:121/122). So the sender here is
     * `peerUser#59511722 user_id:long`, built from the generated
     * `PeerUserData`, and the action sits on the `message` object itself,
     * which is where the `messageService` variant of `Message` puts it.
     *
     * @param  array{data: string, hash: string, secret: string}  $envelope
     * @return array<string, mixed>
     */
    private function update(array $envelope, MessageActionSecureValuesSentMeData $action): array
    {
        return [
            '_' => 'updateNewMessage',
            'update_id' => 1,
            'message' => [
                '_' => 'messageService',
                'id' => 7,
                'from_id' => new PeerUserData(self::SENDER_TG_ID),
                'peer_id' => new PeerUserData(self::SENDER_TG_ID),
                'date' => 1_757_000_000,
                'action' => MtprotoAction::toWire($action, $envelope),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function attachService(
        MemoryNonceRegistry $nonces,
        ?MemoryBindingWriter $writer = null,
        array $config = [],
    ): PassportLoginService {
        return new PassportLoginService(
            config: new PassportConfig(array_merge([
                'enabled' => true,
                'private_key_path' => $this->pemFile,
                'account_id' => 7,
                'allowed_types' => ['personal_details', 'email', 'phone'],
                'user_lookup' => 'field',
                'require_verified' => true,
            ], $config)),
            keys: new PassportKeyLoader($this->pemFile),
            nonces: $nonces,
            identity: new FieldIdentityResolver(
                userClass: MtprotoIntakeUser::class,
                emailColumn: 'email',
                phoneColumn: 'phone',
            ),
            writer: $writer ?? new MemoryBindingWriter,
        );
    }
}

/**
 * MtprotoAction — builds the `messageActionSecureValuesSentMe` values from the
 * GENERATED schema types and writes them in the spelling the intake consumes.
 *
 * It is typed on those generated classes on purpose: a factory that returned a
 * hand-built array could express a shape the specification does not have, and
 * the suite would go green against a coordinate Telegram never writes — the
 * false green this repository shipped twice. Here, `values` is a
 * `Vector<SecureValue>` of real `SecureValueData`, and `credentials` is a real
 * `SecureCredentialsEncryptedData`; `toWire()` has nothing to invent.
 *
 * `secure_data` inside the SEALED credentials is the §5 `Credentials` JSON and
 * is the ONLY place a document type may appear — never a claim.
 */
final class MtprotoAction
{
    /**
     * §6: `email` and `phone` become `secureValueTypeEmail` /
     * `secureValueTypePhone` values whose `plain_data` is `securePlainEmail` /
     * `securePlainPhone`. Every value list also carries the
     * `secureValueTypePersonalDetails` document, so "no claim" is expressible
     * as "the document is there and the claim is not" — the shape a
     * document-only response really has.
     *
     * @param  array{email?: string, phone?: string}  $claims
     */
    public static function sentMe(array $claims): MessageActionSecureValuesSentMeData
    {
        $values = [
            new SecureValueData(
                flags: 0,
                type: new SecureValueTypePersonalDetailsData,
                data: new SecureDataData(data: 'cipher', dataHash: 'hash', secret: 'secret'),
                frontSide: null,
                reverseSide: null,
                selfie: null,
                translation: null,
                files: null,
                plainData: null,
                hash: 'personal-details-hash',
            ),
        ];

        if (isset($claims['email'])) {
            $values[] = new SecureValueData(
                flags: 0,
                type: new SecureValueTypeEmailData,
                data: null,
                frontSide: null,
                reverseSide: null,
                selfie: null,
                translation: null,
                files: null,
                plainData: new SecurePlainEmailData($claims['email']),
                hash: 'email-hash',
            );
        }

        if (isset($claims['phone'])) {
            $values[] = new SecureValueData(
                flags: 0,
                type: new SecureValueTypePhoneData,
                data: null,
                frontSide: null,
                reverseSide: null,
                selfie: null,
                translation: null,
                files: null,
                plainData: new SecurePlainPhoneData($claims['phone']),
                hash: 'phone-hash',
            );
        }

        return new MessageActionSecureValuesSentMeData(
            values: $values,
            credentials: self::credentials('placeholder', 'placeholder', 'placeholder'),
        );
    }

    /**
     * The update-side array for a real action, with the sealed envelope in
     * place of the placeholder credentials `sentMe()` cannot know.
     *
     * @param  array{data: string, hash: string, secret: string}  $envelope
     * @return array<string, mixed>
     */
    public static function toWire(
        MessageActionSecureValuesSentMeData $action,
        array $envelope,
    ): array {
        $values = [];

        foreach ($action->values as $value) {
            $values[] = self::valueToWire($value);
        }

        return [
            '_' => 'messageActionSecureValuesSentMe',
            'values' => $values,
            'credentials' => [
                '_' => 'secureCredentialsEncrypted',
                'data' => $envelope['data'],
                'hash' => $envelope['hash'],
                'secret' => $envelope['secret'],
            ],
        ];
    }

    private static function valueToWire(SecureValueData $value): array
    {
        $wire = [
            '_' => 'secureValue',
            'type' => self::typeName($value),
            'hash' => $value->hash,
        ];

        if ($value->data !== null) {
            $wire['data'] = [
                '_' => 'secureData',
                'data' => $value->data->data,
                'data_hash' => $value->data->dataHash,
                'secret' => $value->data->secret,
            ];
        }

        if ($value->plainData !== null) {
            $wire['plain_data'] = $value->plainData instanceof SecurePlainEmailData
                ? ['_' => 'securePlainEmail', 'email' => $value->plainData->email]
                : ['_' => 'securePlainPhone', 'phone' => $value->plainData->phone];
        }

        return $wire;
    }

    /**
     * `type` is a `SecureValueType` — a BOXED TL type (`secureValueTypeEmail
     * #5b7c044e = SecureValueType`), so on the wire it is an object carrying
     * its constructor, never a bare string. The first revision of this helper
     * returned the bare name, and nothing caught it because the §7 type gate
     * was FAIL-OPEN (`$typeName === null` read as permission), so a `type`
     * the traversal could not name was allowed to carry a `plain_data`. The
     * gate now fails closed, and the fixture — not the gate — is what changed.
     *
     * @return array{_: string}
     */
    private static function typeName(SecureValueData $value): array
    {
        return ['_' => match ($value->type::class) {
            SecureValueTypeEmailData::class => 'secureValueTypeEmail',
            SecureValueTypePhoneData::class => 'secureValueTypePhone',
            default => 'secureValueTypePersonalDetails',
        }];
    }

    private static function credentials(string $data, string $hash, string $secret): SecureCredentialsEncryptedData
    {
        return new SecureCredentialsEncryptedData(data: $data, hash: $hash, secret: $secret);
    }
}

/**
 * A DB-free Eloquent Model the resolvers can hand back, recording every lookup
 * so a test can assert whether the users table was consulted at all.
 */
final class MtprotoIntakeUser extends Model
{
    public ?int $id = 42;

    public static ?self $resolved = null;

    /** @var list<array{key: string, value: mixed}> */
    public static array $lookups = [];

    public static function forId(int $id): self
    {
        $user = new self;
        $user->id = $id;
        $user->setAttribute('id', $id);

        return $user;
    }

    public static function query(): static
    {
        return new self;
    }

    public function where(string $key, mixed $value): static
    {
        self::$lookups[] = ['key' => $key, 'value' => $value];

        return $this;
    }

    public function first(): ?static
    {
        return self::$resolved;
    }
}
