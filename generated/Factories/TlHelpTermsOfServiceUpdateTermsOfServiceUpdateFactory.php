<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlHelpTermsOfServiceUpdateTermsOfServiceUpdate (help.termsOfServiceUpdate). */
final class TlHelpTermsOfServiceUpdateTermsOfServiceUpdateFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpTermsOfServiceUpdateTermsOfServiceUpdate> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpTermsOfServiceUpdateTermsOfServiceUpdate::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'expires' => 1,
            'terms_of_service' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
