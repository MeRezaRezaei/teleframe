<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Concerns;

/**
 * The canned P2 wire payloads (NestedIngest plan Task 3): the nested
 * updateNewMessage#1f2b0afd tree plus its difference-stream sidecars
 * (channel#1c32b11c, user#31774388). One source of truth shared by the
 * sqlite ingest suite and the Postgres mirror track (tests/Pg) so both
 * runtimes ingest the exact same truth.
 */
trait HasNestedUpdateFixtures
{
    protected const FIXTURE_ACCOUNT = 7;

    protected const FIXTURE_CHANNEL_ID = 1737473577;

    protected const FIXTURE_USER_ID = 501558149;

    /**
     * Difference-stream sidecar: channel#1c32b11c (v227 full-ish field set).
     * photo is a REQUIRED ref — chatPhotoEmpty#37c1011c rides along (a
     * paramless constructor: pure aggregation node).
     *
     * @return array<string, mixed>
     */
    protected static function channelPayload(): array
    {
        return [
            '_' => 'channel',
            // verified | megagroup | access_hash
            'flags' => (1 << 7) | (1 << 8) | (1 << 13),
            'verified' => true, // set-flag bools arrive as explicit keys (teleframe truth)
            'megagroup' => true,
            'id' => self::FIXTURE_CHANNEL_ID,
            'access_hash' => -7779317524312221622,
            'title' => 'Teleframe Café',
            'photo' => ['_' => 'chatPhotoEmpty'],
            'date' => 1712345678,
        ];
    }

    /**
     * Difference-stream sidecar: user#31774388 (same shape as the Task-1
     * fixture, flat fields only).
     *
     * @return array<string, mixed>
     */
    protected static function userPayload(): array
    {
        return [
            '_' => 'user',
            'flags' => (1 << 0) | (1 << 1) | (1 << 2) | (1 << 3),
            'id' => self::FIXTURE_USER_ID,
            'access_hash' => -5988024083302710253,
            'first_name' => 'Reza',
            'last_name' => 'Rezaei',
            'username' => 'RezaRezaei',
        ];
    }

    /**
     * The root wire payload: updateNewMessage#1f2b0afd → message#7600b9d3
     * with from_id peerUser, peer_id peerChannel, media messageMediaEmpty
     * (flags.9), entities (flags.7) as a vector of three constructors.
     *
     * @return array<string, mixed>
     */
    protected static function updateNewMessagePayload(): array
    {
        return [
            '_' => 'updateNewMessage',
            'message' => [
                '_' => 'message',
                // out | entities | from_id | media
                'flags' => (1 << 1) | (1 << 7) | (1 << 8) | (1 << 9),
                'out' => true, // set-flag bools arrive as explicit keys (teleframe truth)
                'id' => 1186,
                'from_id' => ['_' => 'peerUser', 'user_id' => self::FIXTURE_USER_ID],
                'peer_id' => ['_' => 'peerChannel', 'channel_id' => self::FIXTURE_CHANNEL_ID],
                'date' => 1724852400,
                'message' => 'Check https://t.me/teleframe from @Reza',
                'media' => ['_' => 'messageMediaEmpty'],
                'entities' => [
                    ['_' => 'messageEntityBold', 'offset' => 0, 'length' => 5],
                    ['_' => 'messageEntityUrl', 'offset' => 6, 'length' => 21],
                    ['_' => 'messageEntityMentionName', 'offset' => 33, 'length' => 5, 'user_id' => self::FIXTURE_USER_ID],
                ],
                'flags2' => 0,
            ],
            'pts' => 1349,
            'pts_count' => 1,
        ];
    }
}
