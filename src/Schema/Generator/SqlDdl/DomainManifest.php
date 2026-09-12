<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator\SqlDdl;

/**
 * Domain manifest for the SQL extractor: which TL fields promote to real
 * columns per domain table, the key strategy, and the index set.
 *
 * Column spec shapes:
 *   ['col' => string, 'field' => string]        promoted primitive — SQL type
 *                                                is DERIVED from the .tl field
 *                                                (TypeMapper), never hand-stated
 *   ['col' => string, 'sql' => string]          derived/contextual column —
 *                                                explicit type, filled by ingest
 *
 * Field-promoted columns are validated at emit time: the field must exist in
 * at least one classified ctor of the domain, and its TL type must be
 * promotable. Violations throw — the manifest can't drift from the .tl.
 *
 * Key strategies (plan §4):
 *   global   PK (account_id, id)           — users, chats, channels, documents,
 *                                            photos, sticker_sets, wallpapers
 *   natural  PK (natural scope)            — messages, dialogs, stories,
 *                                            channel_participants (no surrogate:
 *                                            one index write per upsert)
 *   serial   BIGSERIAL append-only PK      — updates log
 */
final class DomainManifest
{
    /**
     * @return array<string, array{
     *   key: 'global'|'natural'|'serial',
     *   pk?: list<string>,
     *   columns: list<array{col:string, field?:string, sql?:string}>,
     *   partial?: list<string>,
     *   indexes?: list<list<string>>,
     *   fillfactor: int
     * }> table (tf_<domain>) => spec
     */
    public static function tables(): array
    {
        return [
            'tf_users' => [
                'key' => 'global',
                'pk' => ['account_id', 'id'],
                'columns' => [
                    ['col' => 'id', 'field' => 'id'],
                    ['col' => 'access_hash', 'field' => 'access_hash'],
                    ['col' => 'first_name', 'field' => 'first_name'],
                    ['col' => 'last_name', 'field' => 'last_name'],
                    ['col' => 'username', 'field' => 'username'],
                    ['col' => 'phone', 'field' => 'phone'],
                    ['col' => 'is_bot', 'field' => 'bot'],
                    ['col' => 'is_self', 'field' => 'self'],
                    ['col' => 'is_contact', 'field' => 'contact'],
                    ['col' => 'is_premium', 'field' => 'premium'],
                    ['col' => 'is_deleted', 'field' => 'deleted'],
                    ['col' => 'status_type', 'sql' => 'VARCHAR(32)'],
                    ['col' => 'constructor_id', 'sql' => 'INTEGER NOT NULL'],
                    ['col' => 'account_id', 'sql' => 'BIGINT NOT NULL'],
                    ['col' => 'tl_data', 'sql' => 'JSONB NOT NULL'],
                ],
                'partial' => ['username', 'phone'],
                'indexes' => [['account_id']],
                'fillfactor' => 90,
            ],

            'tf_chats' => [
                'key' => 'global',
                'pk' => ['account_id', 'id'],
                'columns' => [
                    ['col' => 'id', 'field' => 'id'],
                    ['col' => 'title', 'field' => 'title'],
                    ['col' => 'participants_count', 'field' => 'participants_count'],
                    ['col' => 'date', 'field' => 'date'],
                    ['col' => 'version', 'field' => 'version'],
                    ['col' => 'is_deactivated', 'field' => 'deactivated'],
                    ['col' => 'is_left', 'field' => 'left'],
                    ['col' => 'constructor_id', 'sql' => 'INTEGER NOT NULL'],
                    ['col' => 'account_id', 'sql' => 'BIGINT NOT NULL'],
                    ['col' => 'tl_data', 'sql' => 'JSONB NOT NULL'],
                ],
                'indexes' => [['account_id']],
                'fillfactor' => 90,
            ],

            'tf_channels' => [
                'key' => 'global',
                'pk' => ['account_id', 'id'],
                'columns' => [
                    ['col' => 'id', 'field' => 'id'],
                    ['col' => 'access_hash', 'field' => 'access_hash'],
                    ['col' => 'title', 'field' => 'title'],
                    ['col' => 'username', 'field' => 'username'],
                    ['col' => 'date', 'field' => 'date'],
                    ['col' => 'participants_count', 'field' => 'participants_count'],
                    ['col' => 'is_broadcast', 'field' => 'broadcast'],
                    ['col' => 'is_megagroup', 'field' => 'megagroup'],
                    ['col' => 'is_verified', 'field' => 'verified'],
                    ['col' => 'is_restricted', 'field' => 'restricted'],
                    ['col' => 'is_left', 'field' => 'left'],
                    ['col' => 'is_forum', 'field' => 'forum'],
                    ['col' => 'restriction_reason', 'sql' => 'VARCHAR(255)'],
                    ['col' => 'constructor_id', 'sql' => 'INTEGER NOT NULL'],
                    ['col' => 'account_id', 'sql' => 'BIGINT NOT NULL'],
                    ['col' => 'tl_data', 'sql' => 'JSONB NOT NULL'],
                ],
                'partial' => ['username'],
                'indexes' => [['account_id']],
                'fillfactor' => 90,
            ],

            'tf_messages' => [
                'key' => 'natural',
                'pk' => ['peer_id', 'message_id', 'account_id'],
                'columns' => [
                    ['col' => 'peer_id', 'field' => 'peer_id'],
                    ['col' => 'message_id', 'field' => 'id'],
                    ['col' => 'from_id', 'field' => 'from_id'],
                    ['col' => 'date', 'field' => 'date'],
                    ['col' => 'is_out', 'field' => 'out'],
                    ['col' => 'is_mentioned', 'field' => 'mentioned'],
                    ['col' => 'is_silent', 'field' => 'silent'],
                    ['col' => 'is_pinned', 'field' => 'pinned'],
                    ['col' => 'message', 'field' => 'message'],
                    ['col' => 'media_type', 'sql' => 'VARCHAR(32)'],
                    ['col' => 'reply_to_msg_id', 'sql' => 'INTEGER'],
                    ['col' => 'constructor_id', 'sql' => 'INTEGER NOT NULL'],
                    ['col' => 'account_id', 'sql' => 'BIGINT NOT NULL'],
                    ['col' => 'tl_data', 'sql' => 'JSONB NOT NULL'],
                ],
                'partial' => ['from_id'],
                'indexes' => [['peer_id', 'date']],
                'fillfactor' => 90,
            ],

            'tf_dialogs' => [
                'key' => 'natural',
                'pk' => ['peer_id', 'account_id'],
                'columns' => [
                    ['col' => 'peer_id', 'field' => 'peer'],
                    ['col' => 'peer_type', 'sql' => 'VARCHAR(32)'],
                    ['col' => 'top_message_id', 'field' => 'top_message'],
                    ['col' => 'unread_count', 'field' => 'unread_count'],
                    ['col' => 'unread_mentions', 'field' => 'unread_mentions_count'],
                    ['col' => 'is_pinned', 'field' => 'pinned'],
                    ['col' => 'pts', 'field' => 'pts'],
                    ['col' => 'constructor_id', 'sql' => 'INTEGER NOT NULL'],
                    ['col' => 'account_id', 'sql' => 'BIGINT NOT NULL'],
                    ['col' => 'tl_data', 'sql' => 'JSONB NOT NULL'],
                ],
                'indexes' => [['account_id']],
                'fillfactor' => 90,
            ],

            'tf_updates' => [
                'key' => 'serial',
                'pk' => ['id'],
                'columns' => [
                    ['col' => 'id', 'sql' => 'BIGSERIAL PRIMARY KEY'],
                    ['col' => 'pts', 'field' => 'pts'],
                    ['col' => 'pts_count', 'field' => 'pts_count'],
                    ['col' => 'date', 'field' => 'date'],
                    ['col' => 'channel_id', 'field' => 'channel_id'],
                    ['col' => 'user_id', 'field' => 'user_id'],
                    ['col' => 'peer_id', 'sql' => 'BIGINT'],
                    ['col' => 'message_id', 'sql' => 'INTEGER'],
                    ['col' => 'constructor_id', 'sql' => 'INTEGER NOT NULL'],
                    ['col' => 'account_id', 'sql' => 'BIGINT NOT NULL'],
                    ['col' => 'tl_data', 'sql' => 'JSONB NOT NULL'],
                ],
                'partial' => ['peer_id'],
                'indexes' => [['account_id']],
                'fillfactor' => 100,
            ],

            'tf_documents' => [
                'key' => 'global',
                'pk' => ['account_id', 'id'],
                'columns' => [
                    ['col' => 'id', 'field' => 'id'],
                    ['col' => 'access_hash', 'field' => 'access_hash'],
                    ['col' => 'file_reference', 'field' => 'file_reference'],
                    ['col' => 'date', 'field' => 'date'],
                    ['col' => 'mime_type', 'field' => 'mime_type'],
                    ['col' => 'size', 'field' => 'size'],
                    ['col' => 'dc_id', 'field' => 'dc_id'],
                    ['col' => 'constructor_id', 'sql' => 'INTEGER NOT NULL'],
                    ['col' => 'account_id', 'sql' => 'BIGINT NOT NULL'],
                    ['col' => 'tl_data', 'sql' => 'JSONB NOT NULL'],
                ],
                'indexes' => [['account_id']],
                'fillfactor' => 90,
            ],

            'tf_photos' => [
                'key' => 'global',
                'pk' => ['account_id', 'id'],
                'columns' => [
                    ['col' => 'id', 'field' => 'id'],
                    ['col' => 'access_hash', 'field' => 'access_hash'],
                    ['col' => 'file_reference', 'field' => 'file_reference'],
                    ['col' => 'date', 'field' => 'date'],
                    ['col' => 'dc_id', 'field' => 'dc_id'],
                    ['col' => 'has_stickers', 'field' => 'has_stickers'],
                    ['col' => 'constructor_id', 'sql' => 'INTEGER NOT NULL'],
                    ['col' => 'account_id', 'sql' => 'BIGINT NOT NULL'],
                    ['col' => 'tl_data', 'sql' => 'JSONB NOT NULL'],
                ],
                'indexes' => [['account_id']],
                'fillfactor' => 90,
            ],

            'tf_sticker_sets' => [
                'key' => 'global',
                'pk' => ['account_id', 'id'],
                'columns' => [
                    ['col' => 'id', 'field' => 'id'],
                    ['col' => 'access_hash', 'field' => 'access_hash'],
                    ['col' => 'title', 'field' => 'title'],
                    ['col' => 'short_name', 'field' => 'short_name'],
                    ['col' => 'count', 'field' => 'count'],
                    ['col' => 'constructor_id', 'sql' => 'INTEGER NOT NULL'],
                    ['col' => 'account_id', 'sql' => 'BIGINT NOT NULL'],
                    ['col' => 'tl_data', 'sql' => 'JSONB NOT NULL'],
                ],
                'indexes' => [['account_id']],
                'fillfactor' => 90,
            ],

            'tf_stories' => [
                'key' => 'natural',
                'pk' => ['peer_id', 'story_id', 'account_id'],
                'columns' => [
                    ['col' => 'peer_id', 'sql' => 'BIGINT'],
                    ['col' => 'story_id', 'field' => 'id'],
                    ['col' => 'date', 'field' => 'date'],
                    ['col' => 'expire_date', 'field' => 'expire_date'],
                    ['col' => 'caption', 'field' => 'caption'],
                    ['col' => 'constructor_id', 'sql' => 'INTEGER NOT NULL'],
                    ['col' => 'account_id', 'sql' => 'BIGINT NOT NULL'],
                    ['col' => 'tl_data', 'sql' => 'JSONB NOT NULL'],
                ],
                'indexes' => [['account_id']],
                'fillfactor' => 90,
            ],

            'tf_wallpapers' => [
                'key' => 'global',
                'pk' => ['account_id', 'id'],
                'columns' => [
                    ['col' => 'id', 'field' => 'id'],
                    ['col' => 'access_hash', 'field' => 'access_hash'],
                    ['col' => 'slug', 'field' => 'slug'],
                    ['col' => 'is_pattern', 'field' => 'pattern'],
                    ['col' => 'is_dark', 'field' => 'dark'],
                    ['col' => 'constructor_id', 'sql' => 'INTEGER NOT NULL'],
                    ['col' => 'account_id', 'sql' => 'BIGINT NOT NULL'],
                    ['col' => 'tl_data', 'sql' => 'JSONB NOT NULL'],
                    ['col' => 'document_id', 'sql' => 'BIGINT'],
                ],
                'indexes' => [['account_id']],
                'fillfactor' => 90,
            ],

            'tf_channel_participants' => [
                'key' => 'natural',
                'pk' => ['channel_id', 'user_id', 'account_id'],
                'columns' => [
                    ['col' => 'channel_id', 'sql' => 'BIGINT'],
                    ['col' => 'user_id', 'sql' => 'BIGINT'],
                    ['col' => 'date', 'field' => 'date'],
                    ['col' => 'constructor_id', 'sql' => 'INTEGER NOT NULL'],
                    ['col' => 'account_id', 'sql' => 'BIGINT NOT NULL'],
                    ['col' => 'tl_data', 'sql' => 'JSONB NOT NULL'],
                ],
                'indexes' => [['account_id']],
                'fillfactor' => 90,
            ],
        ];
    }

    /** @return list<string> all table names, in manifest order */
    public static function tableNames(): array
    {
        return array_keys(self::tables());
    }
}