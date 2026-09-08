<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use RuntimeException;

/** Thrown on any .tl grammar or crc32 violation (spec §7.3 V1/V2). */
final class TlParseException extends RuntimeException
{
}
