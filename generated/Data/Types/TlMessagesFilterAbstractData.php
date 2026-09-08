<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/**
 * Union DTO base for TL type MessagesFilter.
 *
 * @method static static hydrate(array $payload)
 */
abstract class TlMessagesFilterAbstractData extends Data
{
    /** @var array<string, class-string<self>> */
    protected const DISPATCH = [
        'inputMessagesFilterChatPhotos' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterChatPhotosData::class,
        'inputMessagesFilterContacts' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterContactsData::class,
        'inputMessagesFilterDocument' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterDocumentData::class,
        'inputMessagesFilterEmpty' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterEmptyData::class,
        'inputMessagesFilterGeo' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterGeoData::class,
        'inputMessagesFilterGif' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterGifData::class,
        'inputMessagesFilterMusic' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterMusicData::class,
        'inputMessagesFilterMyMentions' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterMyMentionsData::class,
        'inputMessagesFilterPhoneCalls' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterPhoneCallsData::class,
        'inputMessagesFilterPhotoVideo' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterPhotoVideoData::class,
        'inputMessagesFilterPhotos' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterPhotosData::class,
        'inputMessagesFilterPinned' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterPinnedData::class,
        'inputMessagesFilterPoll' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterPollData::class,
        'inputMessagesFilterRoundVideo' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterRoundVideoData::class,
        'inputMessagesFilterRoundVoice' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterRoundVoiceData::class,
        'inputMessagesFilterUrl' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterUrlData::class,
        'inputMessagesFilterVideo' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterVideoData::class,
        'inputMessagesFilterVoice' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessagesFilterVoiceData::class,
    ];

    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [];

    /** Dispatch on the constructor name carried under the '_' key of a decoded wire payload. */
    public static function hydrate(array $payload): static
    {
        $class = static::DISPATCH[$payload['_']]
            ?? throw new \InvalidArgumentException('Unknown constructor ' . $payload['_'] . ' for MessagesFilter');
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
