<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Vault;

use Illuminate\Support\ServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Console\VaultAddAccountCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\VaultAddAppCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\VaultListCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\VaultUseDefaultCommand;

final class VaultTestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            VaultAddAppCommand::class,
            VaultAddAccountCommand::class,
            VaultListCommand::class,
            VaultUseDefaultCommand::class,
        ]);
    }
}
