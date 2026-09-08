<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/**
 * Union DTO base for TL type PageBlock.
 *
 * @method static static hydrate(array $payload)
 */
abstract class TlPageBlockAbstractData extends Data
{
    /** @var array<string, class-string<self>> */
    protected const DISPATCH = [
        'inputPageBlockMap' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\InputPageBlockMapData::class,
        'pageBlockAnchor' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockAnchorData::class,
        'pageBlockAudio' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockAudioData::class,
        'pageBlockAuthorDate' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockAuthorDateData::class,
        'pageBlockBlockquote' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockBlockquoteData::class,
        'pageBlockBlockquoteBlocks' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockBlockquoteBlocksData::class,
        'pageBlockChannel' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockChannelData::class,
        'pageBlockCollage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockCollageData::class,
        'pageBlockCover' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockCoverData::class,
        'pageBlockDetails' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockDetailsData::class,
        'pageBlockDivider' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockDividerData::class,
        'pageBlockEmbed' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockEmbedData::class,
        'pageBlockEmbedPost' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockEmbedPostData::class,
        'pageBlockFooter' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockFooterData::class,
        'pageBlockHeader' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockHeaderData::class,
        'pageBlockHeading1' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockHeading1Data::class,
        'pageBlockHeading2' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockHeading2Data::class,
        'pageBlockHeading3' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockHeading3Data::class,
        'pageBlockHeading4' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockHeading4Data::class,
        'pageBlockHeading5' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockHeading5Data::class,
        'pageBlockHeading6' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockHeading6Data::class,
        'pageBlockKicker' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockKickerData::class,
        'pageBlockList' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockListData::class,
        'pageBlockMap' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockMapData::class,
        'pageBlockMath' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockMathData::class,
        'pageBlockOrderedList' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockOrderedListData::class,
        'pageBlockParagraph' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockParagraphData::class,
        'pageBlockPhoto' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockPhotoData::class,
        'pageBlockPreformatted' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockPreformattedData::class,
        'pageBlockPullquote' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockPullquoteData::class,
        'pageBlockRelatedArticles' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockRelatedArticlesData::class,
        'pageBlockSlideshow' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockSlideshowData::class,
        'pageBlockSubheader' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockSubheaderData::class,
        'pageBlockSubtitle' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockSubtitleData::class,
        'pageBlockTable' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockTableData::class,
        'pageBlockThinking' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockThinkingData::class,
        'pageBlockTitle' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockTitleData::class,
        'pageBlockUnsupported' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockUnsupportedData::class,
        'pageBlockVideo' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\PageBlockVideoData::class,
    ];

    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [];

    /** Dispatch on the constructor name carried under the '_' key of a decoded wire payload. */
    public static function hydrate(array $payload): static
    {
        $class = static::DISPATCH[$payload['_']]
            ?? throw new \InvalidArgumentException('Unknown constructor ' . $payload['_'] . ' for PageBlock');
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
