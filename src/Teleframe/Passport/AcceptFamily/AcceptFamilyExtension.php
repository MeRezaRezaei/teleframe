<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport\AcceptFamily;

use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use RuntimeException;

/**
 * AcceptFamilyExtension — process-level answer to "is the accept-family wire
 * extension enabled?", following the AmqpExtension shape: a per-instance fact
 * with static declare/detect seams, so a host (or a test) can pin the answer
 * without touching the machine's real config.
 *
 * Default: disabled (false), mirroring `teleframe.passport.accept_family`.
 */
final class AcceptFamilyExtension
{
    private static ?bool $declared = null;

    private function __construct(private readonly bool $enabled) {}

    public static function present(): self
    {
        return new self(true);
    }

    public static function absent(): self
    {
        return new self(false);
    }

    public static function detected(): self
    {
        return new self(self::$declared ?? false);
    }

    public static function declare(bool $enabled): void
    {
        self::$declared = $enabled;
    }

    public static function detect(): void
    {
        self::$declared = null;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @throws RuntimeException when disabled
     */
    public function assertEnabled(): void
    {
        if ($this->enabled) {
            return;
        }

        throw new RuntimeException(
            'passport accept-family wire extension is disabled (set teleframe.passport.accept_family=true)'
        );
    }

    public static function fromConfig(PassportConfig $config): self
    {
        return new self($config->acceptFamily());
    }
}
