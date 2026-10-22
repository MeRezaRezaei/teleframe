<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport\Intake;

use MeRezaRezaei\Teleframe\Passport\EncryptedCredentials;

/**
 * MtprotoPassportIntake — the MTProto-side intake seam for Telegram Passport.
 *
 * Wraps the credential extraction that `EncryptedCredentials::fromUpdate()`
 * performs for an MTProto-shaped `updateNewMessage` carrying a
 * `messageActionSecureValuesSentMe` action, behind a small injectable object so
 * hosts and tests can substitute the intake without reaching the static method
 * directly. Plain-PHP constructible: no container, facade, or config() calls.
 *
 * The intake is deliberately thin — it does NOT decrypt, validate, or bind:
 * decryption stays in the shipped `PassportDecryptor`, validation in
 * `PassportValueValidator`, binding in `BindingWriter`. This class only answers
 * "does this update carry passport credentials, and if so, what is the
 * envelope".
 */
final class MtprotoIntake implements PassportIntake
{
    /**
     * Whether the update carries passport credentials at all — the routing
     * predicate shared with `EncryptedCredentials::updateCarriesCredentials()`.
     *
     * @param  array<string, mixed>  $update
     */
    public function carriesCredentials(array $update): bool
    {
        return EncryptedCredentials::updateCarriesCredentials($update);
    }

    /**
     * Extract the credentials envelope from an update, or null when the update
     * does not carry well-formed credentials (the non-passport contract).
     *
     * @param  array<string, mixed>  $update
     */
    public function extract(array $update): ?EncryptedCredentials
    {
        return EncryptedCredentials::fromUpdate($update);
    }
}
