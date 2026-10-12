<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for fileHash of FileHash.
 *
 * bytes params carried as base64 strings: hash
 */
final class FileHashData extends TlFileHashAbstractData
{
    public function __construct(
    public int $offset,
    public int $limit,
    public string $hash,
    ) {
    }
}
