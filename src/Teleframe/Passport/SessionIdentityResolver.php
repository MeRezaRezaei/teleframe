<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use Illuminate\Database\Eloquent\Model;

/**
 * Default identity resolver — session mode (design §2/§11, gap Q1-a).
 *
 * The nonce was issued for a known Laravel userId at request time (the host
 * starts the passport login from an identified web session); when the nonce
 * registry hands that userId back after a one-time consume, this resolver
 * returns the user for it via a bare class/key lookup. No PII matching, no
 * cross-account scan, fully offline-testable — the identity is derived from
 * the initiating session, not from any passport field (that is the wave-2
 * field mode).
 *
 * Plain-PHP rule: construction is `new SessionIdentityResolver(string,
 * string)` and resolution is an `Illuminate\Database\Eloquent\Model`-safe
 * STATIC lookup ONLY when the class actually exists and extends Model —
 * guarded with class_exists + is_subclass_of so a plain-PHP host with no
 * Laravel user class, or a typo'd class name, resolves to null (the service
 * throws loud) instead of fatalling on an unknown symbol. Tests inject a
 * fake model class as $userClass.
 */
final class SessionIdentityResolver implements IdentityResolver
{
    public function __construct(
        private readonly string $userClass = '',
        private readonly string $userKey = 'id',
    ) {}

    public function resolve(PassportPayload $payload, ?int $sessionUserId): ?Model
    {
        if ($sessionUserId === null) {
            return null;
        }

        if ($this->userClass === '' || ! class_exists($this->userClass) || ! is_subclass_of($this->userClass, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $userClass */
        $userClass = $this->userClass;

        $user = $userClass::query()
            ->where($this->userKey, $sessionUserId)
            ->first();

        return $user instanceof Model ? $user : null;
    }
}
