<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Passport\EncryptedCredentials;
use MeRezaRezaei\Teleframe\Passport\MemoryBindingWriter;
use MeRezaRezaei\Teleframe\Passport\MemoryNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\MemoryPendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportAnonymousLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportCredentialsVerifier;
use MeRezaRezaei\Teleframe\Passport\PassportKeyLoader;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\SessionFingerprint;
use MeRezaRezaei\Teleframe\Passport\SessionIdentityResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * PassportCredentialsVerifierTest — THE TRANSPORT-INTEGRITY CHECK.
 *
 * core.telegram.org/passport states the check in the operator's own
 * imperative — "make sure that the credentials hash is equal to
 * SHA256( credentials_data )", with "all hashes represent as raw binary data,
 * not hexits" — and this file proves the three checks the class performs:
 *
 *   1. the `EncryptedCredentials` envelope;
 *   2. §4 `DataCredentials.data_hash` over a `SecureData.data` field;
 *   3. §4 `FileCredentials.file_hash` over downloaded bytes.
 *
 * ══ WHAT IS PINNED HERE, AND WHY EACH CASE IS A TEST ═══════════════════
 *
 * The failure this guards against is not "a check that can be bypassed" — it
 * is a check that is ALWAYS FALSE. Telegram sends every hash as 32 RAW bytes;
 * hash them with `hex` and you get 64 characters, base64 them and you get 44,
 * and `hash_equals` returns false on a length mismatch before it looks at a
 * single byte. Wire the comparison to either encoded spelling and the check
 * refuses every genuine login while still reading exactly like a working one —
 * a silent denial of service that no reviewer can see by reading it. So the
 * negative controls below are not decoration:
 *
 *   - `test_the_raw_binary_trap_is_mechanical` pins the trap as a fact about
 *     PHP: raw-vs-hex and raw-vs-base64-string are both false for a blob that
 *     is CORRECT, and only raw-vs-raw is true;
 *   - `test_a_hex_digest_is_refused_on_the_envelope` and its two siblings feed
 *     the verifier the SAME blob carrying the SAME plaintext under a
 *     differently-ENCODED digest, and require a refusal every time.
 *
 * One of those envelope-level controls is worth spelling out because of how
 * it is built: a SHA-256 hexit string is 64 characters drawn entirely from the
 * base64 alphabet, so it SURVIVES a strict `base64_decode` and arrives as 48
 * bytes. Its refusal therefore genuinely exercises the 32-byte gate rather
 * than tripping an alphabet check by accident — which is exactly the accident
 * a hand-rolled decoder would have relied on.
 *
 * ══ WHAT IS DELIBERATELY ABSENT, SO NOBODY ADDS IT LATER ════════════════
 *
 * There is no test that `EncryptedPassportElement.hash` fails, because there
 * cannot be one: that field is CARRIED by Telegram and DERIVED by nobody.
 * `telegram-bot-api/Client.cpp:3680` — the only line in Telegram's Bot API
 * server that produces it — is `object("hash", td::base64_encode(
 * element_->hash_));`, a passthrough, and its only consumer
 * (`Client.cpp:11754-11759`) base64-decodes it purely to name the element in
 * a `PassportElementErrorUnspecified`. `data_check_key` has zero hits across
 * GitHub; the ~750 `data_check` hits are the unrelated Mini-App initData
 * feature. A test asserting this field is verifiable would be pinning a
 * fiction, and the old "Critical" that wanted one was wrong.
 *
 * ══ AND WHAT THE CHECK IS NOT ═══════════════════════════════════════════
 *
 * This authenticates the BLOB. It does not authenticate the identity claim the
 * blob carries — only Telegram can, via the `updateBotPassportData` sender
 * edge and the one-time-nonce binding. A green run here is not "the Critical
 * is fixed".
 *
 * Plain-PHP throughout: throwaway keypair per test, nothing on disk but a
 * 0600 temp PEM the loader insists on, no container, no database.
 */
final class PassportCredentialsVerifierTest extends TestCase
{
    private string $pemFile = '';

    private string $privatePem = '';

    private string $publicPem = '';

    private SpecCredentialFactory $factory;

    protected function setUp(): void
    {
        PassportIntegrityUser::$resolved = null;
        PassportIntegrityUser::$lookups = [];

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($key);
        self::assertTrue(openssl_pkey_export($key, $this->privatePem));
        $details = openssl_pkey_get_details($key);
        self::assertIsArray($details);
        $this->publicPem = $details['key'];

        $this->pemFile = tempnam(sys_get_temp_dir(), 'teleframe-passport-integrity-');
        self::assertNotFalse($this->pemFile);
        file_put_contents($this->pemFile, $this->privatePem);
        chmod($this->pemFile, 0o600);

        $this->factory = SpecCredentialFactory::withKeys($this->privatePem, $this->publicPem);
    }

    protected function tearDown(): void
    {
        if ($this->pemFile !== '' && is_file($this->pemFile)) {
            unlink($this->pemFile);
        }
        PassportIntegrityUser::$resolved = null;
        PassportIntegrityUser::$lookups = [];
    }

    // ── 1. the pass path: a CORRECT blob is accepted at all three levels ──

    public function test_a_correct_envelope_passes(): void
    {
        $envelope = $this->factory->seal($this->credentialsPayload('a-nonce'));

        // No exception IS the assertion: there is no return value to inspect,
        // because this class answers one question and returns nothing.
        PassportCredentialsVerifier::assertCredentialsEnvelope(
            EncryptedCredentials::fromArray($envelope),
            $this->privatePem,
        );

        self::assertTrue(true, 'a spec-correct envelope reached the end of the verifier');
    }

    public function test_correct_data_credentials_pass(): void
    {
        $json = '{"first_name":"Ada","last_name":"Lovelace"}';
        $sealed = $this->factory->sealDataCredentials($json);

        PassportCredentialsVerifier::assertDataCredentials(
            $sealed['data'],
            $sealed['hash'],
            $sealed['secret'],
            $this->privatePem,
        );

        self::assertTrue(true, 'a spec-correct DataCredentials reached the end of the verifier');
    }

    public function test_correct_file_credentials_pass(): void
    {
        $bytes = "\xFF\xD8\xFF\xE0".random_bytes(64);
        $sealed = $this->factory->sealFileCredentials($bytes);

        PassportCredentialsVerifier::assertFileCredentials(
            $sealed['content'],
            $sealed['hash'],
            $sealed['secret'],
            $this->privatePem,
        );

        self::assertTrue(true, 'a spec-correct FileCredentials reached the end of the verifier');
    }

    // ── 2. the refuse path: a TAMPERED blob never reaches the end ─────────

    public function test_a_tampered_ciphertext_is_refused(): void
    {
        $envelope = $this->factory->seal($this->credentialsPayload('a-nonce'));

        // Flip one bit of the CIPHERTEXT. The KDF input is untouched, so the
        // key and IV are still correct and the AES leg still runs — which is
        // what makes this the honest tamper: the ONLY thing that can catch it
        // is the digest comparison, because nothing about the envelope's shape
        // changed.
        $raw = base64_decode($envelope['data'], true);
        self::assertIsString($raw);
        $raw[0] = $raw[0] === "\x00" ? "\x01" : "\x00";
        $envelope['data'] = base64_encode($raw);

        $this->assertRefused(
            fn () => PassportCredentialsVerifier::assertCredentialsEnvelope(
                EncryptedCredentials::fromArray($envelope),
                $this->privatePem,
            ),
        );
    }

    public function test_a_tampered_hash_is_refused(): void
    {
        $envelope = $this->factory->seal($this->credentialsPayload('a-nonce'));

        // Another well-formed 44-char base64 blob, so the refusal is the
        // DIGEST comparison and not a malformed-field check: this swap keeps
        // every length and alphabet check happy and changes nothing but which
        // 32 bytes are compared.
        $envelope['hash'] = base64_encode(str_repeat("\x00", 32));

        $this->assertRefused(
            fn () => PassportCredentialsVerifier::assertCredentialsEnvelope(
                EncryptedCredentials::fromArray($envelope),
                $this->privatePem,
            ),
        );
    }

    public function test_tampered_data_and_file_credentials_are_refused(): void
    {
        $data = $this->factory->sealDataCredentials('{"first_name":"Ada"}');
        $raw = $data['data'];
        $raw[0] = $raw[0] === "\x00" ? "\x01" : "\x00";
        $data['data'] = $raw;

        $this->assertRefused(
            fn () => PassportCredentialsVerifier::assertDataCredentials(
                $data['data'],
                $data['hash'],
                $data['secret'],
                $this->privatePem,
            ),
        );

        $file = $this->factory->sealFileCredentials("\xFF\xD8original");
        $rawFile = $file['content'];
        $rawFile[0] = $rawFile[0] === "\x00" ? "\x01" : "\x00";
        $file['content'] = $rawFile;

        $this->assertRefused(
            fn () => PassportCredentialsVerifier::assertFileCredentials(
                $file['content'],
                $file['hash'],
                $file['secret'],
                $this->privatePem,
            ),
        );
    }

    // ── 3. THE RAW-BINARY TRAP, as a fact about PHP ──────────────────────

    /**
     * THE negative control, stated as arithmetic rather than as a fixture.
     *
     * These four lines are the whole reason this class exists. A SHA-256 raw
     * digest is 32 bytes; its hexit spelling is 64 characters and its base64
     * spelling is 44. `hash_equals` returns false immediately on a length
     * mismatch, so BOTH encoded spellings lose against a correct blob — the
     * check does not weaken, it INVERTS, and every genuine login is refused by
     * code that still reads like a working check. Only the raw-vs-raw
     * comparison can succeed, and that is the only one written down.
     */
    public function test_the_raw_binary_trap_is_mechanical(): void
    {
        $padded = $this->factory->pad('{"nonce":"n","secure_data":{}}');
        $raw = hash('sha256', $padded, true);

        self::assertSame(32, strlen($raw), 'a SHA-256 raw digest is 32 bytes');
        self::assertSame(64, strlen(hash('sha256', $padded)), 'its hexit spelling is 64 characters');
        self::assertSame(44, strlen(base64_encode($raw)), 'its base64 spelling is 44 characters');

        // Both WRONG spellings fail against a blob that is entirely correct.
        self::assertFalse(hash_equals($raw, hash('sha256', $padded)), 'raw vs hex is always false');
        self::assertFalse(hash_equals($raw, base64_encode($raw)), 'raw vs the base64 STRING is always false');

        // The one comparison that can pass.
        self::assertTrue(hash_equals($raw, hash('sha256', $padded, true)), 'raw vs raw');
    }

    /**
     * THE SAME BLOB, hashed with `hex` instead of raw binary, is REFUSED.
     *
     * Built deliberately so the refusal is the 32-byte gate and not an alphabet
     * accident: SHA-256 hexits are 64 characters drawn entirely from the
     * base64 alphabet, so this string survives a STRICT `base64_decode` and
     * arrives as 48 bytes. A hand-rolled decoder would have called that valid;
     * this one refuses it, because 48 is not 32.
     */
    public function test_a_hex_digest_is_refused_on_the_envelope(): void
    {
        $json = (string) json_encode($this->credentialsPayload('a-nonce'), JSON_THROW_ON_ERROR);
        $padded = $this->factory->pad($json);
        $hex = hash('sha256', $padded);

        self::assertSame(64, strlen($hex));
        self::assertNotFalse(base64_decode($hex, true), 'hexits are legal base64 — the gate must be the length, not the alphabet');
        self::assertSame(48, strlen((string) base64_decode($hex, true)));

        // An envelope whose ONLY defect is that its digest was hexed. The data
        // and the secret are the correct ones from a correct seal.
        $sealed = $this->factory->sealPadded($padded);
        $sealed['hash'] = $hex;

        $this->assertRefused(
            fn () => PassportCredentialsVerifier::assertCredentialsEnvelope(
                EncryptedCredentials::fromArray($sealed),
                $this->privatePem,
            ),
        );
    }

    public function test_a_hex_digest_is_refused_on_the_nested_levels(): void
    {
        $padded = $this->factory->pad('{"first_name":"Ada"}');
        $sealed = $this->factory->sealDataCredentials('{"first_name":"Ada"}');

        $this->assertRefused(
            fn () => PassportCredentialsVerifier::assertDataCredentials(
                $sealed['data'],
                hash('sha256', $padded), // 64 hexits where 32 raw bytes belong
                $sealed['secret'],
                $this->privatePem,
            ),
        );

        $fileSealed = $this->factory->sealFileCredentials('some-bytes');
        $filePadded = $this->factory->pad('some-bytes');

        $this->assertRefused(
            fn () => PassportCredentialsVerifier::assertFileCredentials(
                $fileSealed['content'],
                hash('sha256', $filePadded),
                $fileSealed['secret'],
                $this->privatePem,
            ),
        );
    }

    /**
     * The base64 STRING of a correct digest — the other half of the trap, and
     * the more seductive one, because it is the exact spelling the ENVELOPE
     * carries on the wire. Decoding it a second time is the mistake: the field
     * is already base64, and `assertCredentialsEnvelope()` decodes it exactly
     * once.
     */
    public function test_a_double_encoded_digest_is_refused(): void
    {
        $sealed = $this->factory->sealDataCredentials('{"first_name":"Ada"}');

        $this->assertRefused(
            fn () => PassportCredentialsVerifier::assertDataCredentials(
                $sealed['data'],
                base64_encode($sealed['hash']), // 44 characters where 32 bytes belong
                $sealed['secret'],
                $this->privatePem,
            ),
        );
    }

    /**
     * @return iterable<string, array{0: int}>
     */
    public static function wrongLengthDigests(): iterable
    {
        yield '31 bytes' => [31];
        yield '33 bytes' => [33];
        yield '16 bytes' => [16];
        yield '0 bytes' => [0];
    }

    #[DataProvider('wrongLengthDigests')]
    public function test_a_digest_of_the_wrong_length_is_refused(int $length): void
    {
        $sealed = $this->factory->sealDataCredentials('{"first_name":"Ada"}');

        $this->assertRefused(
            fn () => PassportCredentialsVerifier::assertDataCredentials(
                $sealed['data'],
                str_repeat("\x01", $length),
                $sealed['secret'],
                $this->privatePem,
            ),
        );
    }

    // ── 4. ONE ERROR, ALWAYS: the refusal must not name the failing step ──

    /**
     * An attacker probing this endpoint learns nothing from WHICH check
     * failed. Four structurally different failures — a tampered envelope, a
     * tampered nested ciphertext, a hex digest and a double-encoded digest —
     * must produce the identical message, exactly as `PassportDecryptor`'s
     * single `DECRYPT_ERROR` does.
     */
    public function test_every_refusal_carries_the_same_message(): void
    {
        $messages = [];

        $envelope = $this->factory->seal($this->credentialsPayload('a-nonce'));
        $raw = (string) base64_decode($envelope['data'], true);
        $raw[0] = $raw[0] === "\x00" ? "\x01" : "\x00";
        $envelope['data'] = base64_encode($raw);

        $collect = function (callable $run) use (&$messages): void {
            try {
                $run();
                self::fail('this input must be refused');
            } catch (RuntimeException $e) {
                $messages[] = $e->getMessage();
            }
        };

        $collect(fn () => PassportCredentialsVerifier::assertCredentialsEnvelope(
            EncryptedCredentials::fromArray($envelope),
            $this->privatePem,
        ));

        $sealed = $this->factory->sealDataCredentials('{"first_name":"Ada"}');

        $collect(fn () => PassportCredentialsVerifier::assertDataCredentials(
            $sealed['data'],
            hash('sha256', 'anything'),
            $sealed['secret'],
            $this->privatePem,
        ));

        $collect(fn () => PassportCredentialsVerifier::assertFileCredentials(
            $sealed['data'],
            str_repeat("\x01", 31),
            $sealed['secret'],
            $this->privatePem,
        ));

        $collect(fn () => PassportCredentialsVerifier::assertCredentialsEnvelope(
            EncryptedCredentials::fromArray([
                'data' => 'not base64 $$$',
                'hash' => 'also not base64 $$$',
                'secret' => 'nor this $$$',
            ]),
            $this->privatePem,
        ));

        self::assertCount(4, $messages);
        self::assertSame([PassportCredentialsVerifier::INTEGRITY_ERROR], array_values(array_unique($messages)));
    }

    // ── 5. THE WIRING: a failing check REFUSES THE LOGIN, on BOTH paths ───

    public function test_the_authenticated_chain_accepts_a_correct_blob(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $nonce = $nonces->issue(42);
        PassportIntegrityUser::$resolved = new PassportIntegrityUser;

        $result = $this->authenticatedService($nonces)->login(
            $this->update($this->factory->seal($this->credentialsPayload($nonce))),
            42,
        );

        self::assertSame(987654321, $result->tgUserId);
        self::assertSame(42, $result->user->id);
    }

    public function test_the_authenticated_chain_refuses_a_tampered_blob(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $nonce = $nonces->issue(42);
        PassportIntegrityUser::$resolved = new PassportIntegrityUser;

        $envelope = $this->factory->seal($this->credentialsPayload($nonce));
        $raw = (string) base64_decode($envelope['data'], true);
        $raw[0] = $raw[0] === "\x00" ? "\x01" : "\x00";
        $envelope['data'] = base64_encode($raw);

        $refused = false;
        try {
            $this->authenticatedService($nonces)->login($this->update($envelope), 42);
        } catch (PassportLoginException $e) {
            $refused = true;
            // The chain's wrapper preserves the original cause, which is what
            // `PassportLoginServiceTest`'s never-swallow contract asserts.
            self::assertInstanceOf(RuntimeException::class, $e->getPrevious());
            self::assertSame(PassportCredentialsVerifier::INTEGRITY_ERROR, $e->getPrevious()->getMessage());
        }

        self::assertTrue($refused, 'a tampered envelope must not produce a login');
        self::assertSame([], PassportIntegrityUser::$lookups, 'the user table was never touched');
    }

    public function test_the_anonymous_chain_refuses_a_tampered_blob(): void
    {
        $pending = new MemoryPendingLoginRegistry(60);
        $fingerprint = SessionFingerprint::of('session-a');
        $service = new PassportAnonymousLoginService(
            config: new PassportConfig([
                'enabled' => true,
                'anonymous_login' => true,
                'bot_id' => 7_700_000_03,
                'bot_username' => 'teleframe_integrity_bot',
                'private_key_path' => $this->pemFile,
            ]),
            keys: new PassportKeyLoader($this->pemFile),
            pending: $pending,
            writer: new MemoryBindingWriter,
            userClass: '',
            finder: static fn (string $column, string $value): ?PassportIntegrityUser => new PassportIntegrityUser,
        );

        $ticket = $service->start($fingerprint);

        $envelope = $this->factory->seal($this->credentialsPayload($ticket->nonce()));
        $raw = (string) base64_decode($envelope['data'], true);
        $raw[0] = $raw[0] === "\x00" ? "\x01" : "\x00";
        $envelope['data'] = base64_encode($raw);

        $refused = false;
        try {
            $service->login($this->update($envelope), $ticket->token(), $fingerprint);
        } catch (PassportLoginException $e) {
            $refused = true;
            self::assertInstanceOf(RuntimeException::class, $e->getPrevious());
            self::assertSame(PassportCredentialsVerifier::INTEGRITY_ERROR, $e->getPrevious()->getMessage());
        }

        self::assertTrue($refused, 'a tampered envelope must not produce an anonymous login');
    }

    public function test_the_anonymous_chain_accepts_a_correct_blob(): void
    {
        $pending = new MemoryPendingLoginRegistry(60);
        $fingerprint = SessionFingerprint::of('session-a');
        $service = new PassportAnonymousLoginService(
            config: new PassportConfig([
                'enabled' => true,
                'anonymous_login' => true,
                'bot_id' => 7_700_000_03,
                'bot_username' => 'teleframe_integrity_bot',
                'private_key_path' => $this->pemFile,
            ]),
            keys: new PassportKeyLoader($this->pemFile),
            pending: $pending,
            writer: new MemoryBindingWriter,
            userClass: '',
            finder: static fn (string $column, string $value): ?PassportIntegrityUser => new PassportIntegrityUser,
        );

        $ticket = $service->start($fingerprint);

        $result = $service->login(
            $this->update($this->factory->seal($this->credentialsPayload($ticket->nonce()))),
            $ticket->token(),
            $fingerprint,
        );

        self::assertSame(987654321, $result->tgUserId);
        self::assertSame(['personal_details', 'email'], $result->fieldsVerified);
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function assertRefused(callable $run): void
    {
        $refused = false;
        try {
            $run();
        } catch (RuntimeException $e) {
            $refused = true;
            self::assertSame(PassportCredentialsVerifier::INTEGRITY_ERROR, $e->getMessage());
        }

        self::assertTrue($refused, 'the verifier must refuse this input');
    }

    private function authenticatedService(MemoryNonceRegistry $nonces): PassportLoginService
    {
        return new PassportLoginService(
            config: new PassportConfig([
                'enabled' => true,
                'private_key_path' => $this->pemFile,
                'account_id' => 7,
                'allowed_types' => ['personal_details', 'email', 'phone'],
            ]),
            keys: new PassportKeyLoader($this->pemFile),
            nonces: $nonces,
            identity: new SessionIdentityResolver(PassportIntegrityUser::class),
            writer: new MemoryBindingWriter,
        );
    }

    /**
     * @return array{secure_data: array<string, array<string, string>>, nonce: string}
     */
    private function credentialsPayload(string $nonce): array
    {
        return [
            'secure_data' => [
                'personal_details' => ['first_name' => 'Ada', 'last_name' => 'Lovelace'],
            ],
            'nonce' => $nonce,
        ];
    }

    /**
     * A Bot API update carrying the sealed credentials and the §6 plaintext
     * verified claim beside them.
     *
     * @param  array{data: string, secret: string, hash: string}  $envelope
     * @return array<string, mixed>
     */
    private function update(array $envelope): array
    {
        return [
            'update_id' => 1,
            'message' => [
                'from' => ['id' => 987654321],
                'passport_data' => PassportElementFactory::passportData(
                    $envelope,
                    ['email' => 'ada@example.com'],
                ),
            ],
        ];
    }
}

/**
 * A minimal Eloquent stand-in, declared HERE rather than borrowed from
 * `PassportLoginServiceTest`: that file declares its own `FakePassportUser`
 * at the bottom, so the class only exists once PHPUnit has loaded that file,
 * and a test class run on its own would die on a missing class instead of on
 * anything it is actually testing. One definition per file that needs it.
 */
final class PassportIntegrityUser extends Model
{
    public ?int $id = 42;

    /** @var ?self the row resolve() hands back (null = unresolved) */
    public static ?self $resolved = null;

    /** @var list<array{key: string, value: int}> */
    public static array $lookups = [];

    public static function query(): static
    {
        return new self;
    }

    public function where(string $key, mixed $value): static
    {
        self::$lookups[] = ['key' => $key, 'value' => (int) $value];

        return $this;
    }

    public function first(): ?static
    {
        return self::$resolved;
    }
}
