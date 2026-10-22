<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Obligation;

/**
 * Derived when the declaration is observable (see ObligationFactory::observable()).
 * Changes to this data must notify subscribers — the runtime reacts in real
 * time (daemon re-read, feed push); silence would keep stale truth. The
 * downstream shape is an event DTO (observer pattern).
 */
final class EventObligation extends Obligation {}
