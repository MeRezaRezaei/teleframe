<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputEncryptedFile of InputEncryptedFile.
 */
final class InputEncryptedFileData extends TlInputEncryptedFileAbstractData
{
    public function __construct(
    public int $id,
    public int $accessHash,
    ) {
    }
}
