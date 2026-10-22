<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\App\Ai;

/**
 * AppAiBridge (design §6 Q6 / wave 3): the seam for HOST-OWNED AI
 * read-and-answer wiring. The engine ships no LLM provider; an app's
 * trigger surface is the "read" half and `AppRuntime::answer()` is the
 * "send" half, and this bridge is the optional hook a host plugs between
 * them to inject an AI decision. Minimal, composable, no container calls.
 */
interface AppAiBridge
{
    public function decide(mixed $context = null): mixed;

    public function evaluate(mixed $trigger = null, mixed $update = null): mixed;

    public function shouldHandle(mixed ...$args): bool;

    public function handle(mixed ...$args): mixed;

    public function generate(mixed $context = null): ?string;

    public function reply(mixed $context = null): ?string;

    public function answer(mixed $context = null): ?string;

    public function infer(mixed $context = null): mixed;

    public function suggest(mixed $context = null): mixed;

    public function transform(mixed $value = null, mixed $context = null): mixed;

    public function declaration(): array;

    public function obligation(mixed $declaration = null): array;

    public function indication(mixed $obligation = null): mixed;
}
