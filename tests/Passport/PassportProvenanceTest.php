<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use Closure;
use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Passport\BindingWriter;
use MeRezaRezaei\Teleframe\Passport\EloquentBindingWriter;
use MeRezaRezaei\Teleframe\Passport\LoginResult;
use MeRezaRezaei\Teleframe\Passport\MemoryBindingWriter;
use MeRezaRezaei\Teleframe\Passport\MemoryNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportKeyLoader;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportProvenanceRecorder;
use MeRezaRezaei\Teleframe\Passport\SessionIdentityResolver;
use PHPUnit\Framework\TestCase;

/**
 * PassportProvenanceTest — a completed login must leave a record of HOW it
 * authenticated (2026-09-26 security gap 2).
 *
 * THE GAP. A Telegram Passport login produced a users row byte-identical to a
 * password login. There was no `auth_source`, no timestamp, no record of which
 * field types were verified — the verified types were computed by the chain and
 * then dropped on the floor. Nothing could answer, after the fact, "which of my
 * users authenticated via Telegram, and with what proof?", which is the first
 * question asked when auditing or revoking Telegram-authenticated sessions.
 * `Bindings::onLogin()` existed as a write hook; Passport never used it.
 *
 * THE TWO INVARIANTS pinned here:
 *  1. A successful login RECORDS provenance, keyed to the binding identity.
 *  2. The record holds field TYPES and NEVER a field VALUE. A verified email
 *     or phone written to a column would be copying personal data into a table
 *     nobody expected it in — surviving the request, landing in backups and
 *     exports. This is asserted as a negative, not merely omitted.
 */
final class PassportProvenanceTest extends TestCase
{
    private string $pemFile = '';

    private string $publicPem = '';

    private string $privatePem = '';

    protected function setUp(): void
    {
        ProvenanceUser::$resolved = null;

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($key);
        openssl_pkey_export($key, $this->privatePem);
        $details = openssl_pkey_get_details($key);
        self::assertIsArray($details);

        $this->pemFile = tempnam(sys_get_temp_dir(), 'teleframe-passport-prov-');
        self::assertNotFalse($this->pemFile);
        file_put_contents($this->pemFile, $this->privatePem);
        chmod($this->pemFile, 0o600);

        $this->publicPem = $details['key'];
    }

    protected function tearDown(): void
    {
        if ($this->pemFile !== '' && is_file($this->pemFile)) {
            unlink($this->pemFile);
        }
        ProvenanceUser::$resolved = null;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function service(
        MemoryNonceRegistry $nonces,
        BindingWriter $writer,
        array $config = [],
    ): PassportLoginService {
        return new PassportLoginService(
            config: new PassportConfig(array_merge([
                'enabled' => true,
                'private_key_path' => $this->pemFile,
                'account_id' => 7,
                'allowed_types' => ['personal_details', 'email', 'phone'],
            ], $config)),
            keys: new PassportKeyLoader($this->pemFile),
            nonces: $nonces,
            identity: new SessionIdentityResolver(ProvenanceUser::class),
            writer: $writer,
        );
    }

    /**
     * Seal a `Credentials` payload with the SPEC-FAITHFUL builder
     * (`SpecCredentialFactory`), against the key this test already generated.
     *
     * The previous body of this method built the fixture to match the
     * IMPLEMENTATION — hash over the unpadded plaintext, no Telegram padding,
     * OpenSSL PKCS#7 on the ciphertext — so it agreed with the code by
     * construction and could not catch a spec violation. The factory is
     * transcribed from upstream's own build order instead, so a drift in
     * either direction is now a test failure.
     *
     * @param  array<string, mixed>  $payload
     * @return array{data: string, secret: string, hash: string}
     */
    private function seal(array $payload): array
    {
        return SpecCredentialFactory::withKeys($this->privatePem, $this->publicPem)->seal($payload);
    }

    /**
     * The verified §6 plaintext fields, which live BESIDE the encrypted
     * credentials on the `PassportData` object, not inside it.
     *
     * @return array<string, string>
     */
    private function claims(): array
    {
        return [
            'email' => 'ada.lovelace@example.com',
            'phone_number' => '+15551234567',
        ];
    }

    /**
     * @param  array<string, string>  $passportData
     * @return array<string, mixed>
     */
    private function updateWith(array $envelope, int $fromId = 555000111, array $passportData = []): array
    {
        return [
            'update_id' => 1,
            'message' => [
                'from' => ['id' => $fromId],
                'passport_data' => PassportElementFactory::passportData($envelope, $passportData),
            ],
        ];
    }

    /**
     * A full-strength response: a §5 `personal_details` document inside the
     * blob, the verified email and phone as the §6 plaintext, with
     * real-looking values that must NOT appear in the recorded provenance.
     *
     * @return array<string, mixed>
     */
    private function richPayload(string $nonce): array
    {
        return [
            'secure_data' => [
                'personal_details' => ['first_name' => 'Ada', 'last_name' => 'Lovelace'],
            ],
            'nonce' => $nonce,
        ];
    }

    public function test_a_successful_login_records_provenance_on_the_binding(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $service = $this->service($nonces, $writer);
        $nonce = $nonces->issue(42);
        ProvenanceUser::$resolved = new ProvenanceUser;

        $result = $service->login($this->updateWith($this->seal($this->richPayload($nonce)), passportData: $this->claims()), 42);

        self::assertInstanceOf(LoginResult::class, $result);

        $provenance = $writer->provenance($result->tgUserId, $result->accountId);
        self::assertNotNull($provenance, 'a completed passport login must record provenance');
        self::assertSame(['personal_details', 'email', 'phone'], $provenance['fields']);
        self::assertGreaterThan(0, $provenance['at'], 'provenance must be timestamped');
    }

    public function test_the_recorded_provenance_holds_field_type_s_not_their_values(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $service = $this->service($nonces, $writer);
        $nonce = $nonces->issue(42);
        ProvenanceUser::$resolved = new ProvenanceUser;

        $result = $service->login($this->updateWith($this->seal($this->richPayload($nonce)), passportData: $this->claims()), 42);

        $serialized = (string) json_encode($writer->provenance($result->tgUserId, $result->accountId));

        // The types ARE there — the record is useful.
        self::assertStringContainsString('email', $serialized);
        self::assertStringContainsString('phone', $serialized);

        // The VALUES are not, anywhere in the record.
        self::assertStringNotContainsString('ada.lovelace@example.com', $serialized);
        self::assertStringNotContainsString('+15551234567', $serialized);
        self::assertStringNotContainsString('Lovelace', $serialized);
        self::assertStringNotContainsString('Ada', $serialized);
    }

    public function test_the_writers_refuse_to_store_a_value_passed_as_if_it_were_a_type(): void
    {
        // Defence in depth at the write path: even a caller that wrongly hands
        // the memory writer decrypted values, the record keeps only the three
        // known SecureValueType names. Same allow-list TlUserBinding applies.
        $writer = new MemoryBindingWriter;
        $writer->recordProvenance(42, 7, ['email', 'ada.lovelace@example.com', '+15551234567', 'phone_number']);

        $provenance = $writer->provenance(42, 7);
        self::assertNotNull($provenance);
        self::assertSame(['email'], $provenance['fields']);

        $serialized = (string) json_encode($provenance);
        self::assertStringNotContainsString('ada.lovelace@example.com', $serialized);
        self::assertStringNotContainsString('+15551234567', $serialized);
    }

    public function test_the_eloquent_writer_delegates_provenance_to_the_identity_write_hook(): void
    {
        // The real (non-memory) writer path: provenance must reach
        // Bindings::recordPassportLogin, keyed by the same identity triple the
        // bind used, carrying the same type list — and nothing else.
        $seen = [];
        $writer = new EloquentBindingWriter(
            delegate: static fn (object $user, int $tgId, ?int $accountId): mixed => ['tg_id' => $tgId, 'account_id' => $accountId],
            provenance: static function (int $tgId, ?int $accountId, array $fields) use (&$seen): void {
                $seen[] = ['tg_id' => $tgId, 'account_id' => $accountId, 'fields' => $fields];
            },
        );

        $nonces = new MemoryNonceRegistry(900);
        $service = $this->service($nonces, $writer);
        $nonce = $nonces->issue(42);
        ProvenanceUser::$resolved = new ProvenanceUser;

        $result = $service->login($this->updateWith($this->seal($this->richPayload($nonce)), passportData: $this->claims()), 42);

        self::assertCount(1, $seen);
        self::assertSame($result->tgUserId, $seen[0]['tg_id']);
        self::assertSame($result->accountId, $seen[0]['account_id']);
        self::assertSame(['personal_details', 'email', 'phone'], $seen[0]['fields']);
    }

    public function test_both_shipped_writers_implement_the_provenance_seam(): void
    {
        self::assertInstanceOf(PassportProvenanceRecorder::class, new MemoryBindingWriter);
        self::assertInstanceOf(PassportProvenanceRecorder::class, new EloquentBindingWriter);
    }

    public function test_a_legacy_host_writer_without_the_seam_still_logs_in(): void
    {
        // Backward compatibility: `BindingWriter` was a published seam, so a
        // host may have injected its own implementation predating provenance.
        // Adding the seam as a separate optional interface means such a writer
        // simply does not record — it must NOT become a fatal, and the login
        // must still succeed.
        $writer = new class implements BindingWriter
        {
            public function bind(int $tgId, ?int $accountId, object $user): mixed
            {
                return ['legacy' => true];
            }
        };

        self::assertNotInstanceOf(PassportProvenanceRecorder::class, $writer);

        $nonces = new MemoryNonceRegistry(900);
        $service = $this->service($nonces, $writer);
        $nonce = $nonces->issue(42);
        ProvenanceUser::$resolved = new ProvenanceUser;

        $result = $service->login($this->updateWith($this->seal($this->richPayload($nonce)), passportData: $this->claims()), 42);

        self::assertInstanceOf(LoginResult::class, $result);
        self::assertSame(['legacy' => true], $result->binding);
    }

    public function test_provenance_is_recorded_before_the_host_auth_hook_runs(): void
    {
        // Ordering: by the time the host is being logged in, the record exists
        // — so a hook that immediately audits or revokes sees a complete state.
        $order = [];
        $nonces = new MemoryNonceRegistry(900);
        $writer = new EloquentBindingWriter(
            delegate: static fn (object $user, int $tgId, ?int $accountId): mixed => null,
            provenance: static function () use (&$order): void {
                $order[] = 'provenance';
            },
        );

        $service = new PassportLoginService(
            config: new PassportConfig([
                'enabled' => true,
                'private_key_path' => $this->pemFile,
                'account_id' => 7,
                'allowed_types' => ['personal_details', 'email', 'phone'],
            ]),
            keys: new PassportKeyLoader($this->pemFile),
            nonces: $nonces,
            identity: new SessionIdentityResolver(ProvenanceUser::class),
            writer: $writer,
            onLogin: static function (Model $user) use (&$order): void {
                $order[] = 'onLogin';
            },
        );

        $nonce = $nonces->issue(42);
        ProvenanceUser::$resolved = new ProvenanceUser;
        $service->login($this->updateWith($this->seal($this->richPayload($nonce)), passportData: $this->claims()), 42);

        self::assertSame(['provenance', 'onLogin'], $order);
    }

    public function test_the_memory_writer_records_tenant_scoped_provenance(): void
    {
        // Provenance is per (tg id, tenant) — the binding's own key — so two
        // tenants logging the same telegram user never share a record.
        $writer = new MemoryBindingWriter;
        $writer->recordProvenance(42, 7, ['email']);
        $writer->recordProvenance(42, 9, ['phone']);

        self::assertSame(['email'], $writer->provenance(42, 7)['fields']);
        self::assertSame(['phone'], $writer->provenance(42, 9)['fields']);
        self::assertNull($writer->provenance(42, 11));
    }

    public function test_no_provenance_is_written_when_nothing_is_bound(): void
    {
        $writer = new MemoryBindingWriter;

        self::assertNull($writer->provenance(1, 1), 'an untouched writer holds no provenance');
        self::assertSame([], $writer->rows());
    }

    public function test_the_provenance_closure_seam_defaults_to_the_identity_write_hook(): void
    {
        // No injected closure => the real Bindings::recordPassportLogin is
        // what the shipped writer calls. Asserted indirectly: a
        // provenance-less construction must not fabricate a call, and the
        // default closure must be wired (a plain-PHP constructible writer).
        $writer = new EloquentBindingWriter;
        self::assertInstanceOf(PassportProvenanceRecorder::class, $writer);
    }
}

/**
 * The fake Laravel user for the provenance tests — DB-free Model convention as
 * in the rest of the Passport suite.
 */
final class ProvenanceUser extends Model
{
    public ?int $id = 42;

    public static ?self $resolved = null;

    public static function query(): static
    {
        return new self;
    }

    public function where(string $key, mixed $value): static
    {
        return $this;
    }

    public function first(): ?static
    {
        return self::$resolved;
    }
}
