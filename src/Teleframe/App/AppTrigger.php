<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\App;

/**
 * AppTrigger (design §2/G1) — ONE declarative trigger: a match string in
 * the SHIPPED matcher grammar only (exact `Foo`, prefix `FooBar*`, bare `*`
 * catch-all, single sscanf token `/start %s` — zero new grammar, exactly
 * HandlerMatcher) bound to the substrate's handler form (closure
 * `fn (Update $u): void`, PSR-11-addressable invokable service id, or
 * `[Class::class, 'method']` — precisely the forms UpdateDispatcher::resolve
 * accepts). Pure immutable data row; AppResponder later installs it onto
 * the shared HandlerRegistry under tag 'app:{name}' with the match grammar
 * passing through untouched.
 *
 * The single-`%s` guard below mirrors HandlerRegistry::on's own contract
 * (more than one token is refused there at registration time) so a broken
 * pattern fails LOUD at declare time, not at dispatch.
 */
final class AppTrigger
{
    /**
     * @param  string|array|\Closure  $handler  the UpdateDispatcher::resolve
     *                                          handler form: closure, PSR-11 service id, or [class, method]
     */
    public function __construct(
        public readonly string $match,
        public readonly string|array|\Closure $handler,
        public readonly int $priority = 0,
        public readonly bool $onOwn = false,
    ) {
        if (substr_count($match, '%s') > 1) {
            throw new \InvalidArgumentException(sprintf(
                'match pattern [%s] carries more than one %%s token; the shipped grammar allows at most one',
                $match,
            ));
        }
    }

    /**
     * Named constructor (design §3 usage) — one declarative trigger.
     *
     * @param  string|array|\Closure  $handler  closure `fn (Update $u): void`,
     *                                          PSR-11-addressable invokable, or `[Class::class, 'method']`
     *                                          (the forms UpdateDispatcher::resolve accepts)
     */
    public static function on(string $match, string|array|\Closure $handler, int $priority = 0, bool $onOwn = false): self
    {
        return new self($match, $handler, $priority, $onOwn);
    }

    /**
     * The declarative row (design §2). The handler keeps its substrate
     * form: a closure is the valid runtime form but is not
     * JSON-serializable — hosts that serialize rows substitute their own
     * handler reference.
     *
     * @return array{match: string, handler: string|array|\Closure, priority: int, on_own: bool}
     */
    public function toArray(): array
    {
        return [
            'match' => $this->match,
            'handler' => $this->handler,
            'priority' => $this->priority,
            'on_own' => $this->onOwn,
        ];
    }
}
