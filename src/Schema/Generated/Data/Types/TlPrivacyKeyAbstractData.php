<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/**
 * Union DTO base for TL type PrivacyKey.
 *
 * @method static static hydrate(array $payload)
 */
abstract class TlPrivacyKeyAbstractData extends Data
{
    /** @var array<string, class-string<self>> */
    protected const DISPATCH = [
        'privacyKeyAbout' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyKeyAboutData::class,
        'privacyKeyAddedByPhone' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyKeyAddedByPhoneData::class,
        'privacyKeyBirthday' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyKeyBirthdayData::class,
        'privacyKeyChatInvite' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyKeyChatInviteData::class,
        'privacyKeyForwards' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyKeyForwardsData::class,
        'privacyKeyNoPaidMessages' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyKeyNoPaidMessagesData::class,
        'privacyKeyPhoneCall' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyKeyPhoneCallData::class,
        'privacyKeyPhoneNumber' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyKeyPhoneNumberData::class,
        'privacyKeyPhoneP2P' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyKeyPhoneP2PData::class,
        'privacyKeyProfilePhoto' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyKeyProfilePhotoData::class,
        'privacyKeySavedMusic' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyKeySavedMusicData::class,
        'privacyKeyStarGiftsAutoSave' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyKeyStarGiftsAutoSaveData::class,
        'privacyKeyStatusTimestamp' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyKeyStatusTimestampData::class,
        'privacyKeyVoiceMessages' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PrivacyKeyVoiceMessagesData::class,
    ];

    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [];

    /** Dispatch on the constructor name carried under the '_' key of a decoded wire payload. */
    public static function hydrate(array $payload): static
    {
        $class = static::DISPATCH[$payload['_']]
            ?? throw new \InvalidArgumentException('Unknown constructor ' . $payload['_'] . ' for PrivacyKey');
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
