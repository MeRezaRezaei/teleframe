<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

/**
 * PremiumStatus (notification-core capability cluster): the per-send /
 * per-topic premium flag as a typed value object. The capability table
 * multiplies its rows by premiumMultiplier when this is true. "Unknown
 * premium" is false — the checker conservatively applies the caps and
 * never crashes (design §12: the checker treats "unknown premium" as
 * false; premium is an explicit flag for the first slice, a live
 * users.getFullUser probe is a later best-effort).
 */
final class PremiumStatus
{
    public function __construct(public readonly bool $premium) {}

    /** Unknown premium = false (conservative: caps apply, never crashes). */
    public static function unknown(): self
    {
        return new self(false);
    }

    public static function fromBool(bool $premium): self
    {
        return new self($premium);
    }

    public function premium(): bool
    {
        return $this->premium;
    }

    public function isPremium(): bool
    {
        return $this->premium;
    }
}
