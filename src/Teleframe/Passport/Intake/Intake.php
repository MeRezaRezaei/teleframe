<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport\Intake;

use MeRezaRezaei\Teleframe\Passport\EncryptedCredentials;

/**
 * Intake — convenience alias for the MTProto passport intake. Plain-PHP
 * constructible, delegates to `EncryptedCredentials`.
 */
final class Intake implements PassportIntake
{
    private readonly MtprotoIntake $inner;

    public function __construct()
    {
        $this->inner = new MtprotoIntake;
    }

    public function carriesCredentials(array $update): bool
    {
        return $this->inner->carriesCredentials($update);
    }

    public function extract(array $update): ?EncryptedCredentials
    {
        return $this->inner->extract($update);
    }
}
