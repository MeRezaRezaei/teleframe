<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use RuntimeException;

/** Thrown when regeneration refuses to proceed (spec §7.3 V4 count gate). */
final class TlRegenerateException extends RuntimeException
{
}
