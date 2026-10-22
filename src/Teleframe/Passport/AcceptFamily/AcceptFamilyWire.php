<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport\AcceptFamily;

use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportWireContract;
use RuntimeException;

/**
 * AcceptFamilyWire — a PassportWireContract wrapper that exposes the
 * accept/import/password family ONLY when the extension is enabled.
 *
 * When disabled (default), any call to an accept-family method throws a
 * RuntimeException; non-family methods pass through to the inner wire.
 *
 * Plain-PHP constructible, zero regex.
 */
final class AcceptFamilyWire implements PassportWireContract
{
    /** @var list<string> */
    private const FAMILY = [
        'auth.importAuthorization',
        'auth.exportAuthorization',
        'account.acceptAuthorization',
        'account.getAuthorizationForm',
        'account.getPassword',
        'account.getTmpPassword',
    ];

    public function __construct(
        private readonly PassportWireContract $inner,
        private readonly bool $enabled = false,
    ) {}

    public static function fromConfig(PassportWireContract $inner, \MeRezaRezaei\Teleframe\Passport\PassportConfig $config): self
    {
        return new self($inner, $config->acceptFamily());
    }

    public function call(string $method, array $params = []): array
    {
        if (in_array($method, self::FAMILY, true) && ! $this->enabled) {
            throw new RuntimeException(
                'passport accept-family wire extension is disabled (set teleframe.passport.accept_family=true): '.$method
            );
        }

        return $this->inner->call($method, $params);
    }
}
