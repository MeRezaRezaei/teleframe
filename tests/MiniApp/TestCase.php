<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MiniApp;

use MeRezaRezaei\Teleframe\Tests\Identity\TestCase as IdentityTestCase;

/**
 * MiniApp test base (Phase 5g): mirrors the `tests/Identity/TestCase.php`
 * approach — testbench app + sqlite :memory: + the `tl_user_bindings`
 * migration so the `tg-webapp` guard's binding resolution (Q7/Q8, Phase 5b)
 * runs against the REAL shipped surface, and the artisan-compatible container
 * renders the Blade host stub exactly as a host would.
 */
abstract class TestCase extends IdentityTestCase
{
}