<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

/**
 * Notification log levels (design §2, §4): the 8 PSR-3 backed words
 * debug|info|notice|warning|error|critical|alert|emergency. A topic IS a
 * log level plus a placement — Level is the enum half of that ruling;
 * Topic carries the placement (chat/thread/throttle). Every level maps to
 * a default topic name 'app.{value}' so the PSR-3 bridge (G2) and the
 * logger facade can route a bare level without a registry. Zero regex:
 * matching uses strtolower/trim only.
 */
enum Level: string
{
    case Debug = 'debug';
    case Info = 'info';
    case Notice = 'notice';
    case Warning = 'warning';
    case Error = 'error';
    case Critical = 'critical';
    case Alert = 'alert';
    case Emergency = 'emergency';

    /** @return list<string> the 8 PSR-3 level names, lowercase (design: names) */
    public static function names(): array
    {
        $names = [];
        foreach (self::cases() as $case) {
            $names[] = $case->value;
        }

        return $names;
    }

    /** Parse a PSR-3 level string ('info', 'INFO', 'Error'); unknown levels throw InvalidArgumentException. */
    public static function fromPsr3(string $level): self
    {
        $normalized = strtolower(trim($level));
        foreach (self::cases() as $case) {
            if ($case->value === $normalized) {
                return $case;
            }
        }

        throw new \InvalidArgumentException(sprintf('unknown PSR-3 log level "%s"', $level));
    }

    /** The default topic this level routes to: 'app.' + level name (design §4, Level::defaultTopic). */
    public function defaultTopic(): string
    {
        return 'app.'.$this->value;
    }
}
