<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Identity\Bindings;

/**
 * Default binding writer — delegates to the REAL shipped write surface
 * `Identity\Bindings::bindLaravelUser` (reuse ruling design §6 — the binding
 * row IS the passport identity; no new table ever).
 *
 * The delegate is a LAZY closure seam (`?\Closure $delegate = null`): the
 * default closure is the only place this class references `Bindings`, and it
 * is only invoked when `bind()` is actually called — so construction stays
 * plain-PHP and container-free (standalone-smoke-green), and tests inject a
 * fake closure that mirrors the real static call shape without ever touching
 * Eloquent. The closure follows the BINDINGS argument order
 * `(object $user, int $tgId, ?int $accountId)` — the order of the real
 * `Bindings::bindLaravelUser(Model $user, int $tgId, ?int $accountId)` API —
 * while `bind()` translates the interface's `(tgId, accountId, user)` order.
 *
 * Eloquent needs a real Model to write the morph columns; a non-Model user at
 * call time is a programmer error and throws loud (the module never
 * silent-drops — same posture as the rest of the login chain). The thrown
 * \TypeError is deliberate, not a swallowed failure.
 */
final class EloquentBindingWriter implements BindingWriter, PassportProvenanceRecorder
{
    /** @var \Closure(object, int, ?int): mixed */
    private readonly \Closure $delegate;

    /** @var \Closure(int, ?int, list<string>): void */
    private readonly \Closure $provenance;

    public function __construct(?\Closure $delegate = null, ?\Closure $provenance = null)
    {
        $this->delegate = $delegate ?? static function (object $user, int $tgId, ?int $accountId): mixed {
            if (! $user instanceof Model) {
                throw new \TypeError(sprintf(
                    'EloquentBindingWriter requires an Eloquent Model user, %s given',
                    $user::class,
                ));
            }

            return Bindings::bindLaravelUser($user, $tgId, $accountId);
        };

        // Same lazy-closure posture as the bind delegate: `Bindings` is only
        // referenced when a login actually records provenance, so construction
        // stays plain-PHP and container-free (standalone-smoke gate).
        $this->provenance = $provenance ?? static function (int $tgId, ?int $accountId, array $fieldsVerified): void {
            Bindings::recordPassportLogin($tgId, $accountId, $fieldsVerified);
        };
    }

    public function bind(int $tgId, ?int $accountId, object $user): mixed
    {
        return ($this->delegate)($user, $tgId, $accountId);
    }

    /**
     * Stamp the binding row with the login's provenance (when + verified field
     * TYPES). Best effort by design: the login has already succeeded and the
     * session is already riding, so a provenance failure is swallowed rather
     * than turned into a logout. Nothing is logged — a logged failure would
     * risk carrying a type list, and provenance must never be able to break a
     * completed authentication.
     *
     * @param  list<string>  $fieldsVerified
     */
    public function recordProvenance(int $tgId, ?int $accountId, array $fieldsVerified): void
    {
        try {
            ($this->provenance)($tgId, $accountId, $fieldsVerified);
        } catch (\Throwable) {
            // Deliberately silent — see the method docblock.
        }
    }
}
