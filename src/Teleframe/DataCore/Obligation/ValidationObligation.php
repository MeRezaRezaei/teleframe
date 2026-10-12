<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Obligation;

/**
 * Derived when any field of the declaration carries constraints. A value
 * outside those limits is a flow shifter and must refuse entry — validators,
 * FormRequests and rules() maps shape this obligation downstream.
 */
final class ValidationObligation extends Obligation {}
