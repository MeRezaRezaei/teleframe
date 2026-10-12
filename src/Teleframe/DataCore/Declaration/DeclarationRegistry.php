<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Declaration;

use LogicException;

/**
 * Runtime map of declared shapes: declaration name ⇒ DataDeclaration.
 *
 * The single knowledge graph the verbatim asks for (plan §1): pure PHP map,
 * no Laravel container, plain-constructible. Build one registry per merged
 * surface (tl-mirror + business declarations) and read it from anywhere —
 * emitters, obligations, generated code.
 */
final class DeclarationRegistry
{
    /** @var array<string, DataDeclaration> */
    private array $declarations = [];

    /**
     * @param  list<DataDeclaration>  $declarations
     *
     * @throws LogicException when a declaration name is declared twice — the
     *                        knowledge graph must be unambiguous
     */
    public static function fromDeclarations(array $declarations): self
    {
        $registry = new self;
        foreach ($declarations as $declaration) {
            $name = $declaration->name;
            if (isset($registry->declarations[$name])) {
                throw new LogicException('Duplicate declaration name: '.$name);
            }
            $registry->declarations[$name] = $declaration;
        }

        return $registry;
    }

    /** @return array<string, DataDeclaration> */
    public function all(): array
    {
        return $this->declarations;
    }

    public function get(string $name): ?DataDeclaration
    {
        return $this->declarations[$name] ?? null;
    }
}
