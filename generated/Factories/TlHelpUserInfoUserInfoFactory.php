<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlHelpUserInfoUserInfo (help.userInfo). */
final class TlHelpUserInfoUserInfoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpUserInfoUserInfo> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpUserInfoUserInfo::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'message' => 'message-1',
            'author' => 'author-2',
            'date' => 3,
        ];
    }
}
