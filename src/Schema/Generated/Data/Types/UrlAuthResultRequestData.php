<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for urlAuthResultRequest of UrlAuthResult.
 */
final class UrlAuthResultRequestData extends TlUrlAuthResultAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'requestWriteAccess' => ['flags', 0],
        'requestPhoneNumber' => ['flags', 1],
        'matchCodesFirst' => ['flags', 5],
        'isApp' => ['flags', 6],
    ];

    public function __construct(
    public int $flags,
    public ?bool $requestWriteAccess,
    public ?bool $requestPhoneNumber,
    public ?bool $matchCodesFirst,
    public ?bool $isApp,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlUserAbstractData $bot,
    public string $domain,
    public ?string $browser,
    public ?string $platform,
    public ?string $ip,
    public ?string $region,
    public ?array $matchCodes,
    public ?int $userIdHint,
    public ?string $verifiedAppName,
    ) {
    }
}
