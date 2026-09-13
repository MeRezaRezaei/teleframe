<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/**
 * Union DTO base for TL type PrivacyRule.
 *
 * @method static static hydrate(array $payload)
 */
abstract class TlPrivacyRuleAbstractData extends Data
{
    /** @var array<string, class-string<self>> */
    protected const DISPATCH = [
        'privacyValueAllowAll' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyValueAllowAllData::class,
        'privacyValueAllowBots' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyValueAllowBotsData::class,
        'privacyValueAllowChatParticipants' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyValueAllowChatParticipantsData::class,
        'privacyValueAllowCloseFriends' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyValueAllowCloseFriendsData::class,
        'privacyValueAllowContacts' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyValueAllowContactsData::class,
        'privacyValueAllowPremium' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyValueAllowPremiumData::class,
        'privacyValueAllowUsers' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyValueAllowUsersData::class,
        'privacyValueDisallowAll' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyValueDisallowAllData::class,
        'privacyValueDisallowBots' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyValueDisallowBotsData::class,
        'privacyValueDisallowChatParticipants' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyValueDisallowChatParticipantsData::class,
        'privacyValueDisallowContacts' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyValueDisallowContactsData::class,
        'privacyValueDisallowUsers' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyValueDisallowUsersData::class,
    ];

    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [];

    /** Dispatch on the constructor name carried under the '_' key of a decoded wire payload. */
    public static function hydrate(array $payload): static
    {
        $class = static::DISPATCH[$payload['_']]
            ?? throw new \InvalidArgumentException('Unknown constructor ' . $payload['_'] . ' for PrivacyRule');
        foreach ((new \ReflectionMethod($class, '__construct'))->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $payload)) {
                continue;
            }
            $bits = $class::TL_FLAG_BITS[$name] ?? null;
            if ($bits !== null) {
                $word = (int) ($payload[$bits[0]] ?? 0);
                $payload[$name] = (bool) ($word >> $bits[1] & 1);
                continue;
            }
            $wireKey = self::tlWireKey($name);
            $payload[$name] = array_key_exists($wireKey, $payload) ? $payload[$wireKey] : null;
        }
        /** @var static */
        return $class::from($payload);
    }

    /** camelCase constructor param name to snake_case wire key (regex-free). */
    private static function tlWireKey(string $name): string
    {
        $out = '';
        foreach (str_split($name) as $i => $ch) {
            $out .= $i > 0 && $ch >= 'A' && $ch <= 'Z' ? '_' . strtolower($ch) : $ch;
        }
        return $out;
    }
}
