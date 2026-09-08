<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStoryFwdHeaderStoryFwdHeader (storyFwdHeader). */
final class TlStoryFwdHeaderStoryFwdHeaderFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryFwdHeaderStoryFwdHeader> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryFwdHeaderStoryFwdHeader::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'modified' => true,
            'tl_from' => (string) new \Symfony\Component\Uid\UuidV7(),
            'from_name' => 'from_name-4',
            'story_id' => 5,
        ];
    }
}
