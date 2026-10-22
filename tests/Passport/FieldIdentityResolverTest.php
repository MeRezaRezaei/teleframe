<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Passport\FieldIdentityResolver;
use MeRezaRezaei\Teleframe\Passport\PassportPayload;
use MeRezaRezaei\Teleframe\Passport\PassportVerifiedClaims;
use PHPUnit\Framework\TestCase;

/**
 * FieldIdentityResolver (Cycle 18 completion): the mode `IdentityResolver`
 * documents but which shipped only as a config option that threw
 * "needs FieldIdentityResolver (wave 2)". An advertised option that cannot work
 * is the same defect class as the config-publishing bug — a promise the code
 * does not keep.
 *
 * These tests are mostly about REFUSAL, because that is where a field-mode
 * identity lookup does damage: a payload matching on the wrong key, or on two
 * keys that disagree, would let the wrong human into the wrong account.
 *
 * The rule the refusals serve is one rule (2026-09-26): a CLAIM MAY CONFIRM
 * THE BOUND USER, NEVER SELECT ONE. So the resolver refuses a null session
 * outright and refuses a match whose row is not the session's user — including
 * when the claim is Telegram-verified and the row is real.
 *
 * THE FIXTURES CHANGED SHAPE ON 2026-09-26, and the reason is the point of
 * this suite after the audit. The claim is now built the way §6 says Telegram
 * builds it — as the `EncryptedPassportElement` plaintext in
 * `PassportData.data[]`, handed to
 * `PassportPayload` as a `PassportVerifiedClaims`. The `payload()` helper used
 * to write `['email' => ...]` into the DECRYPTED blob and
 * `secure_data.email.address` into `secure_data`; per §5 the `Credentials`
 * JSON is `{secure_data, nonce}` and `email` is not a §5 `secure_data` key at
 * all, so those fixtures described a protocol that does not exist and the
 * tests passed by agreeing with the implementation about it. The old shapes
 * are kept below as NEGATIVE fixtures: they must now resolve null.
 */
final class FieldIdentityResolverTest extends TestCase
{
    /**
     * A payload as Telegram actually sends it: a host-issued nonce is always
     * present (PassportPayload refuses a payload without one), and the
     * verified email / phone are the §6 element PLAINTEXT — an element in
     * `PassportData.data[]` typed `email` / `phone_number` — beside that
     * element's own encrypted `data`, and not inside `secure_data`.
     *
     * @param  array<string, string>  $claims  verified claims, wrapped into elements
     */
    private static function payload(array $claims = []): PassportPayload
    {
        return new PassportPayload(
            ['nonce' => 'nonce-abc', 'secure_data' => ['personal_details' => ['first_name' => 'Ada']]],
            PassportElementFactory::claims($claims),
        );
    }

    /**
     * The row a lookup hands back: an Eloquent Model carrying the key the
     * BOUND session is id 42 under. Field mode is a cross-check on the bound
     * user, so a resolved row has to be identifiable and has to BE that user.
     */
    private static function user(int $id = 42): Model
    {
        $user = new class extends Model {};
        $user->id = $id;
        $user->setAttribute('id', $id);

        return $user;
    }

    public function test_it_matches_a_verified_email_through_the_injected_seam(): void
    {
        $seen = [];
        $user = self::user();

        $resolver = new FieldIdentityResolver(
            finder: function (string $column, string $value) use (&$seen, $user): ?Model {
                $seen[] = [$column, $value];

                return $user;
            },
        );

        $resolved = $resolver->resolve(self::payload(['email' => 'a@example.com']), 42);

        self::assertSame($user, $resolved);
        self::assertSame([['email', 'a@example.com']], $seen, 'the payload email must be the value looked up');
    }

    public function test_a_phone_payload_uses_the_phone_column(): void
    {
        $seen = [];
        $resolver = new FieldIdentityResolver(
            finder: function (string $column, string $value) use (&$seen): ?Model {
                $seen[] = [$column, $value];

                return null;
            },
        );

        $resolver->resolve(self::payload(['phone_number' => '+15551234567']), 42);

        self::assertSame([['phone', '+15551234567']], $seen);
    }

    /**
     * THE DEFECT, AS A FIXTURE. A payload whose only identity is a bare
     * top-level `email` inside the DECRYPTED blob — a shape the previous
     * implementation read and this one must not. Per §5 the `Credentials` JSON
     * is `{secure_data, nonce}`, so there is no such field. The lookup must
     * never be issued and nothing may resolve.
     */
    public function test_a_claim_inside_the_encrypted_blob_is_not_read(): void
    {
        $calls = 0;
        $resolver = new FieldIdentityResolver(
            finder: function () use (&$calls): ?Model {
                $calls++;

                return new class extends Model {};
            },
        );

        $resolved = $resolver->resolve(new PassportPayload([
            'nonce' => 'nonce-abc',
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'email' => 'attacker@example.com',
        ]), 42);

        self::assertNull($resolved, 'a non-spec location is not an identity');
        self::assertSame(0, $calls, 'and it must not even reach the lookup');
    }

    /**
     * The same, for the OTHER non-spec location: `secure_data.email`. Per §5
     * `email` is not a `secure_data` key at all, so an entry under it is not
     * an identity this package may read — whatever the host's allow-list says.
     */
    public function test_a_secure_data_email_entry_is_not_read_as_an_identity(): void
    {
        $seen = [];
        $resolver = new FieldIdentityResolver(
            finder: function (string $column, string $value) use (&$seen): ?Model {
                $seen[] = [$column, $value];

                return self::user();
            },
        );

        $resolved = $resolver->resolve(new PassportPayload([
            'nonce' => 'nonce-abc',
            'secure_data' => ['email' => ['address' => 'attacker@example.com']],
        ], PassportElementFactory::claims(['email' => 'real@example.com'])), 42);

        // Only the §6 value is ever looked up: the secure_data entry is
        // ignored entirely, not preferred, not compared.
        self::assertInstanceOf(Model::class, $resolved);
        self::assertSame([['email', 'real@example.com']], $seen);
    }

    public function test_a_secure_data_email_entry_alone_resolves_nothing(): void
    {
        $calls = 0;
        $resolver = new FieldIdentityResolver(
            finder: function () use (&$calls): ?Model {
                $calls++;

                return self::user();
            },
        );

        self::assertNull($resolver->resolve(new PassportPayload([
            'nonce' => 'nonce-abc',
            'secure_data' => ['email' => ['address' => 'attacker@example.com']],
        ]), 42));
        self::assertSame(0, $calls);
    }

    public function test_a_payload_carrying_both_email_and_phone_is_refused(): void
    {
        // Self-inconsistent evidence is a problem to surface, not a preference
        // to resolve. Picking a winner here is how the wrong account gets
        // logged into.
        $calls = 0;
        $resolver = new FieldIdentityResolver(
            finder: function () use (&$calls): ?Model {
                $calls++;

                return new class extends Model {};
            },
        );

        $resolved = $resolver->resolve(
            self::payload(['email' => 'a@example.com', 'phone_number' => '+15551234567']),
            42,
        );

        self::assertNull($resolved, 'a payload with both fields must not resolve');
        self::assertSame(0, $calls, 'it must not even reach the lookup');
    }

    public function test_a_payload_with_neither_field_resolves_null(): void
    {
        $resolver = new FieldIdentityResolver(finder: fn (): ?Model => self::user());

        self::assertNull($resolver->resolve(self::payload(), 42));
    }

    public function test_no_match_resolves_null_so_the_service_throws_loud(): void
    {
        $resolver = new FieldIdentityResolver(finder: fn (): ?Model => null);

        self::assertNull($resolver->resolve(self::payload(['email' => 'nobody@example.com']), 42));
    }

    public function test_an_unknown_user_class_resolves_null_instead_of_fatalling(): void
    {
        // No injected seam: this exercises the guarded Eloquent path with a
        // class name that does not exist. A typo must resolve to null (service
        // throws loud) and never fatal on an unknown symbol.
        $resolver = new FieldIdentityResolver(userClass: 'App\\Models\\DefinitelyNotAClass');

        self::assertNull($resolver->resolve(self::payload(['email' => 'a@example.com']), 42));
    }

    public function test_a_seam_returning_a_non_model_is_treated_as_no_match(): void
    {
        $resolver = new FieldIdentityResolver(
            finder: fn (): mixed => new \stdClass,
        );

        self::assertNull(
            $resolver->resolve(self::payload(['email' => 'a@example.com']), 42),
            'a seam that returns the wrong type must not leak through as a user',
        );
    }

    // ── the reconciled rule: a claim CONFIRMS the bound user, never SELECTS one

    public function test_it_refuses_a_null_session_without_reaching_the_lookup(): void
    {
        // THE TAKEOVER. Handed no session at all, the previous version ignored
        // the id it was given and returned the row the email claim named — so
        // the shipped `user_lookup=field` configuration let anyone holding a
        // stolen nonce be logged in as the account that nonce was issued to.
        $calls = 0;
        $resolver = new FieldIdentityResolver(
            finder: function () use (&$calls): ?Model {
                $calls++;

                return self::user();
            },
        );

        $resolved = $resolver->resolve(self::payload(['email' => 'victim@example.com']), null);

        self::assertNull($resolved, 'no bound session means no user, whatever the claim says');
        self::assertSame(0, $calls, 'and the lookup must never be issued');
    }

    public function test_it_refuses_a_claim_that_names_another_account(): void
    {
        // The lookup succeeds and the row is real — it is simply not the
        // session the nonce was issued to. Refused: this is the attack shape
        // with a matching email, not a login.
        $resolver = new FieldIdentityResolver(finder: fn (): ?Model => self::user(99));

        self::assertNull(
            $resolver->resolve(self::payload(['email' => 'victim@example.com']), 42),
            'a claim may confirm the bound user, never select a different one',
        );
    }

    public function test_it_refuses_a_row_it_cannot_identify(): void
    {
        // No key at all: an unidentifiable row cannot be shown to BE the bound
        // user, so it is not accepted as one.
        $resolver = new FieldIdentityResolver(finder: fn (): ?Model => new class extends Model {});

        self::assertNull($resolver->resolve(self::payload(['email' => 'a@example.com']), 42));
    }

    public function test_a_digit_string_key_matching_the_bound_session_is_accepted(): void
    {
        // Hosts key their users table by a uuid or a string id; a digit string
        // is compared without a regex (src/Teleframe is zero-regex).
        $user = new class extends Model {};
        $user->setAttribute('id', '42');
        $resolver = new FieldIdentityResolver(finder: fn (): ?Model => $user);

        self::assertSame($user, $resolver->resolve(self::payload(['email' => 'a@example.com']), 42));
    }
}
