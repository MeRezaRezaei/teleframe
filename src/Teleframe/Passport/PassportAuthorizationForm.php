<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PassportAuthorizationForm (Cycle 18 W3) — the value object behind
 * `account.authorizationForm#ad2e1cd8`, the response of
 * `account.getAuthorizationForm#a929597a` (OFFICIAL layer-227 ids,
 * TL_telegram_v227.tl:2298).
 *
 * This is the "which Telegram user is asking for your passport data, and what
 * do they need" answer, and it is where Telegram reports PER-FIELD refusals
 * instead of failing the call: `errors:Vector<SecureValueError>` is parsed here
 * into typed `PassportFieldError`s, which is the success criterion
 * "SecureValueError surfaces per-field errors" — surfaced, not dropped and not
 * collapsed to a boolean.
 *
 * `users` is the matched Telegram user(s) the form is bound to, `required_types`
 * the SecureValueTypes Telegram wants, `privacy_policy_url` the consent text the
 * host must show. `hasErrors()` is the one question an orchestrator asks before
 * rendering a form.
 *
 * Zero-regex; plain data.
 */
final class PassportAuthorizationForm
{
    /**
     * @param  list<array<string, mixed>>  $users  the matched telegram users, raw
     * @param  list<string>  $requiredTypes  the SecureValueTypes Telegram needs
     * @param  list<PassportFieldError>  $fieldErrors  per-field refusals, in Telegram's order
     * @param  string  $privacyPolicyUrl  the consent URL, '' when absent
     * @param  array<string, mixed>  $raw  the untouched response, for a caller that needs a member not modelled here
     */
    public function __construct(
        public readonly array $users = [],
        public readonly array $requiredTypes = [],
        public readonly array $fieldErrors = [],
        public readonly string $privacyPolicyUrl = '',
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $response
     *
     * @throws PassportLoginException when the response is not an authorization form
     */
    public static function fromResponse(array $response): self
    {
        $constructor = (string) ($response['_'] ?? '');
        if ($constructor !== 'account.authorizationForm') {
            throw new PassportLoginException(
                'account.getAuthorizationForm returned "'.($constructor === '' ? 'none' : $constructor)
                .'" instead of account.authorizationForm'
            );
        }

        $users = [];
        $rawUsers = $response['users'] ?? null;
        if (is_array($rawUsers)) {
            foreach ($rawUsers as $user) {
                if (is_array($user)) {
                    $users[] = $user;
                }
            }
        }

        $requiredTypes = [];
        $rawTypes = $response['required_types'] ?? null;
        if (is_array($rawTypes)) {
            foreach ($rawTypes as $type) {
                if (is_string($type) && $type !== '') {
                    $requiredTypes[] = $type;
                }
            }
        }

        return new self(
            users: $users,
            requiredTypes: $requiredTypes,
            fieldErrors: PassportFieldError::listFromForm($response),
            privacyPolicyUrl: (string) ($response['privacy_policy_url'] ?? ''),
            raw: $response,
        );
    }

    /** True when Telegram reported at least one per-field refusal. */
    public function hasErrors(): bool
    {
        return $this->fieldErrors !== [];
    }

    /** The telegram user id the form is bound to, or 0 when it names none. */
    public function userId(): int
    {
        foreach ($this->users as $user) {
            $id = $user['id'] ?? null;
            if (is_int($id)) {
                return $id;
            }

            if (is_string($id) && $id !== '' && ltrim($id, '0123456789') === '') {
                return (int) $id;
            }
        }

        return 0;
    }

    /**
     * Throw the per-field refusals as ONE typed exception carrying all of them,
     * so a caller that must not proceed has a loud, per-field reason.
     *
     * @throws PassportFieldErrorsException when the form carries field errors
     */
    public function assertNoFieldErrors(string $method = ''): void
    {
        if ($this->hasErrors()) {
            throw new PassportFieldErrorsException($this->fieldErrors, $method);
        }
    }

    /**
     * @return array{user_id: int, required_types: list<string>, field_errors: list<array{constructor: string, type: string, field: string, text: string}>, privacy_policy_url: string}
     */
    public function toArray(): array
    {
        $errors = [];
        foreach ($this->fieldErrors as $error) {
            $errors[] = $error->toArray();
        }

        return [
            'user_id' => $this->userId(),
            'required_types' => $this->requiredTypes,
            'field_errors' => $errors,
            'privacy_policy_url' => $this->privacyPolicyUrl,
        ];
    }
}
