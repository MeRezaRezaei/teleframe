<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\PassportVerifiedClaims;
use MeRezaRezaei\Teleframe\Passport\SecureValueTraversal;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecureDataData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecureFileData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecurePlainEmailData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecurePlainPhoneData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecureValueData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecureValueTypeAddressData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecureValueTypeEmailData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecureValueTypePassportData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecureValueTypePersonalDetailsData;
use MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\SecureValueTypePhoneData;
use PHPUnit\Framework\TestCase;

/**
 * SecureValueTraversalTest — the MTProto §6/§7 walk, over the SAME two
 * constructors Telegram really sends.
 *
 * THE POLARITY RULE, inherited from `SpecCredentialFactory` and
 * `PassportElementFactory`: the fixture is built FROM THE SPECIFICATION, and
 * where the spec and the implementation disagree the FIXTURE IS RIGHT. That
 * is why these payloads are built from the GENERATED schema types
 * (`SecureValueData`, `SecurePlainEmailData`, `SecurePlainPhoneData`,
 * `SecureDataData`, `SecureFileData`, `SecureValueType*Data`) and from the raw
 * `secureValue#187fa0ca` wire arrays — the two spellings the traversal claims
 * to read. An INVENTED fixture shape is the exact false green this repository
 * shipped twice (commit f174e833): a suite built to match the code agrees with
 * it while both are unreachable from real Telegram. The generated artifacts are
 * read here, never edited.
 *
 * §6: the verified values are `secureValue.plain_data` as `securePlainEmail` /
 * `securePlainPhone`. §7: `SecureValue` carries `type`, `data`, `front_side`,
 * `reverse_side`, `selfie`, `translation`, `files`, `plain_data`, `hash` — and
 * only `secureValueTypeEmail` / `secureValueTypePhone` may carry a `plain_data`
 * at all.
 *
 * NEGATIVES are kept as first-class tests, because on this surface the
 * dangerous direction is reading a claim from a coordinate Telegram does not
 * use: an identity inside a nested `SecureData`, an email field on a document
 * type, a `plain_data` on a type §7 does not allow it on.
 */
final class SecureValueTraversalTest extends TestCase
{
    // ── the positive: §6 through the generated DTOs ───────────────────

    public function test_the_generated_secure_value_types_yield_the_verified_claims(): void
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
                hash: 'value-hash',
            ),
            new SecureValueData(
                flags: 0,
                type: new SecureValueTypeEmailData,
                data: null,
                frontSide: null,
                reverseSide: null,
                selfie: null,
                translation: null,
                files: null,
                plainData: new SecurePlainEmailData('ada@example.test'),
                hash: 'email-hash',
            ),
            new SecureValueData(
                flags: 0,
                type: new SecureValueTypePhoneData,
                data: null,
                frontSide: null,
                reverseSide: null,
                selfie: null,
                translation: null,
                files: null,
                plainData: new SecurePlainPhoneData('+15551234567'),
                hash: 'phone-hash',
            ),
        ];

        $traversal = SecureValueTraversal::fromValues($values);

        self::assertFalse($traversal->isEmpty());
        self::assertSame(3, $traversal->valueCount());
        self::assertSame('ada@example.test', $traversal->claims()->email());
        self::assertSame('+15551234567', $traversal->claims()->phone());
        self::assertSame(['email', 'phone'], $traversal->claims()->types());
    }

    /**
     * The SAME spec, in the raw wire spelling: `secureValue#187fa0ca`'s own
     * field names (`plain_data`, `front_side`, …) with the constructor in `_`.
     * Both spellings are read by one rule, so a test that passed only against
     * DTOs would leave the wire path untested — and the wire path is what a
     * host actually receives.
     */
    public function test_the_wire_spelling_of_the_same_constructors_yields_the_same_claims(): void
    {
        $values = [
            [
                '_' => 'secureValue',
                'type' => ['_' => 'secureValueTypeEmail'],
                'plain_data' => ['_' => 'securePlainEmail', 'email' => 'ada@example.test'],
                'hash' => 'email-hash',
            ],
            [
                '_' => 'secureValue',
                'type' => ['_' => 'secureValueTypePhone'],
                'plain_data' => ['_' => 'securePlainPhone', 'phone' => '+15551234567'],
                'hash' => 'phone-hash',
            ],
        ];

        $claims = PassportVerifiedClaims::fromSecureValues($values);

        self::assertSame('ada@example.test', $claims->email());
        self::assertSame('+15551234567', $claims->phone());
    }

    // ── the walk is COMPLETE over §7, not just the claim leaf ──────────

    /**
     * The whole §7 field list is traversed, and the walk REPORTS it. This is
     * the test the previous state of the package could not have passed: the
     * absence of a `SecureValue` traversal was a claim in a docblock, with
     * nothing to observe. `branchesVisited()` / `secureDataCount()` /
     * `fileCount()` make the traversal's reach a fact rather than a promise.
     */
    public function test_every_secure_value_branch_of_the_spec_is_traversed(): void
    {
        $file = static fn (int $id): SecureFileData => new SecureFileData(
            id: $id,
            accessHash: 111,
            size: 2048,
            dcId: 2,
            date: 1_700_000_000,
            fileHash: 'file-hash',
            secret: 'file-secret',
        );

        $traversal = SecureValueTraversal::fromValues([
            new SecureValueData(
                flags: 0,
                type: new SecureValueTypePassportData,
                data: new SecureDataData(data: 'cipher', dataHash: 'hash', secret: 'secret'),
                frontSide: $file(1),
                reverseSide: null,
                selfie: $file(2),
                translation: [$file(3), $file(4)],
                files: [$file(5)],
                plainData: null,
                hash: 'passport-hash',
            ),
        ]);

        self::assertSame(
            ['data', 'front_side', 'selfie', 'translation', 'files'],
            $traversal->branchesVisited(),
        );
        self::assertSame(1, $traversal->secureDataCount());
        self::assertSame(5, $traversal->fileCount(), 'three singles + two translations + one file');
        self::assertSame(['secureValueTypePassport'], $traversal->typeNames());
        self::assertNull($traversal->claims()->email());
        self::assertNull($traversal->claims()->phone());
    }

    /** A `reverse_side` that is present is visited; an absent one is not. */
    public function test_an_absent_branch_is_simply_not_visited(): void
    {
        $traversal = SecureValueTraversal::fromValues([[
            '_' => 'secureValue',
            'type' => ['_' => 'secureValueTypeAddress'],
            'data' => ['_' => 'secureData', 'data' => 'cipher', 'data_hash' => 'h', 'secret' => 's'],
            'hash' => 'address-hash',
        ]]);

        self::assertSame(['data'], $traversal->branchesVisited());
        self::assertSame(1, $traversal->valueCount());
        self::assertSame(['secureValueTypeAddress'], $traversal->typeNames());
    }

    /** The nested `SecureData` is REACHED and is NOT an identity channel. */
    public function test_a_nested_secure_data_is_reached_and_never_read_for_identity(): void
    {
        $traversal = SecureValueTraversal::fromValues([
            new SecureValueData(
                flags: 0,
                type: new SecureValueTypePersonalDetailsData,
                data: new SecureDataData(
                    // A SecureData.data is an ENCRYPTED AES payload (§4) — a
                    // `Credentials`-shaped JSON string, NOT a claim channel.
                    data: '{"secure_data":{"personal_details":{"first_name":"Ada"}},"nonce":"n"}',
                    dataHash: 'hash',
                    secret: 'secret',
                ),
                frontSide: null,
                reverseSide: null,
                selfie: null,
                translation: null,
                files: null,
                plainData: null,
                hash: 'personal-details-hash',
            ),
        ]);

        self::assertSame(1, $traversal->secureDataCount());
        self::assertNull($traversal->claims()->email());
        self::assertNull($traversal->claims()->phone());
    }

    // ── the negatives: coordinates Telegram does not use ───────────────

    /**
     * §7: only `secureValueTypeEmail` and `secureValueTypePhone` may carry a
     * `plain_data`. One on a document type is a protocol violation, so it yields
     * nothing — otherwise anyone who could influence a payload could put a
     * claim where Telegram does not put one.
     */
    public function test_a_plain_data_on_a_type_that_may_not_carry_one_is_refused(): void
    {
        $claims = PassportVerifiedClaims::fromSecureValues([
            new SecureValueData(
                flags: 0,
                type: new SecureValueTypePassportData,
                data: null,
                frontSide: null,
                reverseSide: null,
                selfie: null,
                translation: null,
                files: null,
                plainData: new SecurePlainEmailData('attacker@example.test'),
                hash: 'passport-hash',
            ),
        ]);

        self::assertNull($claims->email());
        self::assertNull($claims->phone());
    }

    /**
     * An `email` on the SecureValue itself — beside `plain_data`, never inside
     * it — is the MTProto image of the removed `secure_data.email.address`
     * read. It is not §6, so it is not read.
     */
    public function test_an_email_beside_the_plain_data_is_not_read(): void
    {
        $claims = PassportVerifiedClaims::fromSecureValues([[
            '_' => 'secureValue',
            'type' => ['_' => 'secureValueTypeEmail'],
            'email' => 'attacker@example.test',
            'phone' => '+15550000001',
            'hash' => 'email-hash',
        ]]);

        self::assertNull($claims->email());
        self::assertNull($claims->phone());
    }

    /** A leaf that is not a `SecurePlainData` at all yields nothing. */
    public function test_a_plain_data_that_is_not_a_secure_plain_leaf_yields_nothing(): void
    {
        $claims = PassportVerifiedClaims::fromSecureValues([[
            '_' => 'secureValue',
            'type' => ['_' => 'secureValueTypeEmail'],
            'plain_data' => ['_' => 'secureData', 'data' => 'cipher'],
            'hash' => 'email-hash',
        ]]);

        self::assertNull($claims->email());
        self::assertNull($claims->phone());
    }

    /**
     * Telegram sends at most one `SecureValue` of each claim type, so two that
     * DISAGREE are a protocol violation. The claim is DROPPED rather than
     * resolved to a winner: picking one would let whoever can append a second
     * value choose which identity a login resolves — the same rule, and the
     * same reason, as the Bot API element list.
     */
    public function test_two_plain_leaves_that_disagree_yield_no_claim(): void
    {
        $claims = PassportVerifiedClaims::fromSecureValues([
            new SecureValueData(
                flags: 0,
                type: new SecureValueTypeEmailData,
                data: null,
                frontSide: null,
                reverseSide: null,
                selfie: null,
                translation: null,
                files: null,
                plainData: new SecurePlainEmailData('attacker@example.test'),
                hash: 'h1',
            ),
            new SecureValueData(
                flags: 0,
                type: new SecureValueTypeEmailData,
                data: null,
                frontSide: null,
                reverseSide: null,
                selfie: null,
                translation: null,
                files: null,
                plainData: new SecurePlainEmailData('victim@example.test'),
                hash: 'h2',
            ),
        ]);

        self::assertNull($claims->email(), 'a self-contradicting payload names no identity');
    }

    /** Duplicated but IDENTICAL leaves agree, so the claim survives. */
    public function test_two_identical_plain_leaves_agree(): void
    {
        $claims = PassportVerifiedClaims::fromSecureValues([
            new SecureValueData(
                flags: 0,
                type: new SecureValueTypePhoneData,
                data: null,
                frontSide: null,
                reverseSide: null,
                selfie: null,
                translation: null,
                files: null,
                plainData: new SecurePlainPhoneData('+15551234567'),
                hash: 'h1',
            ),
            new SecureValueData(
                flags: 0,
                type: new SecureValueTypePhoneData,
                data: null,
                frontSide: null,
                reverseSide: null,
                selfie: null,
                translation: null,
                files: null,
                plainData: new SecurePlainPhoneData('+15551234567'),
                hash: 'h2',
            ),
        ]);

        self::assertSame('+15551234567', $claims->phone());
    }

    // ── the type gate FAILS CLOSED (F2) ─────────────────────────────────

    /**
     * The §7 gate must refuse a type this class cannot NAME, not read it as
     * permission. The first revision read `$typeName === null` as
     * allow-everything, and that is the fail-open shape: an unrecognised type
     * is precisely what a forged payload would use, because it is the one
     * thing a forger controls.
     *
     * The Bot API side has no equivalent hole and that asymmetry is the
     * reason to test it here: there `$element['type']` IS a string, so
     * "unreadable" and "not a claim type" are the same test. In this walk they
     * are two different tests, and only the second is a refusal.
     *
     * Every one of these nodes carries a `plain_data` that would be a perfectly
     * good `securePlainEmail` if the type gate were the only thing standing
     * in the way.
     */
    public function test_a_type_the_walk_cannot_name_yields_no_claim(): void
    {
        $leaf = ['_' => 'securePlainEmail', 'email' => 'attacker@example.test'];

        $unnamedType = [[
            '_' => 'secureValue',
            'type' => ['flags' => 0], // a SecureValueType with NO `_` at all
            'plain_data' => $leaf,
            'hash' => 'h',
        ]];

        $bareStringType = [[
            '_' => 'secureValue',
            'type' => 'secureValueTypeEmail', // a name, but not a Peer box
            'plain_data' => $leaf,
            'hash' => 'h',
        ]];

        $emailTypeWithoutName = [[
            '_' => 'secureValue',
            'type' => ['_' => '', 'email' => 'secureValueTypeEmail'],
            'plain_data' => $leaf,
            'hash' => 'h',
        ]];

        foreach ([$unnamedType, $bareStringType, $emailTypeWithoutName] as $index => $values) {
            $claims = PassportVerifiedClaims::fromSecureValues($values);

            self::assertNull($claims->email(), 'an unnamed type must not carry a claim (case '.$index.')');
            self::assertNull($claims->phone(), 'an unnamed type must not carry a claim (case '.$index.')');
        }
    }

    /**
     * The refusal is on the CLAIM, not on the walk: a value whose type cannot
     * be named is still a value, and the branch is still recorded as visited —
     * so a test can tell "refused the claim" from "never reached the branch".
     */
    public function test_an_unnamed_type_is_still_walked_and_still_yields_no_claim(): void
    {
        $traversal = SecureValueTraversal::fromValues([[
            '_' => 'secureValue',
            'type' => ['flags' => 0],
            'plain_data' => ['_' => 'securePlainEmail', 'email' => 'attacker@example.test'],
            'hash' => 'h',
        ]]);

        self::assertSame(1, $traversal->valueCount());
        self::assertContains('plain_data', $traversal->branchesVisited());
        self::assertSame([], $traversal->claims()->types());
    }

    // ── the constructor-less leaf fallback is SYMMETRIC (F3) ────────────

    /**
     * A `plain_data` that arrived with no constructor falls back to its field
     * name, and the fallback must be symmetric: the first revision returned
     * early on the email branch, so a PHONE-only leaf without a constructor was
     * silently dropped while the email one was read. A shape-dependent claim
     * is the opposite of a rule.
     */
    public function test_a_constructor_less_leaf_is_read_for_email_and_for_phone(): void
    {
        $claims = PassportVerifiedClaims::fromSecureValues([
            ['_' => 'secureValue', 'type' => ['_' => 'secureValueTypeEmail'], 'plain_data' => ['email' => 'ada@example.test'], 'hash' => 'h1'],
            ['_' => 'secureValue', 'type' => ['_' => 'secureValueTypePhone'], 'plain_data' => ['phone' => '+15551234567'], 'hash' => 'h2'],
        ]);

        self::assertSame('ada@example.test', $claims->email(), 'the email fallback reads');
        self::assertSame('+15551234567', $claims->phone(), 'the phone fallback reads too — symmetrically');
    }

    /**
     * A constructor-less leaf that carries BOTH §6 fields reads as the two §6
     * leaves, into two lists with two agreement rules — it never merges them
     * into one value, which is what the two-element Bot API list would do.
     */
    public function test_a_constructor_less_leaf_carrying_both_fields_reads_as_the_two_leaves(): void
    {
        $claims = PassportVerifiedClaims::fromSecureValues([
            ['_' => 'secureValue', 'type' => ['_' => 'secureValueTypeEmail'], 'plain_data' => ['email' => 'ada@example.test', 'phone' => '+15551234567'], 'hash' => 'h'],
        ]);

        self::assertSame('ada@example.test', $claims->email());
        self::assertSame('+15551234567', $claims->phone());
    }

    // ── the agreement comparison is NORMALISED (F4) ─────────────────────

    /**
     * THE FALSE-NEGATIVE SURFACE, probed directly. A domain is
     * case-insensitive by DNS and a trailing dot is the same name written
     * rooted, so two leaves that differ ONLY in those ways name ONE mailbox
     * and must agree. Under the byte-exact comparison they disagreed, which
     * meant a real response could be refused and — worse — that anyone able
     * to append a second `SecureValue` could DENY a login just by respelling
     * the domain.
     */
    public function test_leaves_differing_only_in_domain_case_or_a_trailing_dot_agree(): void
    {
        $domainCase = PassportVerifiedClaims::fromSecureValues([
            $this->emailValue('ada@Example.TEST', 'h1'),
            $this->emailValue('ada@example.test', 'h2'),
        ]);

        self::assertSame('ada@Example.TEST', $domainCase->email(), 'the first RAW value is returned, byte for byte');

        $rootedDot = PassportVerifiedClaims::fromSecureValues([
            $this->emailValue('ada@example.test.', 'h1'),
            $this->emailValue('ada@example.test', 'h2'),
        ]);

        self::assertSame('ada@example.test.', $rootedDot->email());

        // And the case-fold does not run one way: the SECOND value's spelling
        // is the odd one out just as often.
        $reversed = PassportVerifiedClaims::fromSecureValues([
            $this->emailValue('ada@example.test', 'h1'),
            $this->emailValue('ada@EXAMPLE.test', 'h2'),
        ]);

        self::assertSame('ada@example.test', $reversed->email());
    }

    /**
     * The normalisation must NOT let a genuinely different address through.
     * Three separations survive it, and each is a refusal:
     *
     *  - a different domain entirely;
     *  - a different local part;
     *  - a LOCAL PART that differs only in case, because the local part is
     *    case-SENSITIVE (RFC 5321 §2.4) and `Ada@` / `ada@` are two strings
     *    the standard does not license calling one mailbox. Folding the local
     *    part is the loosening this decision refuses to make.
     */
    public function test_the_normalisation_does_not_merge_two_genuinely_different_addresses(): void
    {
        $differentDomain = PassportVerifiedClaims::fromSecureValues([
            $this->emailValue('ada@example.test', 'h1'),
            $this->emailValue('ada@attacker.example.test', 'h2'),
        ]);
        self::assertNull($differentDomain->email(), 'a different domain is a different mailbox');

        $differentLocal = PassportVerifiedClaims::fromSecureValues([
            $this->emailValue('ada@example.test', 'h1'),
            $this->emailValue('mallory@example.test', 'h2'),
        ]);
        self::assertNull($differentLocal->email(), 'a different local part is a different mailbox');

        $localPartCase = PassportVerifiedClaims::fromSecureValues([
            $this->emailValue('Ada@example.test', 'h1'),
            $this->emailValue('ada@example.test', 'h2'),
        ]);
        self::assertNull($localPartCase->email(), 'the local part is case-sensitive and stays byte-exact');

        // A near-miss in the folded part of the domain is still a refusal: the
        // trailing dot is stripped ONCE, so `example.test..` is not `example.test`.
        $doubleDot = PassportVerifiedClaims::fromSecureValues([
            $this->emailValue('ada@example.test..', 'h1'),
            $this->emailValue('ada@example.test', 'h2'),
        ]);
        self::assertNull($doubleDot->email(), 'only ONE trailing dot is the root form');
    }

    /**
     * The appended-leaf attack the comparison exists to block, re-pinned
     * against the NORMALISED comparison rather than the old byte-exact one:
     * a second leaf that normalises the same way cannot change the value (the
     * first wins, unchanged), and one that does not still kills the claim.
     */
    public function test_an_appended_leaf_can_neither_choose_nor_deny_the_identity(): void
    {
        $cannotChoose = PassportVerifiedClaims::fromSecureValues([
            $this->emailValue('victim@example.test', 'h1'),
            $this->emailValue('victim@Example.Test.', 'h2'),
        ]);
        self::assertSame('victim@example.test', $cannotChoose->email(), 'the appended leaf does not get to choose');

        $cannotDeny = PassportVerifiedClaims::fromSecureValues([
            $this->emailValue('victim@example.test', 'h1'),
            $this->emailValue('attacker@example.test', 'h2'),
        ]);
        self::assertNull($cannotDeny->email(), 'a genuinely different second leaf is still a refusal');
    }

    /** Phones are not normalised at all — a different spelling is a refusal. */
    public function test_two_phones_that_differ_at_all_yield_no_claim(): void
    {
        $claims = PassportVerifiedClaims::fromSecureValues([
            ['_' => 'secureValue', 'type' => ['_' => 'secureValueTypePhone'], 'plain_data' => ['_' => 'securePlainPhone', 'phone' => '+15551234567'], 'hash' => 'h1'],
            ['_' => 'secureValue', 'type' => ['_' => 'secureValueTypePhone'], 'plain_data' => ['_' => 'securePlainPhone', 'phone' => '+1 555 123 4567'], 'hash' => 'h2'],
        ]);

        self::assertNull($claims->phone());
    }

    /**
     * A value with no `@` has no domain to fold, so it is compared byte-exact
     * — a malformed address cannot normalise itself into agreement.
     */
    public function test_a_value_with_no_domain_is_compared_byte_exact(): void
    {
        $claims = PassportVerifiedClaims::fromSecureValues([
            $this->emailValue('not-an-address', 'h1'),
            $this->emailValue('NOT-AN-ADDRESS', 'h2'),
        ]);

        self::assertNull($claims->email());
    }

    /**
     * @return array<string, mixed>
     */
    private function emailValue(string $email, string $hash): array
    {
        return [
            '_' => 'secureValue',
            'type' => ['_' => 'secureValueTypeEmail'],
            'plain_data' => ['_' => 'securePlainEmail', 'email' => $email],
            'hash' => $hash,
        ];
    }

    // ── the refusals: a missing leaf, and an empty traversal ───────────

    /** A value with no `plain_data` leaf is document-only: no claim. */
    public function test_a_value_with_no_plain_leaf_yields_no_claim(): void
    {
        $traversal = SecureValueTraversal::fromValues([
            new SecureValueData(
                flags: 0,
                type: new SecureValueTypeAddressData,
                data: new SecureDataData(data: 'cipher', dataHash: 'h', secret: 's'),
                frontSide: null,
                reverseSide: null,
                selfie: null,
                translation: null,
                files: null,
                plainData: null,
                hash: 'address-hash',
            ),
        ]);

        self::assertFalse($traversal->isEmpty(), 'the walk DID reach a value');
        self::assertSame(PassportVerifiedClaims::none()->types(), $traversal->claims()->types());
        self::assertNull($traversal->claims()->email());
        self::assertNull($traversal->claims()->phone());
    }

    /** A blank, non-string or absent leaf is absent, never a claim. */
    public function test_a_blank_or_non_string_leaf_is_treated_as_absent(): void
    {
        $claims = PassportVerifiedClaims::fromSecureValues([
            ['_' => 'secureValue', 'type' => ['_' => 'secureValueTypeEmail'], 'plain_data' => ['_' => 'securePlainEmail', 'email' => '   ']],
            ['_' => 'secureValue', 'type' => ['_' => 'secureValueTypePhone'], 'plain_data' => ['_' => 'securePlainPhone', 'phone' => 42]],
        ]);

        self::assertNull($claims->email());
        self::assertNull($claims->phone());
    }

    /** Every empty traversal is empty, and every one of them yields no claim. */
    public function test_every_empty_traversal_yields_no_claim(): void
    {
        foreach ([null, [], '', 0, false, new \stdClass, ['nonsense'], [null, 'x', 7]] as $values) {
            $traversal = SecureValueTraversal::fromValues($values);

            self::assertTrue(
                $traversal->isEmpty(),
                'an input with no SecureValue is an empty traversal: '.(is_scalar($values) ? var_export($values, true) : gettype($values))
            );
            self::assertNull($traversal->claims()->email());
            self::assertNull($traversal->claims()->phone());
        }
    }
}
