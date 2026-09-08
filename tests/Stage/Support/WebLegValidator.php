<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Stage\Support;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Validator;

/**
 * The web-leg verdict oracle: the same Illuminate Validation components a
 * hosting Laravel app wires (bare-built) — used to prove the teleframe leg's
 * `stageErrors()` agrees with a real FormRequest-style validation run.
 */
final class WebLegValidator
{
    private Factory $factory;

    public function __construct()
    {
        $this->factory = new Factory(new Translator(new ArrayLoader(), 'en'));
    }

    public function make(array $data, array $rules): Validator
    {
        return $this->factory->make($data, $rules);
    }
}