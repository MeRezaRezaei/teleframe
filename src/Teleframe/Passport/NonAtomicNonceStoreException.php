<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use RuntimeException;

/**
 * NonAtomicNonceStoreException — the LOUD, at-bind-time refusal for a host
 * that demanded a hard one-time guarantee from a store that cannot give one.
 *
 * The question this answers: when two consumes genuinely race on a store with
 * no atomic get-and-delete, is silently accepting both right, or should one be
 * refused? Neither is available AT CONSUME TIME on such a store — by the time
 * both callers hold a value, the store has no way to say which one was first;
 * the information needed to refuse the loser does not exist. Refusing anyway
 * would be a coin flip dressed up as a guarantee.
 *
 * So the refusal happens where the information DOES exist: at binding time.
 * A host that sets teleframe.passport.require_atomic_nonce is telling the
 * module "a double login is worse than a failed boot", and this exception is
 * how that preference is honoured — loudly, at resolve, with the store class
 * named. A host that does not set it keeps the documented best-effort
 * behaviour, which is still single-use for every sequential consume.
 *
 * NEVER logged with a nonce; the message names the store, never a key.
 */
final class NonAtomicNonceStoreException extends RuntimeException
{
    /**
     * The refusal a host opted into with teleframe.passport.require_atomic_nonce.
     * Catchable as RuntimeException like every other module refusal, and as
     * this type when the host wants to distinguish "my cache cannot give me a
     * hard one-time guarantee" from any other bind-time failure.
     *
     * Names the STORE CLASS, never a key and never a nonce.
     */
    public static function forStore(string $storeClass): self
    {
        return new self(
            'teleframe.passport.require_atomic_nonce is on, but the PSR-16 store ('.$storeClass
            .') cannot do an atomic one-time consume: PSR-16 has no get-and-delete, so a webhook '
            .'redelivery or a double submit could consume one nonce twice and log in twice. '
            .'Point the cache at Redis (the module wraps a Redis store in '
            .AtomicCacheStore::class.' automatically), bind your own '.AtomicCacheStore::class
            .' store, or clear require_atomic_nonce to accept the documented best-effort window.'
        );
    }
}
