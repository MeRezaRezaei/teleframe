<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportPayload;
use MeRezaRezaei\Teleframe\Passport\PassportValueValidator;
use MeRezaRezaei\Teleframe\Passport\PassportVerifiedClaims;
use PHPUnit\Framework\TestCase;

/**
 * PassportValueValidatorClaimGateTest — the host's `allowed_types` policy must
 * gate the §6 CLAIM channel, not only the §5 `secure_data` keys.
 *
 * THE DEFECT THIS FILE GUARDS. `PassportValueValidator::validate()` iterated
 * ONLY `PassportPayload::verifiedSecureDataTypes()`. The §6 claims (`email` /
 * `phone`) reach the payload on a DIFFERENT door — `PassportVerifiedClaims`,
 * handed in by the intake, never a `secure_data` key — and the validator never
 * looked at them. `PassportPayload::verifiedFieldTypes()` merges the two, but
 * nothing in the validation path ever called it.
 *
 * So the allow-list was real for `personal_details` and ABSENT for claims, in
 * both directions:
 *
 *  - FALSE ACCEPT. `validate($payload, [])` — an allow-list permitting
 *    NOTHING — passed for a payload whose only identity was a verified email
 *    claim, on either §6 shape: the MTProto `secureValue.plain_data`
 *    `securePlainEmail`, and the Bot API `email`-typed `PassportData` element.
 *    A host that configured `allowed_types` to exclude `email`, expecting
 *    email-based logins to be refused, got the opposite.
 *  - and the same run refused `personal_details`, which is what made the gap
 *    invisible: the check was demonstrably working one door over.
 *
 * WHY THIS IS THE SHARP END. `PassportAnonymousLoginService::chain()` reads
 * `plainEmail()` / `plainPhone()` and performs its user lookup off exactly that
 * claim, so the ungated channel is the channel an identity is resolved from,
 * not a cosmetic one.
 *
 * Both §6 shapes are covered below rather than only the Bot API one: both
 * arrive as the same `PassportVerifiedClaims` value object and are
 * indistinguishable downstream, so a gate proven on one and not the other is
 * not a proven gate.
 *
 * Zero regex; no keys, no disk, no container.
 */
final class PassportValueValidatorClaimGateTest extends TestCase
{
    // ── 1. MTProto `securePlainEmail` is refused when `email` is excluded ──

    /**
     * §6, MTProto shape: the verified email is
     * `secureValue.plain_data.securePlainEmail`. The host's allow-list excludes
     * `email`. The claim must be REFUSED — this is the bypass: before the fix
     * this exact call returned cleanly, because the §5 loop saw no
     * `secure_data` at all and the claim loop did not exist.
     */
    public function test_mtproto_secure_plain_email_claim_is_refused_when_email_is_excluded(): void
    {
        $payload = $this->mtprotoPayloadWithEmailClaim();

        self::assertSame(
            'ada@example.test',
            $payload->plainEmail(),
            'fixture sanity: this payload really does carry the §6 email claim'
        );

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('verified claim type "email" is not allowed by the host');

        (new PassportValueValidator)->validate($payload, ['personal_details', 'phone']);
    }

    /**
     * The same bypass with the MOST restrictive allow-list the host can
     * configure — `[]`, which permits nothing. Before the fix this passed
     * silently for the claim while being refused for a document type, which is
     * precisely the silent-wrong shape of the defect.
     */
    public function test_a_claim_is_refused_even_when_the_allow_list_is_empty(): void
    {
        $payload = $this->mtprotoPayloadWithEmailClaim();

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('verified claim type "email" is not allowed by the host');

        (new PassportValueValidator)->validate($payload, []);
    }

    // ── 2. Bot API `email`-typed PassportData element is refused the same ──

    /**
     * §6, Bot API shape: an `EncryptedPassportElement` whose `type` is `email`
     * carries the verified address in its own `email` field, beside its
     * encrypted data — never inside `secure_data`. Built through the shared
     * `PassportElementFactory`, so the coordinate is expressed in the one
     * place the rest of the suite expresses it.
     */
    public function test_bot_api_email_element_claim_is_refused_when_email_is_excluded(): void
    {
        $payload = $this->botApiPayloadWithEmailClaim();

        self::assertSame(
            'ada@example.test',
            $payload->plainEmail(),
            'fixture sanity: this payload really does carry the §6 email claim'
        );

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('verified claim type "email" is not allowed by the host');

        (new PassportValueValidator)->validate($payload, ['personal_details']);
    }

    /** The phone claim is gated on the same terms as the email claim. */
    public function test_a_phone_claim_is_refused_when_phone_is_excluded(): void
    {
        $payload = new PassportPayload(
            ['secure_data' => [], 'nonce' => 'nonce-1'],
            PassportElementFactory::claims(['phone_number' => '+15551234567']),
        );

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('verified claim type "phone" is not allowed by the host');

        (new PassportValueValidator)->validate($payload, ['personal_details', 'email']);
    }

    // ── 3. the SAME claims are ACCEPTED when the allow-list includes them ──

    /**
     * NO OVER-BLOCKING. The gate must refuse only what the host excluded: the
     * very same MTProto payload is accepted once `email` is on the list, and
     * once more when the host allows everything the shipped default names.
     */
    public function test_the_same_claim_is_accepted_when_the_allow_list_includes_email(): void
    {
        (new PassportValueValidator)->validate(
            $this->mtprotoPayloadWithEmailClaim(),
            ['personal_details', 'email'],
        );

        // The shipped default in src/Laravel/config/passport.php — a host
        // running the documented defaults must keep working.
        (new PassportValueValidator)->validate(
            $this->botApiPayloadWithEmailClaim(),
            ['personal_details', 'email', 'phone'],
        );

        $this->addToAssertionCount(2); // both calls reached validate() without throwing
    }

    /** Both claims present and both allowed: no refusal for having two. */
    public function test_both_claims_are_accepted_when_both_are_allowed(): void
    {
        $payload = new PassportPayload(
            [
                'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
                'nonce' => 'nonce-2',
            ],
            PassportElementFactory::claims([
                'email' => 'ada@example.test',
                'phone_number' => '+15551234567',
            ]),
        );

        (new PassportValueValidator)->validate(
            $payload,
            ['personal_details', 'email', 'phone'],
        );

        $this->addToAssertionCount(1);
    }

    /**
     * A document-only payload carries NO claim, so the new loop has nothing to
     * iterate and a host's `allowed_types` still governs it exactly as before.
     */
    public function test_a_claim_free_payload_is_unaffected_by_the_claim_gate(): void
    {
        $payload = new PassportPayload([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => 'nonce-3',
        ]);

        self::assertSame([], $payload->claims()->types());

        (new PassportValueValidator)->validate($payload, ['personal_details']);

        $this->addToAssertionCount(1);
    }

    // ── 4. the pre-existing empty / absent allow-list semantics ────────────

    /**
     * THE SEMANTICS, ASSERTED AS THEY ARE, NOT AS I WOULD HAVE CHOSEN THEM.
     * `PassportConfig::allowedTypes()` documents "empty = refuse all, loud at
     * validate()" (`src/Teleframe/Passport/PassportConfig.php:97`), and the §5
     * loop has always implemented that: `in_array($type, [], true)` is false
     * for every type, so every present type throws. An ABSENT config key is a
     * different thing entirely — `allowedTypes()` falls back to the shipped
     * default `['personal_details', 'email', 'phone']` when the key is not an
     * array.
     *
     * So the claim loop must mirror ALLOW NONE for an empty list, and must NOT
     * invent a "no claims configured ⇒ claims fine" escape. Both halves are
     * asserted here, at the validator boundary AND at the config boundary,
     * because a gate that only holds for a hand-built `[]` and breaks for a
     * real host's `allowed_types` has not gated anything.
     */
    public function test_the_empty_allow_list_means_allow_none_and_still_does(): void
    {
        $validator = new PassportValueValidator;

        // (a) a document type — the pre-existing refusal, unchanged.
        $documentOnly = new PassportPayload([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => 'nonce-4',
        ]);

        try {
            $validator->validate($documentOnly, []);
            self::fail('an empty allow-list must refuse a present document type');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString(
                '"personal_details" is not allowed by the host',
                $e->getMessage(),
                'the §5 refusal message is unchanged by the claim gate'
            );
        }

        // (b) a claim — the SAME list, refused the same way.
        $claimOnly = $this->mtprotoPayloadWithEmailClaim();

        try {
            $validator->validate($claimOnly, []);
            self::fail('an empty allow-list must refuse a present claim type too');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString(
                'verified claim type "email" is not allowed by the host',
                $e->getMessage(),
            );
        }

        // (c) the NON-ARRAY fallback is a DIFFERENT semantic and is preserved:
        //     PassportConfig substitutes the shipped default list. (The
        //     constructor array_merges the defaults, so the key is present —
        //     it is the `! is_array($allowed)` branch at PassportConfig.php:105
        //     that reaches the fallback, and a host writes a string there.)
        $config = new PassportConfig(['allowed_types' => 'personal_details']);
        self::assertSame(
            ['personal_details', 'email', 'phone'],
            $config->allowedTypes(),
            'a non-array allowed_types falls back to the shipped default, which permits claims'
        );

        // ...and that default really does let the same claim through.
        $validator->validate($claimOnly, $config->allowedTypes());

        // (d) an EXPLICIT empty array is NOT the fallback — it stays allow-none.
        $configEmpty = new PassportConfig(['allowed_types' => []]);
        self::assertSame([], $configEmpty->allowedTypes(), 'an explicit empty array is allow-none, not the default');

        $this->addToAssertionCount(1);
    }

    // ── 5. the gate is STRICT, like the §5 refusal beside it ───────────────

    /**
     * `in_array(..., true)` — no loose match. A type that only *looks* like an
     * allowed one must not slip through on type juggling.
     */
    public function test_the_claim_gate_compares_strictly(): void
    {
        $payload = $this->mtprotoPayloadWithEmailClaim();

        // ' email' is not 'email': a padded/odd variant is not the allowed type.
        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('verified claim type "email" is not allowed by the host');

        (new PassportValueValidator)->validate($payload, ['personal_details', ' email']);
    }

    /**
     * The refusal names the TYPE, never the value — the values are personal
     * data and must not reach a log, a response body or a referrer.
     */
    public function test_the_claim_refusal_names_the_type_and_never_the_value(): void
    {
        try {
            (new PassportValueValidator)->validate($this->mtprotoPayloadWithEmailClaim(), []);
            self::fail('expected the claim to be refused');
        } catch (PassportLoginException $e) {
            self::assertStringNotContainsString('ada@example.test', $e->getMessage());
            self::assertStringContainsString('"email"', $e->getMessage());
        }
    }

    /**
     * The §5 shape check still fires FIRST and still outranks the host's list,
     * so adding the claim loop did not reorder the two escalating steps.
     */
    public function test_the_shape_check_still_outranks_the_allow_list(): void
    {
        $payload = new PassportPayload([
            'secure_data' => ['email' => ['address' => 'ada@example.test']],
            'nonce' => 'nonce-5',
        ]);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('not a SecureData type Telegram sends');

        // The host's list explicitly permits everything relevant — the §5 shape
        // refusal must still be the one that fires.
        (new PassportValueValidator)->validate($payload, ['personal_details', 'email', 'phone']);
    }

    // ── fixtures ───────────────────────────────────────────────────────────

    /**
     * §6, MTProto: `secureValue.plain_data` as `securePlainEmail`.
     *
     * `secure_data` is EMPTY on purpose. The claim is the payload's only type,
     * so the refusal this fixture produces can only come from the claim gate —
     * a `personal_details` entry alongside it would let the §5 loop refuse
     * first and the test would pass without the gate having done anything.
     */
    private function mtprotoPayloadWithEmailClaim(): PassportPayload
    {
        $claims = PassportVerifiedClaims::fromSecureValues([
            [
                '_' => 'secureValue',
                'type' => ['_' => 'secureValueTypeEmail'],
                'plain_data' => ['_' => 'securePlainEmail', 'email' => 'ada@example.test'],
                'hash' => 'email-hash',
            ],
        ]);

        return new PassportPayload(['secure_data' => [], 'nonce' => 'nonce-6'], $claims);
    }

    /**
     * §6, Bot API: an `email`-typed `EncryptedPassportElement` in
     * `PassportData.data[]`. Claim-only, for the reason above.
     */
    private function botApiPayloadWithEmailClaim(): PassportPayload
    {
        return new PassportPayload(
            ['secure_data' => [], 'nonce' => 'nonce-7'],
            PassportElementFactory::claims(['email' => 'ada@example.test']),
        );
    }
}
