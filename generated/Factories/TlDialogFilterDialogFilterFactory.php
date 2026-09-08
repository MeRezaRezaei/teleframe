<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlDialogFilterDialogFilter (dialogFilter). */
final class TlDialogFilterDialogFilterFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogFilterDialogFilter> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogFilterDialogFilter::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'contacts' => true,
            'non_contacts' => true,
            'groups' => true,
            'broadcasts' => true,
            'bots' => true,
            'exclude_muted' => true,
            'exclude_read' => true,
            'exclude_archived' => true,
            'title_noanimate' => true,
            'tl_id' => 11,
            'title' => (string) new \Symfony\Component\Uid\UuidV7(),
            'emoticon' => 'emoticon-13',
            'color' => 14,
        ];
    }
}
