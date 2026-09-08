<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Message;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Core\Entities\EntityParser as CoreEntityParser;

/**
 * Zero-regex adapter over the engine's EXISTING entity machinery
 * (`src/Core/Entities/EntityParser`). The Message layer does NOT ship a new
 * markup parser (Q13): templates are plain PHP that return a structured
 * array, and this class converts the layer's conventional input — plain
 * text plus optionally declared `{offset, length, type}` spans — into the
 * engine's entity array shape `{'_' => 'messageEntity*', offset, length, …}`.
 *
 * Decision (documented for the 5d report):
 *   - REUSED: the entity shape (`'_'`, `offset`, `length` in UTF-16 code
 *     units) and the UTF-16 length primitive (`CoreEntityParser::
 *     getUtf16Length()`) from `src/Core/Entities/EntityParser.php`.
 *   - DELEGATED: `html()` / `markdown()` forward to the existing DOM /
 *     deterministic-token-stream parsers — no duplicate parser, no regex.
 *   - NEW: `spans()` — a strict, zero-regex normalizer for the conventional
 *     span list. Bot API type aliases (`bold`, `text_link`, …) map to the
 *     engine's `messageEntity*` constructor names; offsets/lengths are
 *     validated against the UTF-16 length of the text, and spans are checked
 *     for overlap, so a template can never emit a plan Telegram rejects.
 *
 * Every span is `{offset: int, length: int, type: string}` plus zero or more
 * type-specific extras (`url`, `document_id`, `user_id`, `language`). Unknown
 * types or extra keys throw `InvalidArgumentException` — fail fast at
 * compile time, not send time.
 */
final class EntityParser
{
    /** Bot API `MessageEntity.type` → engine `messageEntity*` constructor. */
    public const TYPE_ALIASES = [
        'bold' => 'messageEntityBold',
        'italic' => 'messageEntityItalic',
        'underline' => 'messageEntityUnderline',
        'strikethrough' => 'messageEntityStrike',
        'strike' => 'messageEntityStrike',
        'spoiler' => 'messageEntitySpoiler',
        'code' => 'messageEntityCode',
        'pre' => 'messageEntityPre',
        'blockquote' => 'messageEntityBlockquote',
        'expandable_blockquote' => 'messageEntityBlockquote',
        'text_link' => 'messageEntityTextUrl',
        'text_mention' => 'messageEntityMentionName',
        'custom_emoji' => 'messageEntityCustomEmoji',
        'mention' => 'messageEntityMention',
        'hashtag' => 'messageEntityHashtag',
        'cashtag' => 'messageEntityCashtag',
        'bot_command' => 'messageEntityBotCommand',
        'url' => 'messageEntityUrl',
        'email' => 'messageEntityEmail',
        'phone' => 'messageEntityPhone',
        'bank_card' => 'messageEntityBankCard',
    ];

    /**
     * Extra keys each entity constructor accepts beyond `_`/`offset`/`length`.
     * Shared with `MessagePlanValidator` so the conventional input and the
     * registry typecheck can never disagree.
     *
     * @var array<string, list<string>>
     */
    public const ENTITY_EXTRA_KEYS = [
        'messageEntityTextUrl' => ['url'],
        'messageEntityCustomEmoji' => ['document_id'],
        'messageEntityMentionName' => ['user_id'],
        'messageEntityPre' => ['language'],
    ];

    /**
     * Convert the conventional span input into the engine's entity shape.
     *
     * @param string                       $text  plain text the entities point into
     * @param list<array<string, mixed>>   $spans declared `{offset,length,type}` spans
     * @return array{text: string, entities: list<array<string, mixed>>}
     *
     * @throws InvalidArgumentException on unknown type, unknown extra key,
     *         non-numeric or out-of-bounds offset/length, or overlapping spans
     */
    public static function spans(string $text, array $spans): array
    {
        $entities = [];

        foreach ($spans as $span) {
            $entities[] = self::normalizeSpan($text, $span);
        }

        usort($entities, static fn (array $a, array $b): int => $a['offset'] <=> $b['offset']);

        self::assertNoOverlap($entities);

        return ['text' => $text, 'entities' => $entities];
    }

    /**
     * Resolve a Bot API alias — or an engine-native name — to the engine's
     * `messageEntity*` constructor name.
     *
     * @throws InvalidArgumentException when the type is unknown
     */
    public static function entityType(string $type): string
    {
        if (str_starts_with($type, 'messageEntity')) {
            return $type;
        }

        $resolved = self::TYPE_ALIASES[$type] ?? null;
        if ($resolved === null) {
            $known = implode(', ', array_keys(self::TYPE_ALIASES));
            throw new InvalidArgumentException("Unknown entity type [{$type}]. Known aliases: {$known}.");
        }

        return $resolved;
    }

    /**
     * Delegate an HTML template body to the existing DOM parser (reused,
     * not duplicated).
     *
     * @return array{text: string, entities: list<array<string, mixed>>}
     */
    public static function html(string $html): array
    {
        return CoreEntityParser::htmlToEntities($html);
    }

    /**
     * Delegate a MarkdownV2 template body to the existing zero-regex tokenizer
     * (reused, not duplicated).
     *
     * @return array{text: string, entities: list<array<string, mixed>>}
     */
    public static function markdown(string $markdown): array
    {
        return CoreEntityParser::markdownToEntities($markdown);
    }

    /**
     * @param array<string, mixed> $span
     * @return array<string, mixed>
     */
    private static function normalizeSpan(string $text, array $span): array
    {
        foreach (['offset', 'length', 'type'] as $required) {
            if (! array_key_exists($required, $span)) {
                throw new InvalidArgumentException("Every span needs a [{$required}] key.");
            }
        }

        $type = $span['type'];
        if (! is_string($type)) {
            throw new InvalidArgumentException('Span type must be a string.');
        }

        $resolved = self::entityType($type);

        $offset = $span['offset'];
        $length = $span['length'];
        if (! is_int($offset) || $offset < 0) {
            throw new InvalidArgumentException('Span offset must be a non-negative int.');
        }
        if (! is_int($length) || $length < 1) {
            throw new InvalidArgumentException('Span length must be a positive int.');
        }

        $total = CoreEntityParser::getUtf16Length($text);
        if ($offset + $length > $total) {
            throw new InvalidArgumentException(
                "Span [{$resolved}] @{$offset}+{$length} exceeds text length ({$total} UTF-16 units)."
            );
        }

        $entity = [
            '_' => $resolved,
            'offset' => $offset,
            'length' => $length,
        ];

        $allowedExtras = self::ENTITY_EXTRA_KEYS[$resolved] ?? [];

        foreach ($span as $key => $value) {
            if ($key === 'offset' || $key === 'length' || $key === 'type') {
                continue;
            }
            if (! in_array($key, $allowedExtras, true)) {
                throw new InvalidArgumentException("Unknown span key [{$key}] for entity type [{$resolved}].");
            }
            $entity[$key] = $value;
        }

        self::assertExtraTypes($entity);

        return $entity;
    }

    /** @param array<string, mixed> $entity */
    private static function assertExtraTypes(array $entity): void
    {
        if (array_key_exists('url', $entity) && ! is_string($entity['url'])) {
            throw new InvalidArgumentException('Entity url must be a string.');
        }
        if (array_key_exists('document_id', $entity) && ! is_int($entity['document_id'])) {
            throw new InvalidArgumentException('Entity document_id must be an int.');
        }
        if (array_key_exists('user_id', $entity) && ! is_int($entity['user_id'])) {
            throw new InvalidArgumentException('Entity user_id must be an int.');
        }
        if (array_key_exists('language', $entity) && ! is_string($entity['language'])) {
            throw new InvalidArgumentException('Entity language must be a string.');
        }
    }

    /**
     * Telegram entities must not overlap; enforce the sorted span list.
     *
     * @param list<array<string, mixed>> $entities sorted by offset
     */
    private static function assertNoOverlap(array $entities): void
    {
        while (count($entities) > 1) {
            $previous = array_shift($entities);
            $current = $entities[0];

            $previousEnd = (int) $previous['offset'] + (int) $previous['length'];
            if ($previousEnd > (int) $current['offset']) {
                throw new InvalidArgumentException('Spans must not overlap.');
            }
        }
    }
}