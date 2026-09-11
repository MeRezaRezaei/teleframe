<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;

final class MirrorCatalog
{
    /** @var array<string, MirrorTableEntry> */
    private array $tables = [];

    /** @var array<string, string> tl type => tf table */
    private array $tlIndex = [];

    /** @param array<string, array{tl:string,ctors:list<string>,base:list<array{string,string}>,bools:list<string>,children:list<array{string,string}>}> $entries */
    public static function fromEntries(array $entries): self
    {
        $self = new self();
        foreach ($entries as $tfName => $e) {
            $entry = new MirrorTableEntry($tfName, $e['tl'], $e['ctors'], $e['base'], $e['bools'], $e['children']);
            $self->tables[$tfName] = $entry;
            $self->tlIndex[$e['tl']] = $tfName;
            $self->assertEntryInvariants($entry);
        }
        return $self;
    }

    public static function load(string $jsonPath, TlScheme $scheme): self
    {
        $json = file_get_contents($jsonPath);
        if ($json === false) {
            throw new MirrorSchemaException("Cannot read catalog: {$jsonPath}");
        }
        $entries = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $entries = (new MirrorCtorSplitter($scheme))->apply($entries);
        return self::fromEntries($entries);
    }

    /** @return list<string> */
    public function tableNames(): array
    {
        return array_keys($this->tables);
    }

    public function table(string $tfName): MirrorTableEntry
    {
        return $this->tables[$tfName] ?? throw new MirrorSchemaException("Unknown mirror table: {$tfName}");
    }

    public function has(string $tfName): bool
    {
        return isset($this->tables[$tfName]);
    }

    public function tableForTlType(string $tlType): ?MirrorTableEntry
    {
        return isset($this->tlIndex[$tlType]) ? $this->tables[$this->tlIndex[$tlType]] : null;
    }

    public function assertInvariants(): void
    {
        foreach ($this->tables as $name => $entry) {
            // Ruling B: peer-first tables (e.g. tf_dialogs) are valid — only reject neither-id-nor-peer
            if ($entry->base !== [] && !in_array($entry->base[0][0], ['id', 'peer'], true)) {
                throw new MirrorSchemaException("{$name}: first base column must be 'id' or 'peer'");
            }
        }
    }

    private function assertEntryInvariants(MirrorTableEntry $e): void
    {
        if ($e->ctors === []) {
            throw new MirrorSchemaException("{$e->tfName}: ctors must be non-empty");
        }
        foreach ([...$e->base, ...$e->children] as [$name, $shape]) {
            // zero-NULL: ban nullable marker (but 'NOT NULL' is the canonical non-nullable form)
            if (str_contains($shape, 'NULL') && !str_contains($shape, 'NOT NULL')) {
                throw new MirrorSchemaException("{$e->tfName}.{$name}: nullable shape '{$shape}'");
            }
            foreach (['json', 'blob', 'binary'] as $banned) {
                if (str_contains(strtolower($shape), $banned)) {
                    throw new MirrorSchemaException("{$e->tfName}.{$name}: banned shape '{$shape}'");
                }
            }
        }
    }
}
