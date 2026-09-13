<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/**
 * Union DTO base for TL type KeyboardButton.
 *
 * @method static static hydrate(array $payload)
 */
abstract class TlKeyboardButtonAbstractData extends Data
{
    /** @var array<string, class-string<self>> */
    protected const DISPATCH = [
        'inputKeyboardButtonRequestPeer' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputKeyboardButtonRequestPeerData::class,
        'inputKeyboardButtonUrlAuth' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputKeyboardButtonUrlAuthData::class,
        'inputKeyboardButtonUserProfile' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputKeyboardButtonUserProfileData::class,
        'keyboardButton' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\KeyboardButtonData::class,
        'keyboardButtonBuy' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\KeyboardButtonBuyData::class,
        'keyboardButtonCallback' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\KeyboardButtonCallbackData::class,
        'keyboardButtonCopy' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\KeyboardButtonCopyData::class,
        'keyboardButtonGame' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\KeyboardButtonGameData::class,
        'keyboardButtonRequestGeoLocation' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\KeyboardButtonRequestGeoLocationData::class,
        'keyboardButtonRequestPeer' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\KeyboardButtonRequestPeerData::class,
        'keyboardButtonRequestPhone' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\KeyboardButtonRequestPhoneData::class,
        'keyboardButtonRequestPoll' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\KeyboardButtonRequestPollData::class,
        'keyboardButtonSimpleWebView' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\KeyboardButtonSimpleWebViewData::class,
        'keyboardButtonSwitchInline' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\KeyboardButtonSwitchInlineData::class,
        'keyboardButtonUrl' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\KeyboardButtonUrlData::class,
        'keyboardButtonUrlAuth' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\KeyboardButtonUrlAuthData::class,
        'keyboardButtonUserProfile' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\KeyboardButtonUserProfileData::class,
        'keyboardButtonWebView' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\KeyboardButtonWebViewData::class,
    ];

    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [];

    /** Dispatch on the constructor name carried under the '_' key of a decoded wire payload. */
    public static function hydrate(array $payload): static
    {
        $class = static::DISPATCH[$payload['_']]
            ?? throw new \InvalidArgumentException('Unknown constructor ' . $payload['_'] . ' for KeyboardButton');
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
