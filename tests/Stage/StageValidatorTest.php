<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Stage;

use MeRezaRezaei\Teleframe\Stage\Exceptions\StageValidationException;
use MeRezaRezaei\Teleframe\Stage\StageValidator;
use PHPUnit\Framework\TestCase;

/**
 * StageValidator (Phase 5e): standalone Illuminate Validation over ArrayLoader
 * — catches failed captures as a template-shaped StageValidationException with
 * REAL framework copy per field; successes return the stripped data set.
 */
final class StageValidatorTest extends TestCase
{
    private StageValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new StageValidator();
    }

    public function test_validate_passes_a_correct_answer(): void
    {
        $this->validator->validate('email', 'ada@example.com', ['required', 'email']);
        $this->addToAssertionCount(1);
    }

    public function test_validate_throws_shaped_exception_with_field_messages(): void
    {
        try {
            $this->validator->validate('email', 'not-an-email', ['required', 'email']);
            self::fail('Expected StageValidationException.');
        } catch (StageValidationException $exception) {
            $errors = $exception->errors();
            self::assertArrayHasKey('email', $errors);
            self::assertNotEmpty($errors['email']);
        }
    }

    public function test_data_validates_a_rule_set_and_returns_the_trimmed_array(): void
    {
        $data = $this->validator->data(
            ['email' => 'ada@example.com', 'name' => 'Ada', 'junk' => true],
            ['email' => ['required', 'email'], 'name' => ['required', 'string']],
        );

        self::assertSame(['email' => 'ada@example.com', 'name' => 'Ada'], $data);
    }

    public function test_data_throws_on_violation(): void
    {
        $this->expectException(StageValidationException::class);
        $this->validator->data(['tos' => 'no'], ['tos' => ['accepted']]);
    }

    public function test_errors_map_is_empty_when_valid(): void
    {
        self::assertSame([], $this->validator->errors(['email' => 'a@b.c'], ['email' => ['email']]));
        self::assertArrayHasKey('tos', $this->validator->errors(['tos' => 'no'], ['tos' => ['accepted']]));
    }
}