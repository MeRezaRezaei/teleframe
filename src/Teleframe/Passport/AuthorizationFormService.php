<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use Psr\Log\LoggerInterface;

/**
 * AuthorizationFormService (Cycle 18 W3) — the request/accept pair of the
 * passport authorization family, both OFFICIAL layer-227 constructors
 * (TL_telegram_v227.tl:2298-2299, mirrored in
 * src/Schema/schema/sources/api_full.tl; on the wire closure since the
 * 2026-09-25 seed completion, gap analysis §3.1):
 *
 *   account.getAuthorizationForm#a929597a  bot_id scope public_key = account.AuthorizationForm
 *   account.acceptAuthorization#f3ed4c73    bot_id scope public_key value_hashes credentials = Bool
 *
 * Together they are the "a Telegram user shares their passport data with this
 * bot" exchange: the host asks for the form (and learns which fields Telegram
 * wants, which users match, and which fields are already refused), then submits
 * the user's encrypted values back.
 *
 * Refusal contract:
 * - `PASSWORD_REQUIRED` propagates as itself so the caller routes it to the
 *   existing PasswordConfirmer (2FA role untouched).
 * - a per-field `secureValueError` on either call is raised as ONE
 *   `PassportFieldErrorsException` carrying EVERY failing field — surfaced, not
 *   dropped, not collapsed to "accept failed".
 * - an `accept` answer that is not a true Bool is refused loud. A silent
 *   "false" would leave the host believing the values were shared.
 *
 * Zero-regex; no container; plain-PHP constructible.
 */
final class AuthorizationFormService
{
    /** account.acceptAuthorization's true answer. */
    private const BOOL_TRUE = 'boolTrue';

    public function __construct(
        private readonly PassportWireContract $wire,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * `account.getAuthorizationForm#a929597a` — ask Telegram what this bot
     * needs from this user.
     *
     * @param  int  $botId  the bot the request is for (the bot's own user id)
     * @param  string  $scope  the requested scope, e.g. "personal_details"
     * @param  string  $publicKey  the bot RSA public key, PEM
     *
     * @throws PassportLoginException when the answer is not an authorization form
     */
    public function request(int $botId, string $scope, string $publicKey): PassportAuthorizationForm
    {
        $response = $this->wire->call(PassportWireContract::METHOD_AUTHORIZATION_FORM, [
            'bot_id' => $botId,
            'scope' => $scope,
            'public_key' => $publicKey,
        ]);

        $form = PassportAuthorizationForm::fromResponse($response);

        if ($this->logger !== null) {
            $this->logger->info('account.getAuthorizationForm returned a form', [
                'user_id' => $form->userId(),
                'required_types' => $form->requiredTypes,
                'field_errors' => count($form->fieldErrors),
            ]);
        }

        return $form;
    }

    /**
     * `account.acceptAuthorization#f3ed4c73` — submit the user's encrypted
     * passport values for the bot's request.
     *
     * @param  int  $botId  the bot the request is for
     * @param  string  $scope  the requested scope
     * @param  string  $publicKey  the bot RSA public key the values were encrypted to
     * @param  list<array<string, mixed>>  $valueHashes  secureValueHash[] per accepted value
     * @param  array<string, mixed>  $credentials  the secureCredentialsEncrypted body
     *                                             (data/hash/secret — a credential, never logged)
     *
     * @throws PassportFieldErrorsException when Telegram refuses per field
     * @throws PassportLoginException on any other non-Bool-true answer
     */
    public function accept(
        int $botId,
        string $scope,
        string $publicKey,
        array $valueHashes,
        array $credentials,
    ): bool {
        $response = $this->wire->call(PassportWireContract::METHOD_ACCEPT_AUTHORIZATION, [
            'bot_id' => $botId,
            'scope' => $scope,
            'public_key' => $publicKey,
            'value_hashes' => $valueHashes,
            'credentials' => $credentials,
        ]);

        // A per-field refusal inside a non-Bool answer: surfaced per field.
        $errors = PassportFieldError::listFromForm($response);
        if ($errors !== []) {
            throw new PassportFieldErrorsException(
                $errors,
                PassportWireContract::METHOD_ACCEPT_AUTHORIZATION
            );
        }

        $constructor = (string) ($response['_'] ?? '');
        if ($constructor !== self::BOOL_TRUE) {
            throw new PassportLoginException(
                'account.acceptAuthorization answered "'.($constructor === '' ? 'none' : $constructor)
                .'" instead of boolTrue (refused — a silent false would claim values were shared)'
            );
        }

        if ($this->logger !== null) {
            $this->logger->info('account.acceptAuthorization accepted the passport values', [
                'bot_id' => $botId,
                'scope' => $scope,
                'value_hashes' => count($valueHashes),
            ]);
        }

        return true;
    }
}
