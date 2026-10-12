<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\MTProto\Transport;

use RuntimeException;

/**
 * Raised when an obfuscated2 connection header cannot be parsed or fails
 * validation: wrong length, unknown magic/tag, or a tag that contradicts the
 * protocol byte at offset 56. Never carries key material in the message.
 */
class Obfuscated2Exception extends RuntimeException {}
