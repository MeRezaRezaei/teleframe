<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Message;

/**
 * The `message('name')` finder (Facade-adjacent): resolves a named template
 * to the compiled entity plan `{text, entities, reply_markup?}` the send
 * engine hands to `messages.sendMessage` / `sendMessage`.
 *
 *   use MeRezaRezaei\Teleframe\Message\MessageFactory;
 *
 *   $plan = MessageFactory::message('welcome', ['name' => 'Ada']);
 *   // ['text' => 'Hello, Ada!', 'entities' => []]
 *
 * A hidden `$compiler` may be injected — Laravel hosts bind the singleton
 * here; plain-PHP/tests pass their own `MessageCompiler`. The instance API
 * (`resolve()`, `__invoke()`) is the container-friendly form.
 *
 * Q14 clamp: `media()` (and `MessageCompiler::compileFrom()`) are the
 * closure fallback for media/album sends, which a text plan can never
 * describe — run a factory closure to a plan, without caching.
 */
final class MessageFactory
{
    public function __construct(
        private readonly MessageCompiler $compiler,
    ) {
    }

    /**
     * @return array{text: string, entities: list<array<string, mixed>>, reply_markup?: array<string, mixed>}
     */
    public function resolve(string $name, array $data = []): array
    {
        return $this->compiler->compile($name, $data);
    }

    /**
     * @return array{text: string, entities: list<array<string, mixed>>, reply_markup?: array<string, mixed>}
     */
    public function __invoke(string $name, array $data = []): array
    {
        return $this->resolve($name, $data);
    }

    /**
     * Q14 closure fallback for media/albums: run a factory to a validated
     * plan without naming the template artifact.
     *
     * @param callable(array<string, mixed>): mixed $factory
     * @return array{text: string, entities: list<array<string, mixed>>, reply_markup?: array<string, mixed>}
     */
    public function media(string $name, callable $factory, array $data = []): array
    {
        return $this->compiler->compileFrom($name, $factory, $data);
    }

    /**
     * Static finder: `MessageFactory::message('name', ['var' => $value])`.
     *
     * @param array<string, mixed> $data
     * @return array{text: string, entities: list<array<string, mixed>>, reply_markup?: array<string, mixed>}
     */
    public static function message(string $name, array $data = [], ?MessageCompiler $compiler = null): array
    {
        return ($compiler ?? new MessageCompiler())->compile($name, $data);
    }
}