<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use PHPUnit\Framework\TestCase;

/**
 * §8 conformance for the ONE thing this package sends to Telegram: the
 * `scope` of `requestPassport`.
 *
 * The shipped implementation emitted a flat list of type names —
 * `["personal_details","email","phone"]` — instead of the §8 object
 * `{"v": 1, "data": [...]}` with `v` always 1. That much was fixed and stays
 * fixed.
 *
 * WHAT CHANGED ON 2026-09-26, in the opposite direction. The same revision
 * made `requestScope()` DROP `email` and `phone`, on the reading that "there
 * is no element for an email or a phone, because Telegram shares those from
 * the user's own Passport settings (§6)". §6 describes the RESPONSE shape,
 * not the request: the Bot API element-type vocabulary Telegram accepts in a
 * scope is `EncryptedPassportElement.type`, whose list ends with
 * `"phone_number", "email"` (vendored in this repo at
 * `src/Schema/schema/sources/botapi-spec.json`). So the drop meant a host
 * that asked for an email was asking for nothing, no `email` element ever
 * arrived, and the §6 channel this package reads was dead by configuration.
 * Both element types are requested again — with the one NAME TRANSLATION the
 * vocabulary requires: the element type for a phone is `phone_number`, so the
 * host-facing `phone` spelling maps onto it.
 *
 * The flat list is deliberately KEPT as the `scope` accessor: it is what a
 * host configured and what the dashboard renders. `requestScope()` is the wire
 * shape, and it is the one that goes into a `requestPassport` call.
 */
final class PassportScopeConformanceTest extends TestCase
{
    public function test_the_default_configuration_yields_the_spec_object(): void
    {
        $scope = (new PassportConfig)->requestScope();

        self::assertSame(['v' => 1, 'data' => ['personal_details', 'email', 'phone_number']], $scope);
        self::assertSame(1, $scope['v'], 'v must be 1');
    }

    public function test_the_identity_element_types_are_requested_under_their_spec_names(): void
    {
        $scope = (new PassportConfig(['scope' => ['email', 'phone', 'personal_details']]))->requestScope();

        // `email` is spelled the same in the request and the response; the
        // phone is `phone_number` in the SCOPE vocabulary, which is why the
        // host-facing `phone` is translated rather than forwarded verbatim.
        self::assertSame(['v' => 1, 'data' => ['email', 'phone_number', 'personal_details']], $scope);
        self::assertContains('email', $scope['data']);
        self::assertContains('phone_number', $scope['data']);
    }

    public function test_every_documented_document_type_survives(): void
    {
        $types = [
            'personal_details', 'passport', 'internal_passport', 'driver_license',
            'identity_card', 'address', 'utility_bill', 'bank_statement',
            'rental_agreement', 'passport_registration', 'temporary_registration',
        ];

        $scope = (new PassportConfig(['scope' => $types]))->requestScope();

        self::assertSame(['v' => 1, 'data' => $types], $scope);
    }

    public function test_the_aliases_expand_to_their_documented_members(): void
    {
        // §8: id_document ≡ one of passport|driver_license|identity_card|
        // internal_passport; address_document ≡ one of
        // utility_bill|bank_statement|rental_agreement.
        $scope = (new PassportConfig(['scope' => ['id_document', 'address_document']]))->requestScope();

        self::assertSame([
            'passport', 'driver_license', 'identity_card', 'internal_passport',
            'utility_bill', 'bank_statement', 'rental_agreement',
        ], $scope['data']);
    }

    public function test_each_type_appears_at_most_once(): void
    {
        $scope = (new PassportConfig(['scope' => [
            'passport', 'id_document', 'personal_details', 'personal_details',
        ]]))->requestScope();

        self::assertSame($scope['data'], array_values(array_unique($scope['data'])));
    }

    public function test_an_unknown_entry_is_dropped_rather_than_forwarded(): void
    {
        // One element Telegram does not define fails the WHOLE
        // requestPassport, so a typo in a host's config must not cost the
        // login.
        $scope = (new PassportConfig(['scope' => ['personal_details', 'passprt']]))->requestScope();

        self::assertSame(['v' => 1, 'data' => ['personal_details']], $scope);
    }

    public function test_the_flat_accessor_is_unchanged_for_display(): void
    {
        $config = new PassportConfig(['scope' => ['personal_details', 'email', 'phone']]);

        self::assertSame(['personal_details', 'email', 'phone'], $config->scope());
    }
}
