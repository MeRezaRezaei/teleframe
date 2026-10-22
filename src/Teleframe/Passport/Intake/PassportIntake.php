<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport\Intake;

use MeRezaRezaei\Teleframe\Passport\EncryptedCredentials;

/**
 * PassportIntake — the generic intake contract for a Telegram Passport
 * response, regardless of transport (Bot API webhook or MTProto frame).
 *
 * Plain-PHP constructible; implementations are expected to be quiet (null)
 * on non-passport updates and loud only on oversized/malformed input, matching
 * `EncryptedCredentials::fromUpdate()`.
 */
interface PassportIntake
{
    /**
     * @param  array<string, mixed>  $update
     */
    public function carriesCredentials(array $update): bool;

    /**
     * @param  array<string, mixed>  $update
     */
    public function extract(array $update): ?EncryptedCredentials;
}
