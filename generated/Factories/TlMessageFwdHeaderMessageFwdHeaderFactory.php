<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageFwdHeaderMessageFwdHeader (messageFwdHeader). */
final class TlMessageFwdHeaderMessageFwdHeaderFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageFwdHeaderMessageFwdHeader> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageFwdHeaderMessageFwdHeader::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'imported' => true,
            'saved_out' => true,
            'from_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'from_name' => 'from_name-5',
            'date' => 6,
            'channel_post' => 7,
            'post_author' => 'post_author-8',
            'saved_from_peer' => (string) new \Symfony\Component\Uid\UuidV7(),
            'saved_from_msg_id' => 10,
            'saved_from_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'saved_from_name' => 'saved_from_name-12',
            'saved_date' => 13,
            'psa_type' => 'psa_type-14',
        ];
    }
}
