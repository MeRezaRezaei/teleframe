<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Indication;

use MeRezaRezaei\Teleframe\DataCore\Obligation\Obligation;

/**
 * The indication layer (plan §1 diagram): one derived "an obligation becomes
 * a generated shape" per obligation kind — a ValidationObligation becomes a
 * generated validator rule-map, an EventObligation a generated event DTO, a
 * RequestObligation a generated request. An indication is the design-side
 * contract a generator dials on; it carries what the generator needs (the
 * declaration name through the obligation) and never calls the generator.
 *
 * Shape decision: the plan's §1 diagram leaves the ctor underspecified, so an
 * Indication holds the Obligation it derives from rather than re-declaring
 * kind/declaration strings. The obligation ctor is pinned (kind, declaration,
 * rationale — see Obligation), so this adds no new data contract and hands
 * generators the rationale verbatim for generated-file doc comments. The
 * concrete class IS the kind; IndicationFactory::for() owns the
 * obligation-kind → class mapping.
 */
abstract class Indication
{
    public function __construct(
        public readonly Obligation $obligation,   // derivation source: kind, declaration name, rationale
    ) {}

    /**
     * Stable discriminator — the short class name ('ValidatorIndication',
     * 'RequestIndication', 'EventIndication'), mirroring Constraint::name().
     */
    abstract public function name(): string;
}
