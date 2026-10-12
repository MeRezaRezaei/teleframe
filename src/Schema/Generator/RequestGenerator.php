<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use MeRezaRezaei\Teleframe\DataCore\Declaration\DataDeclaration;
use MeRezaRezaei\Teleframe\DataCore\Declaration\DeclarationRegistry;
use MeRezaRezaei\Teleframe\DataCore\Obligation\ObligationFactory;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;

/**
 * Emits one FormRequest per RequestObligation declaration (plan §3/§4): the
 * generated class extends the abstract StageFormRequest, authorize() is
 * true, and rules() delegates to the paired generated Validator — web leg
 * and stage leg validate identically. A Request is only emitted when its
 * paired Validator is also emitted (same declaration derives both a 'request'
 * and a 'validate' obligation AND passes the emission dial), so generated
 * code never references a class that does not exist.
 */
final class RequestGenerator
{
    private const NS = 'MeRezaRezaei\Teleframe\Schema\Generated\Requests';

    private const VALIDATORS_NS = 'MeRezaRezaei\Teleframe\Schema\Generated\Validators';

    /** @var array<string, true>|null memoized curated method names (raw + pascal forms) */
    private ?array $curated = null;

    public function __construct(private readonly DeclarationRegistry $declarations) {}

    /** @return array<string,string> relative path (Requests/X.php) => content */
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
            // One rules() serves both legs; it must point at a Validator
            // that is actually generated — hence both kinds required.
            if (! isset($kinds['request']) || ! isset($kinds['validate'])) {
                continue;
            }

            $base = Naming::pascal($declaration->name);
            $class = $base.'Request';
            $validator = $base.'Validator';
            $classes[] = $class;

            $body = [
                '/** Request for '.$declaration->name.' — web leg and stage leg validate identically. */',
                'final class '.$class.' extends StageFormRequest',
                '{',
                '    public function authorize(): bool',
                '    {',
                '        return true;',
                '    }',
                '',
                '    /** @return array<string, list<mixed>> */',
                '    public function rules(): array',
                '    {',
                '        return '.$validator.'::rules();',
                '    }',
                '}',
            ];

            $files[$class.'.php'] = CodeWriter::phpFile(self::NS, [
                'use '.self::VALIDATORS_NS.'\\'.$validator.';',
                'use MeRezaRezaei\Teleframe\Stage\StageFormRequest;',
                '',
                ...$body,
            ]);
        }

        Naming::assertUnique($classes, 'request class');
        ksort($files);

        return $files;
    }

    /**
     * Dial: a declaration is emitted when it is a business shape (source
     * not from the tl-mirror) OR its name sits on the curated-methods.json
     * surface — the same dial the ValidatorGenerator applies, so the paired
     * Validator always exists.
     */
    private function emitAllowed(DataDeclaration $declaration): bool
    {
        if (! str_starts_with($declaration->source, 'tl-mirror')) {
            return true;
        }

        return isset($this->curatedMethods()[$declaration->name]);
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
