<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use MeRezaRezaei\Teleframe\Core\Schema\SchemaDiffer;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Schema\WireSchemaCoverage;
use RuntimeException;

/**
 * Regenerates both schema artifacts into a temp dir from the committed
 * sources (offline) and diffs them against the committed artifacts.
 *
 * Exit 0 = committed artifacts are up to date; exit 1 = any difference
 * (added/removed/changed methods or a layer bump). Logic lives in
 * SchemaDiffer (tested) and the generators (tested); this class only
 * orchestrates processes and I/O. Zero regex by repo spec.
 *
 * It ALSO prints the UNSUPPORTED half of the catalogue: which catalogued
 * methods a host cannot dial, in three named families with their real counts.
 * `WireSchemaCoverage` is a rich, honest classifier that used to be reachable
 * only from PHP — `grep -rn WireSchemaCoverage src/Laravel/Console/` was empty
 * — so a limit that existed but could not be discovered was a trap. A host can
 * now ask the tool what it cannot do. The counts here are COMPUTED from the
 * classifier, never transcribed, so the report cannot drift from the code.
 */
class SchemaAuditCommand extends Command
{
    protected $signature = 'teleframe:schema-audit
                            {--write : Save the markdown report to schema/audit-report.md}';

    protected $description = 'Diff committed method schemas against a fresh regeneration from the committed sources, and list the catalogued methods this client cannot call';

    public function handle(): int
    {
        $root = self::root();
        $schemaDir = self::schemaDir();
        $tmp = sys_get_temp_dir().'/teleframe-schema-audit-'.bin2hex(random_bytes(6));

        $failure = self::regenerateTo($tmp);
        if ($failure !== null) {
            $this->line("<error>Schema regeneration failed: {$failure}</error>");
            self::cleanup($tmp);

            return 1;
        }

        $oldMtproto = self::loadArtifact("{$schemaDir}/methods-mtproto.json");
        $newMtproto = self::loadArtifact("{$tmp}/methods-mtproto.json");
        $oldBotapi = self::loadArtifact("{$schemaDir}/methods-botapi.json");
        $newBotapi = self::loadArtifact("{$tmp}/methods-botapi.json");

        $report = self::buildReport($oldMtproto, $newMtproto, $oldBotapi, $newBotapi);
        $this->line($report);
        self::cleanup($tmp);

        if ($this->option('write')) {
            file_put_contents("{$schemaDir}/audit-report.md", $report."\n");
            $this->line('<info>Report written to schema/audit-report.md</info>');
        }

        return self::anyDifference(
            SchemaDiffer::diff($oldMtproto, $newMtproto),
            SchemaDiffer::diff($oldBotapi, $newBotapi),
        ) ? 1 : 0;
    }

    /**
     * Absolute package root (repo working copy or vendor install), derived
     * from this file's location: src/Laravel/Console → package root.
     */
    public static function root(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * Absolute path to the packaged schema artifacts + sources directory.
     */
    public static function schemaDir(): string
    {
        return self::root().'/src/Schema/schema';
    }

    /**
     * The FULL generation chain for teleframe:schema-update, in order.
     * Each entry: a step name and the bin that performs it. Migrations are
     * NEVER part of this chain (spec D4) — hosts apply them explicitly.
     *
     * @return list<array{name: string, bin: string}>
     */
    public static function pipelineSteps(): array
    {
        $root = self::root();

        return [
            ['name' => 'method-schema', 'bin' => $root.'/bin/generate-method-schema.php'],
            ['name' => 'botapi-schema', 'bin' => $root.'/bin/generate-botapi-schema.php'],
            ['name' => 'method-builders', 'bin' => $root.'/bin/generate-method-builders.php'],
            ['name' => 'skill-files', 'bin' => $root.'/bin/generate-skill-files.php'],
            ['name' => 'rpc-catalog', 'bin' => $root.'/bin/generate-rpc-catalog.php'],
            ['name' => 'userscope-schema', 'bin' => $root.'/bin/generate-userscope-schema.php'],
        ];
    }

    /**
     * Run both generators writing into $outDir (sources stay the committed
     * repo ones). Returns null on success, a failure description otherwise.
     */
    public static function regenerateTo(string $outDir): ?string
    {
        $root = self::root();
        if (! is_dir($outDir) && ! mkdir($outDir, 0777, true) && ! is_dir($outDir)) {
            return "cannot create output dir {$outDir}";
        }
        foreach (['generate-method-schema.php', 'generate-botapi-schema.php'] as $generator) {
            $result = self::runProcess([PHP_BINARY, "{$root}/bin/{$generator}", $outDir], $root);
            if ($result['exit'] !== 0) {
                return "php bin/{$generator} exited {$result['exit']}: {$result['output']}";
            }
        }
        self::stampBotapiLayer($outDir);

        return null;
    }

    /**
     * W-43: the botapi generator emits the envelope without the top-level
     * `layer` key (only the mtproto generator knows the telegram layer).
     * Stamp `layer` = the freshly generated mtproto artifact's layer into
     * methods-botapi.json (placed right after `api`, mirroring the mtproto
     * envelope key order). Re-runs are byte-stable (idempotent no-op when
     * the layer already matches). Returns true when a stamp was written.
     */
    public static function stampBotapiLayer(string $dir): bool
    {
        $mtproto = self::loadArtifact("{$dir}/methods-mtproto.json");
        $layer = $mtproto['layer'] ?? null;
        if (! is_int($layer) || $layer <= 0) {
            return false;
        }
        $path = "{$dir}/methods-botapi.json";
        $botapi = json_decode((string) file_get_contents($path), true);
        if (! is_array($botapi) || ! isset($botapi['api'])) {
            throw new RuntimeException("cannot decode botapi artifact {$path}");
        }
        if (($botapi['layer'] ?? null) === $layer) {
            return false;
        }
        $stamped = [];
        foreach ($botapi as $key => $value) {
            if ($key === 'layer') {
                continue;
            }
            $stamped[$key] = $value;
            if ($key === 'api') {
                $stamped['layer'] = $layer;
            }
        }
        file_put_contents($path, json_encode($stamped, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        return true;
    }

    /**
     * The reason each non-callable family exists, in the order they are
     * reported. Keyed by the classifier's own constants so a new family cannot
     * be added to `WireSchemaCoverage` without showing up here as a gap.
     *
     * @return array<string, string>
     */
    private static function familyCauses(): array
    {
        return [
            WireSchemaCoverage::NOT_IN_WIRE_MIRROR => 'declared by the schema catalogue (sources/api.tl, layer '.WireSchemaCoverage::layerOf('api.tl').') '
                .'but ABSENT from the wire mirror the encoder uses (sources/api_full.tl, layer '
                .WireSchemaCoverage::layerOf('api_full.tl').'). Encoding goes through the mirror, so these namespaces '
                .'do not exist at the wire layer at all and can never reach Telegram. Deliberate: the wire advertises '
                .'layer '.WireSchemaCoverage::layerOf('api_full.tl').' and is not being moved.',
            WireSchemaCoverage::ENVELOPE => 'generic {X:Type} request ENVELOPES. Their .tl line declares a bound query (!X = X), so each exists to '
                .'WRAP some other request. The client hand-builds the two it needs (invokeWithLayer, initConnection), '
                .'which is why one can be on the wire and still uncallable by a host: binding query:!X needs the '
                .'wrapped request, which is the thing the client supplies for you. Call the method you actually want.',
            WireSchemaCoverage::SERVICE => 'MTProto SERVICE requests from sources/mtproto.tl, not schema-layer RPCs. The connection layer speaks '
                .'them directly during the auth handshake; they are not addressable through the method-call API, so '
                .'there is nothing for a host to call.',
        ];
    }

    /**
     * The unsupported half of the catalogue, rendered for a host.
     *
     * Computed live from `WireSchemaCoverage::unsupported()` over every
     * catalogued mtproto name — never transcribed — so a host reading only
     * this output learns, for any method they care about, whether it is
     * callable and, if not, which of the three families refuses it and why.
     *
     * Reads the catalogued name list from the committed artifact rather than
     * from the mirror: a method absent from the mirror is exactly the case
     * that must still be REPORTED, so enumerating the mirror would silently
     * hide the largest family.
     */
    public static function renderCoverageSection(): string
    {
        $names = self::cataloguedMtprotoNames();
        if ($names === []) {
            return "# Unsupported catalogued methods\n\n(methods-mtproto.json could not be read — no coverage report.)\n";
        }

        $unsupported = WireSchemaCoverage::unsupported($names);

        // Group by family, preserving the classifier's own order within a
        // family and reporting families in the declared order above.
        $byFamily = [];
        foreach ($unsupported as $name => $_reason) {
            $byFamily[WireSchemaCoverage::classify($name)][] = $name;
        }

        $out = [];
        $out[] = '# Unsupported catalogued methods — '.count($unsupported).' of '.count($names);
        $out[] = '';
        $out[] = 'Every name below is in the schema catalogue but CANNOT be called by a host.';
        $out[] = 'This section exists so the limit is discoverable: it is computed from';
        $out[] = 'WireSchemaCoverage, not transcribed.';
        $out[] = '';

        if ($unsupported === []) {
            $out[] = 'None — every catalogued method is callable.';

            return implode("\n", $out)."\n";
        }

        foreach (self::familyCauses() as $family => $cause) {
            $members = $byFamily[$family] ?? [];
            if ($members === []) {
                continue;
            }
            $out[] = '## '.$family.' — '.count($members);
            $out[] = '';
            $out[] = 'Cause: '.$cause;
            $out[] = '';
            sort($members);
            foreach ($members as $name) {
                $out[] = '  - '.$name;
            }
            $out[] = '';
        }

        // A family the classifier can emit but this renderer does not know is
        // a reporting bug, not a silent omission: say so rather than hide it.
        foreach (array_keys($byFamily) as $family) {
            if (! isset(self::familyCauses()[$family])) {
                $out[] = '## '.$family.' — '.count($byFamily[$family]);
                $out[] = '';
                $out[] = 'Cause: UNREPORTED FAMILY — WireSchemaCoverage emitted a verdict this command has no';
                $out[] = 'wording for. Name it in SchemaAuditCommand::familyCauses().';
                $out[] = '';
            }
        }

        return implode("\n", $out)."\n";
    }

    /**
     * Every function name in the committed mtproto catalogue, in file order.
     *
     * @return list<string>
     */
    public static function cataloguedMtprotoNames(): array
    {
        try {
            $artifact = self::loadArtifact(SchemaArtifacts::path('methods-mtproto.json'));
        } catch (RuntimeException) {
            return [];
        }

        $methods = $artifact['methods'] ?? null;
        if (! is_array($methods)) {
            return [];
        }

        // array_keys() on this shape is already a list, so array_values() would
        // be a no-op; strval() guards against a non-string key ever appearing.
        return array_map('strval', array_keys($methods));
    }

    /**
     * @param  array<string, mixed>  $oldMtproto
     * @param  array<string, mixed>  $newMtproto
     * @param  array<string, mixed>  $oldBotapi
     * @param  array<string, mixed>  $newBotapi
     */
    public static function buildReport(array $oldMtproto, array $newMtproto, array $oldBotapi, array $newBotapi): string
    {
        return implode("\n\n", [
            '# Schema audit report — '.date('Y-m-d H:i:s T'),
            SchemaDiffer::reportSection('mtproto', $oldMtproto, $newMtproto),
            SchemaDiffer::reportSection('bot-http', $oldBotapi, $newBotapi),
            self::renderCoverageSection(),
        ])."\n";
    }

    /**
     * True when any diff reports added/removed/changed methods or a layer bump.
     *
     * @param  array{added: list<string>, removed: list<string>, changed: list<string>, layer: int|null}  ...$diffs
     */
    public static function anyDifference(array ...$diffs): bool
    {
        foreach ($diffs as $d) {
            if ($d['added'] !== [] || $d['removed'] !== [] || $d['changed'] !== [] || $d['layer'] !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public static function loadArtifact(string $path): array
    {
        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            throw new RuntimeException("cannot decode artifact {$path}");
        }

        return $decoded;
    }

    /**
     * Dev-only subprocess orchestration: spawns the bin/ generators
     * (php bin/generate-*.php). Exempted from the bin-only proc_open
     * policy in phpstan.neon.dist — see the comment there.
     *
     * @param  list<string>  $command
     * @return array{exit: int, output: string}
     */
    public static function runProcess(array $command, ?string $cwd = null): array
    {
        $proc = proc_open(
            $command,
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $cwd,
        );
        if (! is_resource($proc)) {
            return ['exit' => -1, 'output' => 'proc_open failed'];
        }
        fclose($pipes[0]);
        $output = (string) stream_get_contents($pipes[1]);
        $output .= (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = (int) proc_close($proc);

        return ['exit' => $exit, 'output' => trim($output)];
    }

    public static function cleanup(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (['methods-mtproto.json', 'methods-botapi.json'] as $file) {
            $path = "{$dir}/{$file}";
            if (file_exists($path)) {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
