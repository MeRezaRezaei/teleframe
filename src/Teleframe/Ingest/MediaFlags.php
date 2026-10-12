<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

/**
 * TDLib-inspired index_mask pattern: a 30-bit bitmask where each bit
 * represents "has media type X". Stored as an integer column on tf_messages
 * with partial btree indexes per flag for fast filtering.
 *
 * Bit assignments:
 *   0 (0x001) photo
 *   1 (0x002) video
 *   2 (0x004) document
 *   3 (0x008) sticker
 *   4 (0x010) voice
 *   5 (0x020) video_note
 *   6 (0x040) location
 *   7 (0x080) contact
 *   8 (0x100) poll
 *   9 (0x200) inline_result
 *  10 (0x400) invoice
 */
final class MediaFlags
{
    private const FLAGS = [
        'photo'         => 1 << 0,
        'video'         => 1 << 1,
        'document'      => 1 << 2,
        'sticker'       => 1 << 3,
        'voice'         => 1 << 4,
        'video_note'    => 1 << 5,
        'location'      => 1 << 6,
        'contact'       => 1 << 7,
        'poll'          => 1 << 8,
        'inline_result' => 1 << 9,
        'invoice'       => 1 << 10,
    ];

    /**
     * Extract media flags from a raw TL message payload.
     */
    public static function fromPayload(array $payload): int
    {
        $media = $payload['media'] ?? null;
        if (!is_array($media) || !isset($media['_'])) {
            return 0;
        }

        $ctor = (string) $media['_'];

        return match ($ctor) {
            'messageMediaPhoto' => self::FLAGS['photo'],
            'messageMediaGeo',
            'messageMediaGeoLive' => self::FLAGS['location'],
            'messageMediaContact' => self::FLAGS['contact'],
            'messageMediaPoll' => self::FLAGS['poll'],
            'messageMediaInvoice' => self::FLAGS['invoice'],
            'messageMediaWebPage' => isset($media['webpage']['document'])
                ? self::FLAGS['document']
                : (isset($media['webpage']['photo']) ? self::FLAGS['photo'] : 0),
            'messageMediaDocument' => self::documentFlags($media),
            'messageMediaAudio' => isset($media['audio']['voice']) && $media['audio']['voice']
                ? self::FLAGS['voice']
                : self::FLAGS['document'],
            'messageMediaVideo' => self::FLAGS['video'],
            'messageMediaDice' => 0,
            default => 0,
        };
    }

    /**
     * Check if a specific flag is set.
     */
    public static function has(int $flags, string $flag): bool
    {
        $bit = self::FLAGS[$flag] ?? 0;

        return $bit !== 0 && ($flags & $bit) !== 0;
    }

    /**
     * All defined flag names.
     *
     * @return array<string, int>
     */
    public static function all(): array
    {
        return self::FLAGS;
    }

    private static function documentFlags(array $media): int
    {
        $flags = self::FLAGS['document'];
        $doc = $media['document'] ?? null;

        if (!is_array($doc) || !isset($doc['attributes'])) {
            return $flags;
        }

        foreach ($doc['attributes'] as $attr) {
            if (!is_array($attr) || !isset($attr['_'])) {
                continue;
            }
            $flags |= match ($attr['_']) {
                'documentAttributeSticker' => self::FLAGS['sticker'],
                'documentAttributeVideo' => self::FLAGS['video'],
                'documentAttributeAudio' => isset($attr['voice']) && $attr['voice']
                    ? self::FLAGS['voice']
                    : 0,
                default => 0,
            };
        }

        return $flags;
    }
}
