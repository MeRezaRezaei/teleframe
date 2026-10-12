<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Constraint;
use MeRezaRezaei\Teleframe\DataCore\Declaration\DataDeclaration;
use MeRezaRezaei\Teleframe\DataCore\Declaration\DeclarationRegistry;
use MeRezaRezaei\Teleframe\DataCore\Declaration\Field;
use MeRezaRezaei\Teleframe\DataCore\Obligation\ObligationFactory;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;

/**
 * Emits final rule-map validators for declared data shapes (plan §3/§4):
 * one static rules() (the whole map, derived from each field's constraint
 * laravelRules()) plus a static validate() delegating to the final
 * StageValidator — the generated class WRAPS it, never extends it.
 *
 * Emission dial (hardcoded this slice — config/teleframe.php is dirty with
 * unrelated work): business declarations ALWAYS; TL-mirror declarations ONLY
 * for the curated-methods.json method surface (never the full mirror).
 */
final class ValidatorGenerator
{
    private const NS = 'MeRezaRezaei\Teleframe\Schema\Generated\Validators';

    /** @var array<string, true>|null memoized curated method names (raw + pascal forms) */
    private ?array $curated = null;

    public function __construct(private readonly DeclarationRegistry $declarations) {}

    /** @return array<string,string> relative path (Validators/X.php) => content */
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
            if (! isset($kinds['validate'])) {
                continue;
            }

            $class = Naming::pascal($declaration->name).'Validator';
            $classes[] = $class;

            $body = [
                '/** Validator for '.$declaration->name.' (derived from '.$declaration->source.' by ValidatorGenerator). */',
                'final class '.$class,
                '{',
                '    /** @return array<string, list<mixed>> — one source, every leg. */',
                '    public static function rules(): array',
                '    {',
                '        return [',
                ...$this->ruleMapLines($declaration),
                '        ];',
                '    }',
                '',
                '    /** @param array<string, mixed> $data @return array<string, mixed> */',
                '    public static function validate(array $data): array',
                '    {',
                '        return (new StageValidator())->data($data, self::rules());',
                '    }',
                '}',
            ];

            $files[$class.'.php'] = CodeWriter::phpFile(self::NS, [
                'use MeRezaRezaei\Teleframe\Stage\StageValidator;',
                '',
                ...$body,
            ]);
        }

        Naming::assertUnique($classes, 'validator class');
        ksort($files);

        return $files;
    }

    /**
     * Dial: a declaration is emitted when it is a business shape (source
     * not from the tl-mirror) OR its name sits on the curated-methods.json
     * surface. TL-mirror declarations never leak into the full mirror by
     * default — only the curated method names get a generated Validator.
     */
    private function emitAllowed(DataDeclaration $declaration): bool
    {
        if (! str_starts_with($declaration->source, 'tl-mirror')) {
            return true;
        }

        return isset($this->curatedMethods()[$declaration->name]);
    }

    /**
     * Rule-map source lines: one entry per declared field in declaration
     * order, each constraint's laravelRules() concatenated in order (array /
     * string ops only — no regex). Field keys are aligned for readability;
     * the maps themselves are order-deterministic.
     *
     * @return list<string>
     */
    private function ruleMapLines(DataDeclaration $declaration): array
    {
        $entries = [];
        $maxKey = 0;
        foreach ($declaration->fields as $field) {
            $key = "'".$field->name."'";
            $maxKey = max($maxKey, strlen($key));
            $entries[] = [$key, $this->fieldRules($field)];
        }

        $lines = [];
        foreach ($entries as [$key, $rules]) {
            $pad = str_repeat(' ', $maxKey - strlen($key));
            $lines[] = '            '.$key.$pad.' => ['.implode(', ', $rules).'],';
        }

        return $lines;
    }

    /** @return list<string> PHP literals, one per Laravel rule string */
    private function fieldRules(Field $field): array
    {
        $rules = [];
        foreach ($field->constraints as $constraint) {
            $rules[] = $this->constraintRules($constraint);
        }
        if ($rules === []) {
            return [];
        }

        return array_merge(...$rules);
    }

    /** @return list<string> */
    private function constraintRules(Constraint $constraint): array
    {
        $out = [];
        foreach ($constraint->laravelRules() as $rule) {
            if ($rule === 'sometimes') {
                // Plan §4: a default is a data-shape concern, not a
                // validator rule — DefaultValue's 'sometimes' marker
                // (plan §1 semantics) never reaches the generated map.
                continue;
            }
            $out[] = var_export((string) $rule, true);
        }

        return $out;
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
