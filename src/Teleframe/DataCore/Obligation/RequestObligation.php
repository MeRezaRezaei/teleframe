<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Obligation;

/**
 * Derived when the declaration is an intake surface (see ObligationFactory::intake()).
 * Data arrives from a leg (web form or telegram update); one rules() serves
 * both — validators and FormRequests shape this obligation downstream.
 */
final class RequestObligation extends Obligation {}
