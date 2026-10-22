<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * ImportedAuthorization (Cycle 18 W3) — the value object behind
 * `auth.Authorization` (the response of `auth.importAuthorization#a57a7dad`):
 * the moment a Telegram account's authorization becomes THIS session's
 * authorization, which is what turns a passport exchange into a real login.
 *
 * Carries the imported telegram user id (the stable bigint the Identity
 * binding is keyed on — the same `tg_user_id` the webhook path resolves from
 * `message.from.id`), the raw `user` array, and the full authorization array
 * (flags/terms_of_service/user). `authorizationSignUpRequired` is NOT this
 * class: it means Telegram wants a sign-up, which is not a login, and
 * AuthorizationImporter refuses it loudly instead of returning a half-import.
 *
 * `user` is raw wire data (it can carry a profile photo reference, a username,
 * a first/last name). It is kept because the host's own `MessageFactory`/Eloquent
 * columns may want it, and it is NEVER logged by this module.
 *
 * Zero-regex; plain data.
 */
final class ImportedAuthorization
{
    /**
     * @param  int  $userId  the imported telegram user id (the Identity binding key)
     * @param  array<string, mixed>  $user  the raw `user` object from the authorization
     * @param  array<string, mixed>  $authorization  the full auth.Authorization array
     */
    public function __construct(
        public readonly int $userId,
        public readonly array $user,
        public readonly array $authorization = [],
    ) {}

    /**
     * The stable telegram bigint as a string — the form the `tl_user_id`
     * binding column and Telegram's own APIs expect (an int64 user id does not
     * survive a JSON round trip intact on every consumer).
     */
    public function userIdString(): string
    {
        return (string) $this->userId;
    }

    /**
     * True when Telegram asked the client to accept terms (auth.Authorization
     * with the `terms_of_service` flag) — surfaced, never acted on: the host
     * owns consent.
     */
    public function hasTermsOfService(): bool
    {
        return isset($this->authorization['terms_of_service']);
    }

    /**
     * Credential-free representation: the identity facts and a
     * first/last/username projection, never the raw arrays.
     *
     * @return array{user_id: int, user_id_string: string, first_name: string, last_name: string, username: string, terms_of_service: bool}
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'user_id_string' => $this->userIdString(),
            'first_name' => (string) ($this->user['first_name'] ?? ''),
            'last_name' => (string) ($this->user['last_name'] ?? ''),
            'username' => (string) ($this->user['username'] ?? ''),
            'terms_of_service' => $this->hasTermsOfService(),
        ];
    }
}
