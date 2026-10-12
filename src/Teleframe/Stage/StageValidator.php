<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Stage;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use MeRezaRezaei\Teleframe\Stage\Exceptions\StageValidationException;

/**
 * Minimal standalone Illuminate validator (Phase 5e, Q7-no-Laravel rule:
 * the teleframe package vets rules with the *same* Illuminate component a
 * hosting Laravel app uses, without requiring an app container —
 * illuminate/validation is shipped inside vendor/laravel/framework).
 *
 * Translator is seeded with the framework's built-in English messages so
 * `email`, `accepted`, `required` etc. fail with real, host-friendly copy.
 */
final class StageValidator
{
    private Factory $factory;

    public function __construct(
        private readonly array $messages = [],
    ) {
        $this->factory = new Factory(new Translator(new ArrayLoader(), 'en'));
    }

    /**
     * Run a single-attribute rule list against one captured answer and throw
     * a template-shaped failure on violation.
     *
     * @param array<int, mixed> $rules
     */
    public function validate(string $field, mixed $value, array $rules): void
    {
        $errors = $this->errors([$field => $value], [$field => $rules]);
        if ($errors !== []) {
            throw new StageValidationException($errors);
        }
    }

    /** Run full rule maps; returns data stripped of attributes not in `$rules`. */
    public function data(array $data, array $rules): array
    {
        $errors = $this->errors($data, $rules);
        if ($errors !== []) {
            throw new StageValidationException($errors);
        }

        return array_intersect_key($data, array_flip(array_keys($rules)));
    }

    /** @return array<string, list<string>> field → rule messages */
    public function errors(array $data, array $rules): array
    {
        $validator = $this->factory->make($data, $rules, $this->messages);
        if ($validator->passes()) {
            return [];
        }
        $messages = $validator->errors()->getMessages();
        $out = [];
        foreach ($messages as $field => $bag) {
            $out[(string) $field] = array_values(array_map('strval', $bag));
        }

        return $out;
    }
}