<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Message;

use MeRezaRezaei\Teleframe\Core\Schema\MethodRegistry;
use MeRezaRezaei\Teleframe\Message\Exceptions\InvalidMessagePlanException;

/**
 * Q14 send-time typecheck vs `MethodRegistry`: a compiled plan's
 * `text` / `entities` / `reply_markup` keys must conform to what the send
 * method (`messages.sendMessage` by default) accepts. Reads the method's
 * declared `params` straight from the registry — no hard-coded whitelist.
 *
 * Plan key → method param mapping (the plan is the handler-facing shape, the
 * registry holds the wire shape):
 *   - `text`        → `message`    (the string body)
 *   - `entities`    → `entities`   (declared `Vector<MessageEntity>`)
 *   - `reply_markup`→ `reply_markup` (declared `ReplyMarkup`)
 *
 * `assertPlanValid()` validates AND normalizes: it returns the canonical plan
 * (`entities` defaulted to `[]`, `reply_markup` only when present) so a
 * template can `return ['text' => '…']` and still get a full plan. Unknown
 * keys — plan-level, per-entity, or reply_markup-level — throw the domain
 * exception `InvalidMessagePlanException`, exactly as RULINGS Q14 requires.
 * This checks the message type ONLY; it never touches the network.
 */
final class MessagePlanValidator
{
    /** Bot API `ReplyMarkup` variant keys (all four markup types). */
    private const REPLY_MARKUP_KEYS = [
        'inline_keyboard',
        'keyboard',
        'remove_keyboard',
        'force_reply',
        'input_field_placeholder',
        'selective',
        'resize_keyboard',
        'one_time_keyboard',
        'is_persistent',
    ];

    /**
     * @param array<string, mixed> $plan
     * @return array{text: string, entities: list<array<string, mixed>>, reply_markup?: array<string, mixed>}
     *
     * @throws InvalidMessagePlanException on any unknown key or structural violation
     */
    public static function assertPlanValid(array $plan, string $methodName = 'messages.sendMessage'): array
    {
        $method = MethodRegistry::get($methodName);

        $allowed = [
            'text' => self::param($method->params, 'message'),
            'entities' => self::param($method->params, 'entities'),
            'reply_markup' => self::param($method->params, 'reply_markup'),
        ];

        foreach ($plan as $key => $value) {
            if (! array_key_exists((string) $key, $allowed)) {
                $known = implode(', ', array_keys($allowed));
                throw new InvalidMessagePlanException(
                    "Unknown plan key [{$key}] for method [{$methodName}]. Known plan keys: {$known}."
                );
            }
        }

        self::assertText($plan, $methodName);

        $normalized = [
            'text' => (string) $plan['text'],
            'entities' => array_key_exists('entities', $plan)
                ? self::assertEntities($plan['entities'], $methodName)
                : [],
        ];

        if (array_key_exists('reply_markup', $plan)) {
            $normalized['reply_markup'] = self::assertReplyMarkup($plan['reply_markup'], $methodName);
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $plan
     */
    private static function assertText(array $plan, string $methodName): void
    {
        $text = $plan['text'] ?? null;
        if (! is_string($text) || $text === '') {
            throw new InvalidMessagePlanException(
                "Plan for method [{$methodName}] needs a non-empty string text."
            );
        }
    }

    /**
     * @param mixed $entities
     * @return list<array<string, mixed>>
     */
    private static function assertEntities(mixed $entities, string $methodName): array
    {
        if (! is_array($entities)) {
            throw new InvalidMessagePlanException("Plan [{$methodName}] entities must be an array of entity objects.");
        }

        $normalized = [];
        foreach (array_values($entities) as $entity) {
            if (! is_array($entity)) {
                throw new InvalidMessagePlanException("Plan [{$methodName}] entities must be arrays.");
            }

            $normalized[] = self::assertEntity($entity, $methodName);
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $entity
     * @return array<string, mixed>
     */
    private static function assertEntity(array $entity, string $methodName): array
    {
        $type = $entity['_'] ?? null;
        if (! is_string($type) || $type === '' || ! str_starts_with($type, 'messageEntity')) {
            throw new InvalidMessagePlanException(
                "Entity in plan [{$methodName}] must declare a `_` key naming a messageEntity* constructor."
            );
        }

        $offset = $entity['offset'] ?? null;
        $length = $entity['length'] ?? null;
        if (! is_int($offset) || $offset < 0) {
            throw new InvalidMessagePlanException("Entity [{$type}] offset must be a non-negative int.");
        }
        if (! is_int($length) || $length < 1) {
            throw new InvalidMessagePlanException("Entity [{$type}] length must be a positive int.");
        }

        $allowedExtras = EntityParser::ENTITY_EXTRA_KEYS[$type] ?? [];

        foreach ($entity as $key => $value) {
            if ($key === '_' || $key === 'offset' || $key === 'length') {
                continue;
            }
            if (! in_array((string) $key, $allowedExtras, true)) {
                throw new InvalidMessagePlanException("Unknown entity key [{$key}] for entity type [{$type}].");
            }
        }

        self::assertEntityExtraTypes($type, $entity, $methodName);

        $normalized = [
            '_' => $type,
            'offset' => $offset,
            'length' => $length,
        ];
        foreach ($allowedExtras as $extra) {
            if (array_key_exists($extra, $entity)) {
                $normalized[$extra] = $entity[$extra];
            }
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $entity
     */
    private static function assertEntityExtraTypes(string $type, array $entity, string $methodName): void
    {
        if (array_key_exists('url', $entity) && ! is_string($entity['url'])) {
            throw new InvalidMessagePlanException("Entity [{$type}] url must be a string.");
        }
        if (array_key_exists('document_id', $entity) && ! is_int($entity['document_id'])) {
            throw new InvalidMessagePlanException("Entity [{$type}] document_id must be an int.");
        }
        if (array_key_exists('user_id', $entity) && ! is_int($entity['user_id'])) {
            throw new InvalidMessagePlanException("Entity [{$type}] user_id must be an int.");
        }
        if (array_key_exists('language', $entity) && ! is_string($entity['language'])) {
            throw new InvalidMessagePlanException("Entity [{$type}] language must be a string.");
        }
    }

    /**
     * @param mixed $replyMarkup
     * @return array<string, mixed>
     */
    private static function assertReplyMarkup(mixed $replyMarkup, string $methodName): array
    {
        if (! is_array($replyMarkup)) {
            throw new InvalidMessagePlanException("Plan [{$methodName}] reply_markup must be an array.");
        }

        foreach ($replyMarkup as $key => $value) {
            if (! in_array((string) $key, self::REPLY_MARKUP_KEYS, true)) {
                $known = implode(', ', self::REPLY_MARKUP_KEYS);
                throw new InvalidMessagePlanException(
                    "Unknown reply_markup key [{$key}] for method [{$methodName}]. Known keys: {$known}."
                );
            }
        }

        return $replyMarkup;
    }

    /**
     * Locate a param entry by name within a method's params list.
     *
     * @param list<array{name: string, type: string, flag_word: string|null, bit: int|null, required: bool|null, description: string}> $params
     * @return array{name: string, type: string, flag_word: string|null, bit: int|null, required: bool|null, description: string}|null
     */
    private static function param(array $params, string $name): ?array
    {
        foreach ($params as $param) {
            if ($param['name'] === $name) {
                return $param;
            }
        }

        return null;
    }
}