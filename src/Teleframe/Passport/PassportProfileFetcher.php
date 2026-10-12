<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use Psr\Log\LoggerInterface;

/**
 * PassportProfileFetcher (Cycle 18 W3) — `help.getPassportConfig#c661ad08
 * hash:int = help.PassportConfig`, the OFFICIAL layer-227 constructor
 * (TL_telegram_v227.tl:2719, mirrored in src/Schema/schema/sources/api_full.tl;
 * on the wire closure since the 2026-09-25 seed completion, gap analysis §3.1).
 *
 * One call, one job: hand Telegram the hash the host already has and get back
 * either the country/language profile or "not modified". The host caches the
 * hash; the fetcher never caches the payload itself (that is the host's PSR-16
 * decision, not this class's).
 *
 * Refusals propagate as themselves (PASSWORD_REQUIRED included) so a caller can
 * route them; this class logs the response CONSTRUCTOR only, never the payload.
 *
 * Zero-regex; no container; plain-PHP constructible.
 */
final class PassportProfileFetcher
{
    public function __construct(
        private readonly PassportWireContract $wire,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * @param  int|null  $hash  a previously received hash; null fetches the
     *                          full profile
     *
     * @throws PassportLoginException when the answer is not a passport config
     */
    public function fetch(?int $hash = null): PassportProfile
    {
        $response = $this->wire->call(PassportWireContract::METHOD_PASSPORT_CONFIG, [
            'hash' => $hash ?? 0,
        ]);

        $constructor = (string) ($response['_'] ?? '');
        if ($constructor === '') {
            throw new PassportLoginException(
                'help.getPassportConfig returned an unnamed response (refused — no silent empty profile)'
            );
        }

        $profile = PassportProfile::fromResponse($response);

        if ($this->logger !== null) {
            $this->logger->info('help.getPassportConfig returned '.$constructor, [
                'hash' => $profile->hash,
                'not_modified' => $profile->notModified,
                'countries' => count($profile->countries),
            ]);
        }

        return $profile;
    }
}
