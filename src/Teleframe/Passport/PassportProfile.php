<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PassportProfile (Cycle 18 W3) — the value object behind
 * `help.passportConfig#a098d6af hash:int countries_langs:DataJSON`, the
 * response of `help.getPassportConfig#c661ad08`.
 *
 * Two useful facts for a Laravel host rendering a passport form: which
 * countries Telegram serves, and which languages each one defaults to — so the
 * host does not hardcode a country list that Telegram can change server-side.
 * `countries_langs` is a DataJSON (a JSON *string*), parsed once here.
 *
 * `help.passportConfigNotModified#bfb9f457` — the answer when the caller passes
 * a `hash` it already has — carries no data by definition, so it becomes a
 * profile with `notModified() === true` and empty lists, rather than an
 * exception: "nothing changed" is a real answer, not a failure. A caller that
 * needs the data must fetch with no hash.
 *
 * Zero-regex: `json_decode` + `is_array`/`is_string` only.
 */
final class PassportProfile
{
    /**
     * @param  int  $hash  the server's config hash (pass it back to get NotModified)
     * @param  list<array{code: string, default_language: string, languages: list<string>}>  $countries
     * @param  list<string>  $languages  every language code Telegram serves, deduped, in order
     * @param  bool  $notModified  true when the server answered PassPortConfigNotModified
     */
    public function __construct(
        public readonly int $hash = 0,
        public readonly array $countries = [],
        public readonly array $languages = [],
        public readonly bool $notModified = false,
    ) {}

    /**
     * @param  array<string, mixed>  $response
     *
     * @throws PassportLoginException when `countries_langs` is present but is
     *                                not decodable JSON — a silently empty country list would render
     *                                an empty form with no error
     */
    public static function fromResponse(array $response): self
    {
        $constructor = (string) ($response['_'] ?? '');

        if ($constructor === 'help.passportConfigNotModified') {
            return new self(
                hash: (int) ($response['hash'] ?? 0),
                notModified: true,
            );
        }

        $hash = (int) ($response['hash'] ?? 0);
        $raw = $response['countries_langs'] ?? null;

        if ($raw === null || $raw === '') {
            return new self(hash: $hash);
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                throw new PassportLoginException(
                    'help.getPassportConfig returned an undecodable countries_langs payload (refused — no silent empty form)'
                );
            }
            $raw = $decoded;
        }

        if (! is_array($raw)) {
            throw new PassportLoginException(
                'help.getPassportConfig returned a countries_langs value that is not a country list'
            );
        }

        $countries = [];
        $languages = [];
        foreach ($raw as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $code = (string) ($entry[0] ?? '');
            if ($code === '') {
                continue;
            }

            $default = (string) ($entry[1] ?? '');
            if ($default !== '') {
                $languages[] = $default;
            }

            // Optional third member: the country's other offered languages.
            $offered = [];
            $extra = $entry[2] ?? null;
            if (is_array($extra)) {
                foreach ($extra as $language) {
                    if (is_string($language) && $language !== '') {
                        $offered[] = $language;
                        $languages[] = $language;
                    }
                }
            }

            $countries[] = [
                'code' => $code,
                'default_language' => $default,
                'languages' => $offered,
            ];
        }

        return new self(
            hash: $hash,
            countries: $countries,
            languages: array_values(array_unique($languages)),
        );
    }

    /** One country's entry, or null. */
    public function country(string $code): ?array
    {
        foreach ($this->countries as $country) {
            if ($country['code'] === $code) {
                return $country;
            }
        }

        return null;
    }

    /** True when Telegram serves this country (the form's country allow-list). */
    public function servesCountry(string $code): bool
    {
        return $this->country($code) !== null;
    }

    /**
     * @return array{hash: int, not_modified: bool, countries: list<string>, languages: list<string>}
     */
    public function toArray(): array
    {
        $codes = [];
        foreach ($this->countries as $country) {
            $codes[] = $country['code'];
        }

        return [
            'hash' => $this->hash,
            'not_modified' => $this->notModified,
            'countries' => $codes,
            'languages' => $this->languages,
        ];
    }
}
