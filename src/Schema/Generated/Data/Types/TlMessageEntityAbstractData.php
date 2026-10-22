<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/**
 * Union DTO base for TL type MessageEntity.
 *
 * @method static static hydrate(array $payload)
 */
abstract class TlMessageEntityAbstractData extends Data
{
    /** @var array<string, class-string<self>> */
    protected const DISPATCH = [
        'inputMessageEntityMentionName' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputMessageEntityMentionNameData::class,
        'messageEntityBankCard' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityBankCardData::class,
        'messageEntityBlockquote' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityBlockquoteData::class,
        'messageEntityBold' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityBoldData::class,
        'messageEntityBotCommand' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityBotCommandData::class,
        'messageEntityCashtag' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityCashtagData::class,
        'messageEntityCode' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityCodeData::class,
        'messageEntityCustomEmoji' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityCustomEmojiData::class,
        'messageEntityDiffDelete' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityDiffDeleteData::class,
        'messageEntityDiffInsert' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityDiffInsertData::class,
        'messageEntityDiffReplace' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityDiffReplaceData::class,
        'messageEntityEmail' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityEmailData::class,
        'messageEntityFormattedDate' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityFormattedDateData::class,
        'messageEntityHashtag' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityHashtagData::class,
        'messageEntityItalic' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityItalicData::class,
        'messageEntityMention' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityMentionData::class,
        'messageEntityMentionName' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityMentionNameData::class,
        'messageEntityPhone' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityPhoneData::class,
        'messageEntityPre' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityPreData::class,
        'messageEntitySpoiler' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntitySpoilerData::class,
        'messageEntityStrike' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityStrikeData::class,
        'messageEntityTextUrl' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityTextUrlData::class,
        'messageEntityUnderline' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityUnderlineData::class,
        'messageEntityUnknown' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityUnknownData::class,
        'messageEntityUrl' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\MessageEntityUrlData::class,
    ];

    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [];

    /** Dispatch on the constructor name carried under the '_' key of a decoded wire payload. */
    public static function hydrate(array $payload): static
    {
        $class = static::DISPATCH[$payload['_']]
            ?? throw new \InvalidArgumentException('Unknown constructor ' . $payload['_'] . ' for MessageEntity');
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
