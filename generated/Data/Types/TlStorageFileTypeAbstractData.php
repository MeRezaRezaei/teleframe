<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/**
 * Union DTO base for TL type storage.FileType.
 *
 * @method static static hydrate(array $payload)
 */
abstract class TlStorageFileTypeAbstractData extends Data
{
    /** @var array<string, class-string<self>> */
    protected const DISPATCH = [
        'storage.fileGif' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStorageFileGifData::class,
        'storage.fileJpeg' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStorageFileJpegData::class,
        'storage.fileMov' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStorageFileMovData::class,
        'storage.fileMp3' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStorageFileMp3Data::class,
        'storage.fileMp4' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStorageFileMp4Data::class,
        'storage.filePartial' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStorageFilePartialData::class,
        'storage.filePdf' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStorageFilePdfData::class,
        'storage.filePng' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStorageFilePngData::class,
        'storage.fileUnknown' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStorageFileUnknownData::class,
        'storage.fileWebp' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStorageFileWebpData::class,
    ];

    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [];

    /** Dispatch on the constructor name carried under the '_' key of a decoded wire payload. */
    public static function hydrate(array $payload): static
    {
        $class = static::DISPATCH[$payload['_']]
            ?? throw new \InvalidArgumentException('Unknown constructor ' . $payload['_'] . ' for storage.FileType');
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
