<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

/**
 * Injective, deterministic tf-name -> PHP class-name mapping shared by the
 * mirror writers (models + factories must agree on every class name).
 *
 * Singularization is greedy over ascending tf-names: a singular table (e.g.
 * `tf_users_username`) is a strict prefix of its plural sibling
 * (`tf_users_usernames`) and so always claims `TfUsersUsername`; the plural
 * falls back to its full un-stripped form (`TfUsersUsernames`) instead of
 * clobbering the same file.
 */
final class MirrorClassName
{
    /**
     * @param  list<string>  $tfNames
     * @return array<string, string> tfName => PHP class name
     */
    public static function map(array $tfNames): array
    {
        usort($tfNames, static fn (string $a, string $b) => $a <=> $b);

        $result = [];
        $claimed = [];

        foreach ($tfNames as $tfName) {
            $candidate = self::singular($tfName);
            if (isset($claimed[$candidate]) && $claimed[$candidate] !== $tfName) {
                $candidate = self::full($tfName);
            }
            if (isset($claimed[$candidate]) && $claimed[$candidate] !== $tfName) {
                throw new MirrorSchemaException("Unresolvable class-name collision for: {$tfName}");
            }
            $result[$tfName] = $candidate;
            $claimed[$candidate] = $tfName;
        }

        return $result;
    }

    private static function singular(string $tfName): string
    {
        $out = self::pascal($tfName);

        // T6d: map known plurals BEFORE stripping trailing 's'
        foreach (['Entities' => 'Entity', 'Medias' => 'Media', 'Actions' => 'Action'] as $from => $to) {
            if (str_ends_with($out, $from)) {
                return substr($out, 0, -strlen($from)).$to;
            }
        }
        if (str_ends_with($out, 's') && !str_ends_with($out, 'ss')) {
            $out = substr($out, 0, -1);
        }

        return $out;
    }

    private static function full(string $tfName): string
    {
        return self::pascal($tfName);
    }

    private static function pascal(string $tfName): string
    {
        $parts = explode('_', $tfName);
        $out = '';
        foreach ($parts as $part) {
            $out .= ucfirst($part);
        }

        return $out;
    }
}
