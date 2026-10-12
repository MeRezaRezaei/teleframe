<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use InvalidArgumentException;

/**
 * CapabilityTable (design §6): DATA rows {feature, base, unit,
 * premiumMultiplier} — the extendable limit source. A row IS a decision:
 * the checker contains NO hardcoded numbers, it only reads rows off this
 * table. Hosts extend/override via merge() (config rows); premium is
 * honored exactly once, inside effective(), by the row's multiplier.
 *
 * Row shape:  feature: string
 *             base:    int (in the doc's unit — see unit)
 *             unit:    'chars' | 'items' | 'MiB' | 'GiB' | 'msg/s' | 'msg/h'
 *             premiumMultiplier: float (1.0 = not premium-raised)
 *
 * effective() converts file rows to the checkable unit (bytes): MiB/GiB →
 * bytes — that conversion is table data, never checker logic.
 *
 * The two file transports are separate rows because the design's
 * effective(string, bool) has no transport parameter: file_upload_bot
 * (50 MiB ×1) vs file_upload_mtproto (2 GiB ×2 → 4 GiB premium).
 */
final class CapabilityTable
{
    /** Unit conversion data (MiB/GiB → bytes), owned by the table. */
    private const MEBIBYTE = 1048576; // 2^20

    private const GIBIBYTE = 1073741824; // 2^30

    /** @var array<string, array{feature: string, base: int, unit: string, premiumMultiplier: float}> */
    private array $rows;

    /**
     * @param  array<string, array{feature?: string, base?: int, unit?: string, premiumMultiplier?: int|float}>|null  $rows
     *                                                                                                                       override/extend the defaults; null = ship defaults
     */
    public function __construct(?array $rows = null)
    {
        $this->rows = $rows === null
            ? $this->normalizeAll(self::DEFAULT_ROWS)
            : $this->normalizeAll($rows);
    }

    /**
     * One row as stored, or null when the feature is unknown.
     *
     * @return array{feature: string, base: int, unit: string, premiumMultiplier: float}|null
     */
    public function row(string $feature): ?array
    {
        return $this->rows[$feature] ?? null;
    }

    /**
     * All rows, keyed by feature — feedable straight back into merge().
     *
     * @return array<string, array{feature: string, base: int, unit: string, premiumMultiplier: float}>
     */
    public function rows(): array
    {
        return $this->rows;
    }

    /**
     * Merge host/config rows into a NEW table (immutable): a row with an
     * explicit 'feature' key wins for naming, otherwise the array key is
     * the feature name; an existing feature is overridden, a new one added.
     *
     * @param  array<string|int, array{feature?: string, base?: int, unit?: string, premiumMultiplier?: int|float}>  $rows
     */
    public function merge(array $rows): self
    {
        $merged = $this->rows;
        foreach ($rows as $key => $row) {
            $feature = $this->featureOf($row, $key);
            $merged[$feature] = $this->normalizeRow($feature, $row);
        }

        return new self($merged);
    }

    /**
     * The effective limit for one feature, premium applied: base ×
     * premiumMultiplier when $premium (×1.0 otherwise), converted to the
     * checkable unit (file rows → bytes). Fractional results truncate
     * toward zero — conservative, never grants above the row.
     *
     * @throws InvalidArgumentException on an unknown feature
     */
    public function effective(string $feature, bool $premium): int
    {
        $row = $this->rows[$feature] ?? null;
        if ($row === null) {
            throw new InvalidArgumentException('unknown capability feature: '.$feature);
        }

        $value = $row['base'] * ($premium ? $row['premiumMultiplier'] : 1.0);
        if ($row['unit'] === 'MiB') {
            $value *= self::MEBIBYTE;
        } elseif ($row['unit'] === 'GiB') {
            $value *= self::GIBIBYTE;
        }

        return (int) $value;
    }

    /** @var array<string, array{feature: string, base: int, unit: string, premiumMultiplier: float}> */
    private const DEFAULT_ROWS = [
        'text_message' => ['feature' => 'text_message', 'base' => 4096, 'unit' => 'chars', 'premiumMultiplier' => 1.0],
        'caption' => ['feature' => 'caption', 'base' => 1024, 'unit' => 'chars', 'premiumMultiplier' => 1.0],
        'media_album' => ['feature' => 'media_album', 'base' => 10, 'unit' => 'items', 'premiumMultiplier' => 1.0],
        'file_upload_bot' => ['feature' => 'file_upload_bot', 'base' => 50, 'unit' => 'MiB', 'premiumMultiplier' => 1.0],
        'file_upload_mtproto' => ['feature' => 'file_upload_mtproto', 'base' => 2, 'unit' => 'GiB', 'premiumMultiplier' => 2.0],
        'send_rate' => ['feature' => 'send_rate', 'base' => 30, 'unit' => 'msg/s', 'premiumMultiplier' => 4.5],
        'per_chat_rate' => ['feature' => 'per_chat_rate', 'base' => 1, 'unit' => 'msg/s', 'premiumMultiplier' => 4.5],
        'topic_budget' => ['feature' => 'topic_budget', 'base' => 60, 'unit' => 'msg/h', 'premiumMultiplier' => 4.5],
    ];

    /**
     * @param  array<string, array{feature?: string, base?: int, unit?: string, premiumMultiplier?: int|float}>  $rows
     * @return array<string, array{feature: string, base: int, unit: string, premiumMultiplier: float}>
     */
    private function normalizeAll(array $rows): array
    {
        $normalized = [];
        foreach ($rows as $key => $row) {
            $feature = $this->featureOf($row, $key);
            $normalized[$feature] = $this->normalizeRow($feature, $row);
        }

        return $normalized;
    }

    /**
     * @param  array{feature?: string, base?: int, unit?: string, premiumMultiplier?: int|float}  $row
     */
    private function featureOf(array $row, int|string $key): string
    {
        if (isset($row['feature']) && $row['feature'] !== '') {
            return $row['feature'];
        }

        if (is_string($key) && $key !== '') {
            return $key;
        }

        throw new InvalidArgumentException('capability row is missing a feature name');
    }

    /**
     * @param  array{feature?: string, base?: int, unit?: string, premiumMultiplier?: int|float}  $row
     * @return array{feature: string, base: int, unit: string, premiumMultiplier: float}
     */
    private function normalizeRow(string $feature, array $row): array
    {
        $base = $row['base'] ?? null;
        $unit = $row['unit'] ?? null;
        $multiplier = $row['premiumMultiplier'] ?? null;

        if (! is_int($base) || $base < 0) {
            throw new InvalidArgumentException('capability row '.$feature.': base must be a non-negative int');
        }
        if (! is_string($unit) || $unit === '') {
            throw new InvalidArgumentException('capability row '.$feature.': unit must be a non-empty string');
        }
        if (! is_int($multiplier) && ! is_float($multiplier)) {
            throw new InvalidArgumentException('capability row '.$feature.': premiumMultiplier must be a number');
        }
        if ($multiplier < 0) {
            throw new InvalidArgumentException('capability row '.$feature.': premiumMultiplier must be >= 0');
        }

        return [
            'feature' => $feature,
            'base' => $base,
            'unit' => $unit,
            'premiumMultiplier' => (float) $multiplier,
        ];
    }
}
