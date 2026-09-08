<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Stage\Fixtures\Forms;

use MeRezaRezaei\Teleframe\Stage\StageFormRequest;

/**
 * ONE FormRequest serving BOTH legs of the "same route" gate: the web form
 * posts here, and the teleframe stage sub-dispatches the identical route
 * with its accumulated data — validated by the IDENTICAL rules() below.
 */
final class SignupFormRequest extends StageFormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'tos' => ['required', 'accepted'],
        ];
    }
}