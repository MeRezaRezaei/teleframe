<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Obligation;

use MeRezaRezaei\Teleframe\DataCore\Declaration\DataDeclaration;

/**
 * Derives the obligation list of a declaration — "the core of the app,
 * derived, deterministic" (plan §2). Pure: no I/O, no Laravel, no random —
 * the same declaration always derives the same obligations.
 *
 * Derivation rules (exactly as plan §2 renders them):
 *   - any field with constraints -> ValidationObligation ('validate')
 *   - entityConstraints non-empty -> PersistenceObligation ('persist')
 *   - observable($d)              -> EventObligation       ('notify')
 *   - intake($d)                  -> RequestObligation     ('request')
 *
 * observable()/intake() are PURE deterministic heuristics (documented on
 * their methods); tests pin them, so they must never touch I/O or the clock.
 */
final class ObligationFactory
{
    /** @return list<Obligation> — the core of the app, derived, deterministic. */
    public function for(DataDeclaration $d): array
    {
        $out = [];
        $limited = array_filter($d->fields, static fn ($f) => $f->constraints !== []);
        if ($limited !== []) {
            $out[] = new ValidationObligation('validate', $d->name,
                'fields '.implode(', ', array_keys($limited)).' carry limits; a value outside them is a flow shifter and must refuse');
        }
        if ($d->entityConstraints !== []) {
            $out[] = new PersistenceObligation('persist', $d->name,
                'entity limits (Unique/Relation) force index/constraint shapes on the stored truth');
        }
        if ($this->observable($d)) {   // declared => change notification is an obligation, not an app choice
            $out[] = new EventObligation('notify', $d->name,
                'the runtime reacts to this data in real time (daemon re-read, feed push); silence would keep stale truth');
        }
        if ($this->intake($d)) {       // declared entry surface or RPC method
            $out[] = new RequestObligation('request', $d->name,
                'data arrives from a leg (web form or telegram update); one rules() serves both');
        }

        return $out;
    }

    /**
     * Heuristic: a declaration is observable when its name ends in 'Rule'
     * (a routing/policy rule the daemons re-read live — e.g. UpdateRoutingRule
     * has a Redis observer today) OR it declares a field named 'mode' (a mode
     * switch the runtime reacts to, e.g. act_on / store_only). Deterministic,
     * string-shape only, no I/O.
     */
    private function observable(DataDeclaration $d): bool
    {
        return str_ends_with($d->name, 'Rule') || $d->field('mode') !== null;
    }

    /**
     * Heuristic: a declaration is an intake surface when its source starts
     * with 'tl-mirror' (it mirrors a telegram TL type — data arrives from the
     * update leg) OR it declares a field named 'peer_id' (a peer-keyed record
     * a leg brings data for). Deterministic, string-shape only, no I/O.
     */
    private function intake(DataDeclaration $d): bool
    {
        return str_starts_with($d->source, 'tl-mirror') || $d->field('peer_id') !== null;
    }
}
