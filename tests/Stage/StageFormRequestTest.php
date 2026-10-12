<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Stage;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Stage\Exceptions\StageValidationException;
use MeRezaRezaei\Teleframe\Stage\StageFormRequest;
use MeRezaRezaei\Teleframe\Tests\Stage\Fixtures\Forms\SignupFormRequest;
use MeRezaRezaei\Teleframe\Tests\Stage\Support\WebLegValidator;
use PHPUnit\Framework\TestCase;

/**
 * StageFormRequest (Phase 5e, "same route" gate): the SAME rules() serves
 * the web leg (instance FormRequest) and the teleframe leg (static stage
 * helpers) — both must agree on identical data.
 */
final class StageFormRequestTest extends TestCase
{
    public function test_rules_for_returns_the_form_requests_own_rules(): void
    {
        $expected = (new SignupFormRequest())->rules();

        self::assertSame($expected, StageFormRequest::rulesFor(SignupFormRequest::class));
        self::assertSame(['email', 'tos'], array_keys($expected));
    }

    public function test_validate_stage_data_passes_and_trims(): void
    {
        $validated = StageFormRequest::validateStageData(SignupFormRequest::class, [
            'email' => 'ada@example.com',
            'tos' => 'yes',
            'sneak' => 'ignored',
        ]);

        self::assertSame(['email' => 'ada@example.com', 'tos' => 'yes'], $validated);
    }

    public function test_validate_stage_data_throws_when_rules_break(): void
    {
        $this->expectException(StageValidationException::class);
        StageFormRequest::validateStageData(SignupFormRequest::class, [
            'email' => 'nope',
            'tos' => 'no',
        ]);
    }

    public function test_stage_errors_agree_with_web_leg_errors(): void
    {
        $data = ['email' => 'nope', 'tos' => 'no'];
        $rules = (new SignupFormRequest())->rules();

        $validator = (new WebLegValidator())->make($data, $rules);

        self::assertFalse($validator->passes());
        self::assertSame(
            array_keys($validator->errors()->getMessages()),
            array_keys(StageFormRequest::stageErrors(SignupFormRequest::class, $data)),
        );
    }

    public function test_stage_errors_empty_when_valid(): void
    {
        self::assertSame([], StageFormRequest::stageErrors(SignupFormRequest::class, [
            'email' => 'ada@example.com',
            'tos' => 'yes',
        ]));
    }

    public function test_rules_for_rejects_non_stage_form_requests(): void
    {
        $this->expectException(InvalidArgumentException::class);
        StageFormRequest::rulesFor(self::class);
    }
}