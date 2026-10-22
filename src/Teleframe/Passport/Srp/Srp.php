<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport\Srp;

use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Passport\PasswordConfirmerContract;
use Psr\Log\LoggerInterface;

/**
 * Srp — convenience alias for the live SRP verifier. Plain-PHP constructible.
 */
final class Srp implements PasswordConfirmerContract
{
    private readonly LiveSrp $inner;

    public function __construct(
        ?UserAccountScope $scope = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->inner = new LiveSrp($scope, $logger);
    }

    public function confirm(string $password): array
    {
        return $this->inner->confirm($password);
    }
}
