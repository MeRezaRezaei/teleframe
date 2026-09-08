<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Stage;

use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

/**
 * ONE FormRequest serves BOTH legs of the "same route" gate: the web leg
 * validates through Laravel's own container; the teleframe (stage) leg
 * validates the accumulated plain-array data against the IDENTICAL
 * `rules()` — via the static helpers, no app container required.
 *
 * The base Framework/FormRequest has no own `rules()` (it only probes
 * `method_exists` before calling through the container), so `abstract rules()`
 * is a safe, additive constraint on top.
 */
abstract class StageFormRequest extends FormRequest
{
    /**
     * The stage leg's rule source — declare once, both legs share it.
     *
     * @return array<string, list<mixed>>
     */
    abstract public function rules(): array;

    /** @return array<string, list<mixed>> */
    public static function rulesFor(string $class): array
    {
        if (! is_subclass_of($class, self::class)) {
            throw new InvalidArgumentException(sprintf(
                '%s must extend %s to expose stage rules().',
                $class,
                self::class,
            ));
        }

        /** @var class-string<static> $class */
        $instance = new $class();

        return $instance->rules();
    }

    /**
     * Validate `$data` (the stage leg) with the same rules() the web leg
     * uses. Returns the validated subset or throws a StageValidationException.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public static function validateStageData(string $class, array $data): array
    {
        $validator = new StageValidator();

        return $validator->data($data, self::rulesFor($class));
    }

    /**
     * Non-throwing variant: the error map (field → messages) when `$data`
     * fails, empty when it passes. Backs the Q20 sub-dispatch gate.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, list<string>>
     */
    public static function stageErrors(string $class, array $data): array
    {
        $validator = new StageValidator();

        return $validator->errors($data, self::rulesFor($class));
    }
}