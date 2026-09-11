<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;

/**
 * Emits Eloquent models for TDLib-style domain tables: one model per
 * domain (TlUser, TlMessage, etc.) extending TlAnchorModel.
 */
final class ModelGenerator
{
    private const NS = 'MeRezaRezaei\Teleframe\Schema\Generated\Models';

    /**
     * Domains that have PeerResolution trait (types with peer_id/from_id columns).
     *
     * @var array<string, true>
     */
    private const PEER_RESOLUTION_DOMAINS = [
        'messages' => true,
        'dialogs' => true,
        'updates' => true,
        'stories' => true,
        'channel_participants' => true,
    ];

    public static function modelFqcn(string $class): string
    {
        return self::NS . '\\' . $class;
    }

    /** @return array<string,string> class file path (Generated/Models/X.php) => content */
    public function generate(TlScheme $scheme): array
    {
        $files = [];
        $classes = [];

        // Collect which TL types map to each domain
        $domainTypes = [];
        $types = $scheme->types();
        ksort($types);
        foreach ($types as $type) {
            if ($type->name === 'Vector t' || $type->constructors() === []) {
                continue;
            }
            $classification = Naming::classifyType($type->name);
            if ($classification === null) {
                continue; // ephemeral — no model
            }
            $domain = $classification['domain'];
            $domainTypes[$domain][] = $type->name;
        }

        // Emit one model per domain
        ksort($domainTypes);
        foreach ($domainTypes as $domain => $typeNames) {
            $class = Naming::domainModel($domain);
            $table = Naming::domainTable($domain);
            $classes[] = $class;

            $uses = ['AccountScoped'];

            if (isset(self::PEER_RESOLUTION_DOMAINS[$domain])) {
                $uses[] = 'PeerResolution';
            }

            // Collect constructor_id values for type-aware helpers
            $ctorIds = [];
            foreach ($typeNames as $typeName) {
                $type = $types[$typeName];
                foreach ($type->constructors() as $ctor) {
                    $ctorIds[$ctor->name] = sprintf('%08x', $ctor->id);
                }
            }

            $body = [
                '/** Domain model for ' . $domain . ' (TL types: ' . implode(', ', $typeNames) . '). */',
                'final class ' . $class . ' extends TlAnchorModel',
                '{',
                '    use ' . implode(', ', $uses) . ';',
                '',
                "    protected \$table = '{$table}';",
                '',
                '    protected $guarded = [];',
                '}',
            ];

            $imports = [
                'MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel',
                'MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped',
            ];
            if (isset(self::PEER_RESOLUTION_DOMAINS[$domain])) {
                $imports[] = 'MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution';
            }

            $files[$class . '.php'] = CodeWriter::phpFile(self::NS, [...self::useLines($imports), ...$body]);
        }

        Naming::assertUnique($classes, 'model class');
        ksort($files);
        return $files;
    }

    /**
     * Per-file use-import assembly: deduped, ksort-deterministic 'use X;'
     * lines terminated by a single blank line.
     *
     * @param list<string> $imports fully-qualified class names
     * @return list<string> deduped 'use X;' lines in ksort order plus a blank separator
     */
    private static function useLines(array $imports): array
    {
        $set = [];
        foreach ($imports as $import) {
            if ($import !== '') {
                $set[$import] = true;
            }
        }
        ksort($set);
        $lines = [];
        foreach (array_keys($set) as $fqcn) {
            $lines[] = 'use ' . $fqcn . ';';
        }
        $lines[] = '';
        return $lines;
    }
}
