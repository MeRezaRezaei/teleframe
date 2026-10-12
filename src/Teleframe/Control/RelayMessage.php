<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Control;

/**
 * A message in flight through the control layer ("sit in the middle").
 *
 * One value object serves BOTH directions so a single filter chain can see
 * the whole conversation: outbound = the web client asking our relay to
 * call Telegram; inbound = Telegram's reply / update flowing back. Filters
 * may snoop, rewrite (withMethod/withParams), or short-circuit (return a
 * different message / drop) — that is the "control what data is passing to
 * the client" seam from the 2026-09-17 verbatim.
 */
final class RelayMessage
{
    public const OUTBOUND = 'outbound';

    public const INBOUND = 'inbound';

    /** @param array<string, mixed> $params */
    public function __construct(
        public readonly string $direction,
        public readonly string $method,
        public readonly array $params,
        public readonly ?string $raw = null,
    ) {}

    public function isOutbound(): bool
    {
        return $this->direction === self::OUTBOUND;
    }

    public function isInbound(): bool
    {
        return $this->direction === self::INBOUND;
    }

    /** @param array<string, mixed> $params */
    public function withParams(array $params): self
    {
        return new self($this->direction, $this->method, $params, $this->raw);
    }

    public function withMethod(string $method): self
    {
        return new self($this->direction, $method, $this->params, $this->raw);
    }

    /** Short-circuit: replace this message (e.g. a synthetic reply). */
    public function replace(self $message): self
    {
        return $message;
    }
}
