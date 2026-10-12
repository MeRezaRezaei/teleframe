<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use MeRezaRezaei\Teleframe\DataCore\Declaration\DataDeclaration;
use MeRezaRezaei\Teleframe\DataCore\Declaration\DeclarationRegistry;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Field;
use MeRezaRezaei\Teleframe\DataCore\Obligation\ObligationFactory;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use RuntimeException;

/**
 * Emits one plain event DTO per EventObligation declaration (plan §3/§4):
 * readonly promoted props from the declaration's fields, zero framework
 * traits, dispatchable through any Illuminate\Contracts\Events\Dispatcher.
 * The shape follows the UpdateStored convention: ids + decision fields only,
 * NEVER session strings (session strings are credentials); a trailing
 * $operation string ('upsert' | 'delete') carries the change kind.
 */
final class EventGenerator
{
    private const NS = 'MeRezaRezaei\Teleframe\Schema\Generated\Events';

    private const SCALAR_TYPES = [
        'int' => true,
        'string' => true,
        'bool' => true,
        'float' => true,
        'array' => true,
    ];

    /** @var array<string, true>|null memoized curated method names (raw + pascal forms) */
    private ?array $curated = null;

    public function __construct(private readonly DeclarationRegistry $declarations) {}

    /** @return array<string,string> relative path (Events/X.php) => content */
    public function generate(TlScheme $scheme): array
    {
        $files = [];
        $classes = [];
        $factory = new ObligationFactory;

        foreach ($this->declarations->all() as $declaration) {
            if (! $this->emitAllowed($declaration)) {
                continue;
            }
            $kinds = [];
            foreach ($factory->for($declaration) as $obligation) {
                $kinds[$obligation->kind] = true;
            }
            if (! isset($kinds['notify'])) {
                continue;
            }

            $class = $this->eventClassName(Naming::pascal($declaration->name));
            $classes[] = $class;

            $props = [];
            foreach ($declaration->fields as $field) {
                if ($this->isCredentialField($field)) {
                    continue;
                }
                $prop = self::camel($field->name);
                if (! self::isIdentifier($prop)) {
                    throw new RuntimeException(
                        'event property "'.$field->name.'" of '.$declaration->name.' is not a legal PHP identifier'
                    );
                }
                if (isset($props[$prop])) {
                    throw new RuntimeException(
                        'event property collision: "'.$prop.'" produced twice for '.$declaration->name
                    );
                }
                $props[$prop] = '        public readonly '.$this->propType($field->phpType).' $'.$prop.',';
            }
            if (isset($props['operation'])) {
                throw new RuntimeException(
                    'event property collision: a field of '.$declaration->name.' already produced "operation"'
                );
            }
            $props['operation'] = "        public readonly string \$operation,   // 'upsert' | 'delete'";

            $body = [
                '/** Fired when an '.$declaration->name.' row is written/deleted (EventObligation of '.$declaration->source.'). */',
                'final class '.$class,
                '{',
                '    public function __construct(',
                ...array_values($props),
                '    ) {}',
                '}',
            ];

            $files[$class.'.php'] = CodeWriter::phpFile(self::NS, $body);
        }

        Naming::assertUnique($classes, 'event class');
        ksort($files);

        return $files;
    }

    /**
     * Dial: a declaration is emitted when it is a business shape (source
     * not from the tl-mirror) OR its name sits on the curated-methods.json
     * surface — same dial as the sibling emitters.
     */
    private function emitAllowed(DataDeclaration $declaration): bool
    {
        if (! str_starts_with($declaration->source, 'tl-mirror')) {
            return true;
        }

        return isset($this->curatedMethods()[$declaration->name]);
    }

    /** 'UpdateRoutingRule' → 'RoutingRuleChanged'; plain names → '<Name>Changed'. */
    private function eventClassName(string $base): string
    {
        $stripped = str_starts_with($base, 'Update') ? substr($base, 6) : $base;

        return $stripped.'Changed';
    }

    /** Session strings are credentials: ids-only events never carry them. */
    private function isCredentialField(Field $field): bool
    {
        $name = strtolower($field->name);

        return str_contains($name, 'session') && ! str_ends_with($name, '_id');
    }

    /** Scalar phpTypes render verbatim; DTO/unknown phpTypes degrade to mixed. */
    private function propType(string $phpType): string
    {
        return isset(self::SCALAR_TYPES[$phpType]) ? $phpType : 'mixed';
    }

    /** snake_case → camelCase: 'peer_id' → 'peerId'. */
    private static function camel(string $name): string
    {
        return lcfirst(implode('', array_map('ucfirst', explode('_', $name))));
    }

    /** Regex-free PHP identifier check (promoted-property names must be legal). */
    private static function isIdentifier(string $name): bool
    {
        if ($name === '') {
            return false;
        }
        if ($name[0] !== '_' && ! ctype_alpha($name[0])) {
            return false;
        }

        return strspn($name, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_') === strlen($name);
    }

    /** @return array<string, true> curated method names, raw and pascal forms */
    private function curatedMethods(): array
    {
        if ($this->curated !== null) {
            return $this->curated;
        }

        $path = __DIR__.'/../config/curated-methods.json';
        if (! is_file($path)) {
            $path = __DIR__.'/../../../config/curated-methods.json';
        }
        if (! is_file($path)) {
            throw new TlRegenerateException('curated-methods.json not found (looked in src/Schema/config and config)');
        }

        $config = json_decode((string) file_get_contents($path), true);
        if (! is_array($config)) {
            throw new TlRegenerateException('curated-methods.json is not a JSON object');
        }

        $set = [];
        foreach (['mtproto', 'bot-http'] as $section) {
            $names = $config[$section] ?? [];
            if (! is_array($names)) {
                continue;
            }
            foreach ($names as $name) {
                if (! is_string($name) || $name === '') {
                    continue;
                }
                $set[$name] = true;
                $set[Naming::pascal($name)] = true;
            }
        }
        $this->curated = $set;

        return $set;
    }
}
