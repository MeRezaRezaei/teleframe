<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

use MeRezaRezaei\Teleframe\Handler\Middleware\EchoEliminator;
use MeRezaRezaei\Teleframe\Handler\Middleware\ReplayDedup;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * The one dispatch point both intake rows funnel through (friction I.1
 * dissolved): resolve the matched handler, run the middleware onion (echo
 * eliminator + replay dedup first by default), then invoke the handler with
 * the uprate and its matched ``%s`` arg.
 *
 * Handler invocation is DI-flavored minimism per Q4/Q18: parameters typed
 * ``Update``, ``ValidatedUpdate`` or ``TelegramContext`` receive the current
 * frame; any other class parameter is resolved from the PSR-11 delegate
 * container (null when unbound); a builtin ``string $arg`` parameter receives
 * the extracted sscanf token; untyped parameters receive the Update (closure
 * convenience). v1 handlers return void (Q6); replies go through the facade,
 * not the return value.
 */
final class UpdateDispatcher
{
    /** Count of updates dropped by the replay dedup middleware (spec 5a §3). */
    private int $seenReplays = 0;

    /** @var list<\Closure|EchoEliminator|ReplayDedup> */
    private array $middleware;

    /**
     * @param list<callable> $middleware extra middleware layered AFTER the
     *        echo eliminator and replay dedup (which stay first)
     */
    public function __construct(
        private readonly HandlerRegistry $registry,
        private readonly Pipeline $pipeline,
        private readonly ContainerInterface $container,
        private readonly CacheInterface $sends,
        array $middleware = [],
        int $dedupTtl = 60,
    ) {
        $eliminator = new EchoEliminator($sends, new HandlerMatcher($registry));
        $dedup = new ReplayDedup($sends, $dedupTtl, function (): void {
            ++$this->seenReplays;
        });
        $this->middleware = [$eliminator, $dedup, ...$middleware];
    }

    public function seenReplays(): int
    {
        return $this->seenReplays;
    }

    /**
     * Route and dispatch one update through the real pipeline. Returns the
     * handler result (null when no handler matched, an echo was
     * eliminated, or a replay was deduped) — absent a match, nothing is
     * dispatched.
     */
    public function dispatch(Update $update): mixed
    {
        $match = (new HandlerMatcher($this->registry))->match($update->constructor());
        if ($match === null) {
            return null;
        }

        $handler = $match->handler;
        $arg = $match->arg();

        $terminal = function (Update $frame) use ($handler, $arg): mixed {
            $context = new TelegramContext($frame);
            $delegate = $this->resolve($handler->handler);

            return $this->invoke($delegate, $frame, $context, $arg);
        };

        $runner = $this->pipeline->then($this->middleware, $terminal);

        return $runner($update);
    }

    /** Resolve a PSR-11-addressable handler to an invocable callable. */
    private function resolve(string|array|\Closure $handler): callable
    {
        if ($handler instanceof \Closure) {
            return $handler;
        }

        if (is_array($handler)) {
            [$class, $method] = $handler;
            /** @var object $instance */
            $instance = $this->container->get($class);

            return [$instance, $method];
        }

        /** @var object|string|array<mixed> $service */
        $service = $this->container->get($handler);

        if (is_array($service) || is_string($service)) {
            return [$service, '__invoke'];
        }

        return $service instanceof \Closure ? $service : [$service, '__invoke'];
    }

    private function invoke(callable $handler, Update $update, TelegramContext $context, ?string $arg = null): mixed
    {
        $args = [];

        foreach ($this->reflect($handler)->getParameters() as $parameter) {
            $type = $parameter->getType();
            $name = $type instanceof \ReflectionNamedType && ! $type->isBuiltin()
                ? $type->getName()
                : null;

            if ($name === Update::class) {
                $args[] = $update;
                continue;
            }

            if ($name === ValidatedUpdate::class) {
                $args[] = new ValidatedUpdate($update);
                continue;
            }

            if ($name === TelegramContext::class) {
                $args[] = $context;
                continue;
            }

            if ($name !== null && $this->container->has($name)) {
                $args[] = $this->container->get($name);
                continue;
            }

            if ($name === null && $parameter->getName() === 'arg' && $arg !== null) {
                $args[] = $arg;
                continue;
            }

            $args[] = $update;
        }

        return $handler(...$args);
    }

    private function reflect(callable $handler): \ReflectionFunctionAbstract
    {
        if (is_array($handler)) {
            return new \ReflectionMethod($handler[0], $handler[1]);
        }

        if (is_string($handler) && str_contains($handler, '::')) {
            [$class, $method] = explode('::', $handler, 2);

            return new \ReflectionMethod($class, $method);
        }

        if (is_object($handler) && ! $handler instanceof \Closure) {
            return new \ReflectionMethod($handler, '__invoke');
        }

        return new \ReflectionFunction($handler);
    }
}