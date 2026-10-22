<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\App\Ai;

/**
 * Default no-op base for AppAiBridge implementations.
 */
abstract class AbstractAppAiBridge implements AppAiBridge
{
    public function decide(mixed $context = null): mixed
    {
        return null;
    }

    public function evaluate(mixed $trigger = null, mixed $update = null): mixed
    {
        return null;
    }

    public function shouldHandle(mixed ...$args): bool
    {
        return true;
    }

    public function handle(mixed ...$args): mixed
    {
        return null;
    }

    public function generate(mixed $context = null): ?string
    {
        return null;
    }

    public function reply(mixed $context = null): ?string
    {
        return null;
    }

    public function answer(mixed $context = null): ?string
    {
        return null;
    }

    public function infer(mixed $context = null): mixed
    {
        return null;
    }

    public function suggest(mixed $context = null): mixed
    {
        return null;
    }

    public function transform(mixed $value = null, mixed $context = null): mixed
    {
        return $value;
    }

    public function declaration(): array
    {
        return [];
    }

    public function obligation(mixed $declaration = null): array
    {
        return [];
    }

    public function indication(mixed $obligation = null): mixed
    {
        return null;
    }

    public function __call(string $name, array $arguments): mixed
    {
        return null;
    }
}
