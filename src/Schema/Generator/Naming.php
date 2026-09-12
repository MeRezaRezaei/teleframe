<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use RuntimeException;

/**
 * TL ↔ PHP/SQL name mapping for the TDLib-style domain table schema.
 *
 * Domain tables use simple, readable names (tf_users, tf_messages, etc.)
 * with no hash suffixes or repeated-word collisions.
 */
final class Naming
{
    /** SQL/Laravel-reserved or awkward words: param columns get the tl_ prefix. */
    private const RESERVED = [
        'id', 'type', 'default', 'order', 'operator', 'limit', 'offset', 'from',
        'unique', 'check', 'references', 'table', 'user', 'size', 'value',
        'index', 'primary', 'key', 'constraint', 'exclude', 'long', 'share',
        'all', 'and', 'any', 'asc', 'authorization', 'both', 'case', 'cast',
        'character', 'collate', 'column', 'current', 'desc', 'distinct', 'do',
        'end', 'except', 'false', 'for', 'grant', 'group', 'having', 'in',
        'intersect', 'into', 'leading', 'not', 'null', 'only', 'or', 'placing',
        'select', 'some', 'then', 'to', 'trailing', 'true', 'union', 'using',
        'variadic', 'when', 'window', 'with',
    ];

    /**
     * TL type name → domain table name.
     *
     * Maps core Telegram entity types to their domain table.
     * Returns null for types that don't map to a persisted table
     * (API response wrappers, primitives, MTProto internals).
     *
     * @var array<string, string> TL type name => domain name (without tf_ prefix)
     */
    private const TYPE_TO_DOMAIN = [
        // Core entities
        'User'                  => 'users',
        'Chat'                  => 'chats',
        'Channel'               => 'channels',
        'Message'               => 'messages',
        'MessageService'        => 'messages',
        'Dialog'                => 'dialogs',
        'Document'              => 'documents',
        'Photo'                 => 'photos',
        'StickerSet'            => 'sticker_sets',
        'StoryItem'             => 'stories',
        'WallPaper'             => 'wallpapers',
        'WallPaperSolid'        => 'wallpapers',
        'ChannelParticipant'    => 'channel_participants',
        // Update namespace
        'Update'                => 'updates',
    ];

    /**
     * Namespace prefix → domain table name.
     *
     * Types whose name starts with this prefix (before the dot) map to the domain.
     *
     * @var array<string, string>
     */
    private const NAMESPACE_TO_DOMAIN = [
        'updates'   => 'updates',
        'stories'   => 'stories',
    ];

    /**
     * Classification result for a TL type.
     *
     * @return array{domain: string, is_entity: bool}|null
     */
    public static function classifyType(string $tlType): ?array
    {
        // Exact name match
        if (isset(self::TYPE_TO_DOMAIN[$tlType])) {
            $domain = self::TYPE_TO_DOMAIN[$tlType];
            return ['domain' => $domain, 'is_entity' => true];
        }

        // Namespace prefix match
        $dotPos = strrpos($tlType, '.');
        if ($dotPos !== false) {
            $namespace = substr($tlType, 0, $dotPos);
            if (isset(self::NAMESPACE_TO_DOMAIN[$namespace])) {
                return ['domain' => self::NAMESPACE_TO_DOMAIN[$namespace], 'is_entity' => false];
            }
        }

        return null;
    }

    /**
     * Whether a TL type should be treated as a persisted entity
     * (gets its own row in a domain table) vs ephemeral (no table).
     */
    public static function isPersistedType(string $tlType): bool
    {
        return isset(self::TYPE_TO_DOMAIN[$tlType]);
    }

    /**
     * Get the domain name for a TL type, or null if it's ephemeral.
     */
    public static function domainForType(string $tlType): ?string
    {
        $classification = self::classifyType($tlType);
        return $classification['domain'] ?? null;
    }

    /**
     * Domain table name: 'users' → 'tf_users'.
     */
    public static function domainTable(string $domain): string
    {
        return 'tf_' . $domain;
    }

    /**
     * Constructor names that belong to a different domain than their
     * result type declares (wire reality: channel/channelForbidden are
     * `= Chat;` ctors, but the spec §5 table routes them to tf_channels).
     *
     * @var array<string, string> ctor name => domain name (without tf_ prefix)
     */
    private const CTOR_DOMAIN_OVERRIDES = [
        'channel'          => 'channels',
        'channelForbidden' => 'channels',
    ];

    /**
     * Classify a constructor to its domain: ctor-name overrides win,
     * then the result-type rules in classifyType().
     *
     * @return array{domain: string, is_entity: bool}|null
     */
    public static function classifyConstructor(string $ctorName, string $resultType): ?array
    {
        if (isset(self::CTOR_DOMAIN_OVERRIDES[$ctorName])) {
            $domain = self::CTOR_DOMAIN_OVERRIDES[$ctorName];
            return ['domain' => $domain, 'is_entity' => true];
        }

        return self::classifyType($resultType);
    }

    /**
     * Domain name → singular Studly model suffix: 'users' → 'User',
     * 'sticker_sets' → 'StickerSet', 'stories' → 'Story', ... Explicit
     * map because naive rtrim/plural-strip mangles stories/sticker_sets.
     *
     * @var array<string, string>
     */
    private const DOMAIN_MODEL_NAMES = [
        'users'                => 'User',
        'chats'                => 'Chat',
        'channels'             => 'Channel',
        'messages'             => 'Message',
        'dialogs'              => 'Dialog',
        'updates'              => 'Update',
        'documents'            => 'Document',
        'photos'               => 'Photo',
        'sticker_sets'         => 'StickerSet',
        'stories'              => 'Story',
        'wallpapers'           => 'Wallpaper',
        'channel_participants' => 'ChannelParticipant',
    ];

    /**
     * Model class name for a domain: 'users' → 'TlUser', 'messages' → 'TlMessage'.
     */
    public static function domainModel(string $domain): string
    {
        if (isset(self::DOMAIN_MODEL_NAMES[$domain])) {
            return 'Tl' . self::DOMAIN_MODEL_NAMES[$domain];
        }

        // Fallback for unknown domains: strip one trailing 's', StudlyCase.
        $singular = str_ends_with($domain, 's') ? substr($domain, 0, -1) : $domain;
        $studly = implode('', array_map(
            static fn (string $s): string => ucfirst($s),
            explode('_', $singular),
        ));
        return 'Tl' . $studly;
    }

    /**
     * Full FQCN for a domain model class.
     */
    public static function domainModelFqcn(string $domain): string
    {
        return 'MeRezaRezaei\\Teleframe\\Schema\\Generated\\Models\\' . self::domainModel($domain);
    }

    /**
     * Legacy alias: model name for a TL type (used by PeerResolution etc.).
     */
    public static function model(string $tlType): string
    {
        return 'Tl' . self::pascal($tlType);
    }

    // ── Per-constructor naming (legacy, used by old generators) ──────

    /**
     * Per-constructor table name: ('User', 'user') → 'tl_user_user'.
     * Strips the type's namespace prefix from the ctor name.
     */
    public static function constructorTable(string $tlType, string $ctorName): string
    {
        $typeSnake = self::snake($tlType);
        $ctorName = self::stripTypePrefix($tlType, $ctorName);
        $ctorSnake = self::snake($ctorName);
        return 'tl_' . $typeSnake . '_' . $ctorSnake;
    }

    /**
     * Child table for a vector column: ('tl_user_user', 'statuses') → 'tl_user_user__statuses'.
     */
    public static function childTable(string $parentTable, string $column): string
    {
        return $parentTable . '__' . $column;
    }

    /**
     * Per-constructor model class: ('User', 'user') → 'TlUserUser'.
     */
    public static function ctorModel(string $tlType, string $ctorName): string
    {
        $ctorName = self::stripTypePrefix($tlType, $ctorName);
        return 'Tl' . self::pascal($tlType) . self::pascal($ctorName);
    }

    /**
     * Per-constructor DTO class: 'user' → 'UserData', 'messages.sendMessage' → 'TlMessagesSendMessageData'.
     */
    public static function dataClass(string $ctorName): string
    {
        $pascal = self::pascal($ctorName);
        return str_contains($ctorName, '.') ? 'Tl' . $pascal . 'Data' : $pascal . 'Data';
    }

    /**
     * Abstract data class for a TL type: 'User' → 'TlUserAbstractData'.
     */
    public static function abstractDataClass(string $tlType): string
    {
        return 'Tl' . self::pascal($tlType) . 'AbstractData';
    }

    /**
     * Per-constructor DTO class name: ('User', 'user') → 'TlUserUserData'.
     */
    public static function ctorDto(string $tlType, string $ctorName): string
    {
        $ctorName = self::stripTypePrefix($tlType, $ctorName);
        return 'Tl' . self::pascal($tlType) . self::pascal($ctorName) . 'Data';
    }

    /**
     * Strip the type's namespace prefix from the ctor name.
     * e.g., ('messages.Dialogs', 'messages.dialogsSlice') → 'dialogsSlice'.
     */
    private static function stripTypePrefix(string $tlType, string $ctorName): string
    {
        $dotPos = strrpos($tlType, '.');
        if ($dotPos !== false) {
            $prefix = substr($tlType, 0, $dotPos + 1);
            if (str_starts_with($ctorName, $prefix)) {
                return substr($ctorName, strlen($prefix));
            }
        }
        return $ctorName;
    }

    /**
     * Column name for a TL param: escapes reserved words with tl_ prefix.
     */
    public static function column(string $param): string
    {
        $snake = self::snake($param);
        return in_array($snake, self::RESERVED, true) ? 'tl_' . $snake : $snake;
    }

    /**
     * Postgres column type for a scalar/nat/true/ref param.
     */
    public static function dbType(TlParam $p, bool $precision = false): string
    {
        if ($p->kind() === 'vector' || $p->kind() === 'generic') {
            throw new \InvalidArgumentException('param "' . $p->name . '" is a vector/generic — use tl_data JSONB, not a column');
        }
        return match ($p->baseType()) {
            'int' => 'integer',
            'long' => 'bigint',
            'int128' => $precision ? 'numeric(39,0)' : 'numeric',
            'int256' => $precision ? 'numeric(78,0)' : 'numeric',
            'double' => 'double',
            'string' => 'text',
            'bytes' => 'binary',
            '#' => 'bigint',
            'true' => 'boolean',
            default => 'bigint',
        };
    }

    public static function cast(TlParam $p): string
    {
        return match ($p->baseType()) {
            'int' => 'int',
            'long' => 'int',
            'int128', 'int256' => 'string',
            'double' => 'float',
            'string' => 'string',
            'bytes' => 'string',
            '#' => 'int',
            'true' => 'bool',
            default => 'int',
        };
    }

    /**
     * @param list<string> $names
     * @throws RuntimeException on duplicates
     */
    public static function assertUnique(array $names, string $kind): void
    {
        $seen = [];
        foreach ($names as $n) {
            if (isset($seen[$n])) {
                throw new RuntimeException("naming collision ({$kind}): {$n} generated twice");
            }
            $seen[$n] = true;
        }
    }

    /** dotted CamelCase → snake_case: 'messages.dialogsSlice' → 'messages_dialogs_slice' */
    public static function snake(string $dotted): string
    {
        $parts = explode('.', $dotted);
        $snaked = array_map(
            static fn (string $s): string => strtolower(self::camelToSnake($s)),
            $parts,
        );
        return implode('_', $snaked);
    }

    /** Regex-free camel→snake: '_' before every uppercase letter that is not the first character. */
    private static function camelToSnake(string $s): string
    {
        $out = '';
        foreach (str_split($s) as $i => $ch) {
            if ($i > 0 && $ch >= 'A' && $ch <= 'Z') {
                $out .= '_';
            }
            $out .= $ch;
        }
        return $out;
    }

    /** dotted/snake name → PascalCase path */
    public static function pascal(string $dotted): string
    {
        $dotted = str_replace('_', '.', $dotted);
        $parts = explode('.', $dotted);
        return implode('', array_map(
            static fn (string $s): string => ucfirst($s),
            $parts,
        ));
    }
}
