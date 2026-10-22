<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport\AcceptFamily;

use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use RuntimeException;

/**
 * AcceptFamily — the gated wire extension for the Layer-229 accept/import/
 * password family (auth.importAuthorization, auth.exportAuthorization,
 * account.getPassword + SRP, account.acceptAuthorization, ...).
 *
 * The extension is OFF by default: the intentional 227-wire vs 229-schema
 * gap stays intact unless a host opts in via
 * `teleframe.passport.accept_family`. Nothing here touches the core wire
 * (TLRegistry / UserScopeSchema) — it is a Passport-module seam that
 * records the gate and refuses loudly when a caller tries to use the family
 * without enabling it.
 *
 * Plain-PHP constructible, zero container, zero regex.
 */
class AcceptFamily
{
    public function __construct(private readonly bool $enabled = false) {}

    public static function fromConfig(PassportConfig $config): self
    {
        return new self($config->acceptFamily());
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @throws RuntimeException when the extension is disabled
     */
    public function assertEnabled(): void
    {
        if ($this->enabled) {
            return;
        }

        throw new RuntimeException(
            'passport accept-family wire extension is disabled (set teleframe.passport.accept_family=true)'
        );
    }
}
