<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

final class PayloadWalker
{
    /**
     * Pure shape traversal over a decoded TL payload (teleframe truth:
     * constructor nodes carry their name under '_', vectors are PHP lists,
     * conditionally-absent fields are missing keys). Yields every constructor
     * node depth-first, parents before children.
     *
     * @param array<string, mixed>|list<mixed> $payload
     * @return \Generator<int, array{constructor: string, payload: array<string, mixed>, path: string, parent_param: string|null, is_root: bool, vector_index: int|null}, void, void>
     */
    public static function walk(array $payload): \Generator
    {
        yield from self::walkValue($payload, '', null, null, true);
    }

    /**
     * @param array<string, mixed>|list<mixed>|scalar|null $value
     */
    private static function walkValue(mixed $value, string $parentPath, ?string $parentParam, ?int $vectorIndex, bool $isRoot): \Generator
    {
        if (!is_array($value)) {
            return;
        }

        if (array_is_list($value)) {
            foreach ($value as $index => $item) {
                yield from self::walkValue($item, $parentPath, $parentParam, $index, false);
            }

            return;
        }

        $constructor = $value['_'] ?? null;
        if (!is_string($constructor)) {
            return;
        }

        $path = $isRoot ? $constructor : self::childPath($parentPath, $parentParam, $vectorIndex);

        yield [
            'constructor' => $constructor,
            'payload' => $value,
            'path' => $path,
            'parent_param' => $parentParam,
            'is_root' => $isRoot,
            'vector_index' => $vectorIndex,
        ];

        foreach ($value as $key => $fieldValue) {
            if ($key !== '_' && is_string($key)) {
                yield from self::walkValue($fieldValue, $path, $key, null, false);
            }
        }
    }

    private static function childPath(string $parentPath, ?string $parentParam, ?int $vectorIndex): string
    {
        $parts = [];
        if ($parentParam !== null && $parentParam !== '') {
            $parts[] = $parentParam;
        }
        if ($vectorIndex !== null) {
            $parts[] = (string) $vectorIndex;
        }
        $segment = implode('.', $parts);
        if ($segment === '') {
            return $parentPath;
        }

        return $parentPath === '' ? $segment : $parentPath . '.' . $segment;
    }
}
