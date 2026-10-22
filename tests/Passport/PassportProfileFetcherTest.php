<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\MemoryPassportWire;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportProfile;
use MeRezaRezaei\Teleframe\Passport\PassportProfileFetcher;
use MeRezaRezaei\Teleframe\Passport\PassportWireContract;
use PHPUnit\Framework\TestCase;

/**
 * PassportProfileFetcher — `help.getPassportConfig#c661ad08` (OFFICIAL
 * layer-227 id, gap analysis §3.1). The call a Laravel host makes so it does
 * not hardcode the countries Telegram serves.
 *
 * Two behaviours worth pinning: the `DataJSON` countries_langs payload is
 * decoded (a server change must reach the host, not a frozen list), and
 * `passportConfigNotModified` is an ANSWER rather than a failure — a host that
 * polls with a cached hash must not have to treat "nothing changed" as an
 * error.
 */
final class PassportProfileFetcherTest extends TestCase
{
    public function test_it_dispatches_with_the_official_method_and_a_zero_hash_by_default(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_PASSPORT_CONFIG => [
                ['_' => 'help.passportConfig', 'hash' => 4242, 'countries_langs' => '[["US","en"],["DE","de"]]'],
            ],
        ]);

        $profile = (new PassportProfileFetcher($wire))->fetch();

        self::assertSame(4242, $profile->hash);
        self::assertFalse($profile->notModified);
        self::assertSame(['US', 'DE'], array_column($profile->countries, 'code'));
        self::assertTrue($profile->servesCountry('US'));
        self::assertFalse($profile->servesCountry('AQ'));

        $dispatch = $wire->firstDispatchOf('help.getPassportConfig');
        self::assertNotNull($dispatch);
        self::assertSame('c661ad08', PassportWireContract::OFFICIAL_IDS['help.getPassportConfig']);
        self::assertSame(['hash' => 0], $dispatch['params']);
    }

    public function test_a_known_hash_is_passed_through_and_not_modified_is_an_answer(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_PASSPORT_CONFIG => [
                ['_' => 'help.passportConfigNotModified', 'hash' => 99],
            ],
        ]);

        $profile = (new PassportProfileFetcher($wire))->fetch(99);

        self::assertTrue($profile->notModified);
        self::assertSame(99, $profile->hash);
        self::assertSame([], $profile->countries);
        self::assertSame(['hash' => 99], $wire->firstDispatchOf('help.getPassportConfig')['params']);
    }

    public function test_extra_offered_languages_are_parsed(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_PASSPORT_CONFIG => [
                [
                    '_' => 'help.passportConfig',
                    'hash' => 1,
                    'countries_langs' => '[["CH","de",["fr","it"]],["SG","en"]]',
                ],
            ],
        ]);

        $profile = (new PassportProfileFetcher($wire))->fetch();

        self::assertSame(['fr', 'it'], $profile->country('CH')['languages']);
        self::assertSame(['de', 'fr', 'it', 'en'], $profile->languages);
    }

    public function test_an_undecodable_payload_is_refused_not_an_empty_country_list(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_PASSPORT_CONFIG => [
                ['_' => 'help.passportConfig', 'hash' => 1, 'countries_langs' => 'not-json'],
            ],
        ]);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessageMatches('/undecodable/');
        (new PassportProfileFetcher($wire))->fetch();
    }

    public function test_an_unnamed_response_is_refused_loud(): void
    {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_PASSPORT_CONFIG => [['hash' => 1]],
        ]);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessageMatches('/unnamed response/');
        (new PassportProfileFetcher($wire))->fetch();
    }

    public function test_the_profile_renders_credential_free(): void
    {
        $profile = new PassportProfile(
            hash: 7,
            countries: [['code' => 'US', 'default_language' => 'en', 'languages' => []]],
            languages: ['en'],
        );

        self::assertSame(
            ['hash' => 7, 'not_modified' => false, 'countries' => ['US'], 'languages' => ['en']],
            $profile->toArray()
        );
    }
}
