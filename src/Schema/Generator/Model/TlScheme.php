<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator\Model;

/** A parsed TL scheme: layer + all types + all methods. */
final class TlScheme
{
    /** @var array<string, TlType> keyed by full type name */
    private array $types = [];
    /** @var array<string, TlMethod> keyed by full method name */
    private array $methods = [];
    /** @var array<string, array{stated:string, computed:string}> crc32 mismatches (stated id trusted anyway) */
    public array $crcMismatches = [];

    public function __construct(public readonly int $layer)
    {
    }

    public function ensureType(string $name): TlType
    {
        return $this->types[$name] ??= new TlType($name);
    }

    public function addType(TlType $type): void
    {
        foreach ($type->constructors() as $c) {
            $this->ensureType($c->resultType)->addConstructor($c);
        }
        if (isset($this->types[$type->name])) {
            foreach ($type->constructors() as $c) {
                $this->types[$type->name]->addConstructor($c);
            }
            return;
        }
        $this->types[$type->name] = $type;
    }

    public function addConstructor(TlConstructor $c): void
    {
        $type = $this->ensureType($c->resultType);
        if (isset($type->constructors()[$c->name])) {
            // Last-wins (secret scheme ===N=== sections re-define constructors).
            $replacement = new TlType($c->resultType);
            foreach ($type->constructors() as $old) {
                if ($old->name !== $c->name) {
                    $replacement->addConstructor($old);
                }
            }
            $replacement->addConstructor($c);
            $this->types[$c->resultType] = $replacement;
            return;
        }
        $type->addConstructor($c);
    }

    /** Remove a constructor (used by last-wins replacement when the result type changed). */
    public function removeConstructor(string $ctorName): void
    {
        foreach ($this->types as $key => $type) {
            if (isset($type->constructors()[$ctorName])) {
                $replacement = new TlType($type->name);
                foreach ($type->constructors() as $old) {
                    if ($old->name !== $ctorName) {
                        $replacement->addConstructor($old);
                    }
                }
                $this->types[$key] = $replacement;
            }
        }
    }

    public function addMethod(TlMethod $m): void
    {
        $this->methods[$m->name] = $m;
        $this->ensureType($m->returnType);
    }

    /** @return array<string, TlType> */
    public function types(): array
    {
        return $this->types;
    }

    /** @return array<string, TlMethod> */
    public function methods(): array
    {
        return $this->methods;
    }

    /** @return array{types:int,constructors:int,methods:int} */
    public function counts(): array
    {
        $ctors = 0;
        foreach ($this->types as $t) {
            $ctors += count($t->constructors());
        }
        return ['types' => count($this->types), 'constructors' => $ctors, 'methods' => count($this->methods)];
    }
}
