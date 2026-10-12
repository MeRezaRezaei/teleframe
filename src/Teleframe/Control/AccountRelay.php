<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Control;

/**
 * A relay bound to one vault account — the "middle" between a web client
 * and Telegram for that account.
 *
 * Construction is framework-agnostic and plain-PHP-constructible: the host
 * app wires the ticket redemption + vault lookup, then hands this class a
 * context and a scope resolver callable (production: TeleframeClient::user()
 * from the vault session; tests: a fake). Every outbound request and every
 * inbound reply/update passes through the filter onion, so the package can
 * snoop, rewrite, or drop what flows to the client — and can later swap the
 * resolver to switch which account is live (multi-account switching).
 */
final class AccountRelay
{
    /** @var list<RelayFilter> */
    private array $filters = [];

    /**
     * @param  callable(): mixed  $scopeResolver  lazy live session/scope for this account
     */
    public function __construct(
        private readonly RelayContext $context,
        private readonly mixed $scopeResolver,
        private readonly RelayPipeline $pipeline = new RelayPipeline,
    ) {}

    public function context(): RelayContext
    {
        return $this->context;
    }

    public function filters(): array
    {
        return $this->filters;
    }

    public function push(RelayFilter $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    /** Lazily resolve the live MTProto scope (never dialed until asked). */
    public function scope(): mixed
    {
        return ($this->scopeResolver)();
    }

    /**
     * Run one message through the filter onion. The terminal is what the
     * transport eventually does with the (possibly rewritten) message —
     * production will exec it against scope(); tests inject a recorder.
     *
     * @param  callable(RelayMessage): RelayMessage|null  $terminal
     */
    public function send(RelayMessage $message, ?callable $terminal = null): RelayMessage
    {
        $terminal ??= fn (RelayMessage $m): RelayMessage => $m;

        return $this->pipeline->then($this->filters, $terminal)($message);
    }
}
