<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/**
 * Union DTO base for TL type InputPrivacyKey.
 *
 * @method static static hydrate(array $payload)
 */
abstract class TlInputPrivacyKeyAbstractData extends Data
{
    /** @var array<string, class-string<self>> */
    protected const DISPATCH = [
        'inputPrivacyKeyAbout' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputPrivacyKeyAboutData::class,
        'inputPrivacyKeyAddedByPhone' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputPrivacyKeyAddedByPhoneData::class,
        'inputPrivacyKeyBirthday' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputPrivacyKeyBirthdayData::class,
        'inputPrivacyKeyChatInvite' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputPrivacyKeyChatInviteData::class,
        'inputPrivacyKeyForwards' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputPrivacyKeyForwardsData::class,
        'inputPrivacyKeyNoPaidMessages' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputPrivacyKeyNoPaidMessagesData::class,
        'inputPrivacyKeyPhoneCall' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputPrivacyKeyPhoneCallData::class,
        'inputPrivacyKeyPhoneNumber' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputPrivacyKeyPhoneNumberData::class,
        'inputPrivacyKeyPhoneP2P' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputPrivacyKeyPhoneP2PData::class,
        'inputPrivacyKeyProfilePhoto' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputPrivacyKeyProfilePhotoData::class,
        'inputPrivacyKeySavedMusic' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputPrivacyKeySavedMusicData::class,
        'inputPrivacyKeyStarGiftsAutoSave' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputPrivacyKeyStarGiftsAutoSaveData::class,
        'inputPrivacyKeyStatusTimestamp' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputPrivacyKeyStatusTimestampData::class,
        'inputPrivacyKeyVoiceMessages' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputPrivacyKeyVoiceMessagesData::class,
    ];

    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [];

    /** Dispatch on the constructor name carried under the '_' key of a decoded wire payload. */
    public static function hydrate(array $payload): static
    {
        $class = static::DISPATCH[$payload['_']]
            ?? throw new \InvalidArgumentException('Unknown constructor ' . $payload['_'] . ' for InputPrivacyKey');
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
