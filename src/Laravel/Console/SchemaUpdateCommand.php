<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use MeRezaRezaei\Teleframe\Core\Schema\SchemaDiffer;

/**
 * Fetches fresh upstream sources (curl, manual/CI network step), then
 * runs the full regeneration chain, then stamps the schema-manifest.json
 * layer and reports the diff against the pre-update committed artifacts.
 *
 * --no-fetch  : skip the network step, regenerate purely from the committed
 *               sources (fully offline).
 * --dry-run   : regenerate + stamp into a scratch dir, leave the repo
 *               untouched (safe preview of a layer bump).
 *
 * The chain NEVER runs migrations (spec D4). A failed fetch never aborts
 * with committed sources damaged: curl writes a temp file that is renamed
 * over the destination only on success. Zero regex by repo spec.
 */
class SchemaUpdateCommand extends Command
{
    protected $signature = 'teleframe:schema-update
                            {--no-fetch : Skip the network fetch, regenerate from committed sources}
                            {--dry-run : Regenerate + stamp into a scratch dir, leave the repo untouched}';

    protected $description = 'Fetch upstream schema sources, regenerate the full artifact chain, and stamp the layer manifest';

    /** @var list<array{url: string, dest: string}> */
    private const FETCHES = [
        ['url' => 'https://raw.githubusercontent.com/telegramdesktop/tdesktop/dev/Telegram/SourceFiles/mtproto/scheme/api.tl', 'dest' => '../sources/api.tl'],
        ['url' => 'https://raw.githubusercontent.com/telegramdesktop/tdesktop/dev/Telegram/SourceFiles/mtproto/scheme/mtproto.tl', 'dest' => '../sources/mtproto.tl'],
        ['url' => 'https://core.telegram.org/api/errors.json', 'dest' => '../sources/errors.json'],
        ['url' => 'https://raw.githubusercontent.com/danog/MadelineProto/master/extracted.json', 'dest' => '../sources/extracted.json'],
        ['url' => 'https://raw.githubusercontent.com/PaulSonOfLars/telegram-bot-api-spec/main/api.json', 'dest' => '../sources/botapi-spec.json'],
    ];

    public function handle(): int
    {
        $root = SchemaAuditCommand::root();
        $schemaDir = SchemaAuditCommand::schemaDir();
        $sourcesDir = "{$schemaDir}/sources";

        if (!$this->option('no-fetch')) {
            $failures = [];
            foreach (self::FETCHES as $fetch) {
                $dest = $sourcesDir . '/' . basename($fetch['dest']);
                $tmpDest = $dest . '.fetching';
                $result = SchemaAuditCommand::runProcess(['curl', '-fsSL', '--max-time', '120', $fetch['url'], '-o', $tmpDest], $root);
                if ($result['exit'] !== 0 || !file_exists($tmpDest)) {
                    if (file_exists($tmpDest)) {
                        unlink($tmpDest);
                    }
                    $failures[] = [$fetch, $result];
                    continue;
                }
                rename($tmpDest, $dest);
                $this->line('<info>fetched ' . $fetch['dest'] . '</info>');
            }
            if ($failures !== []) {
                $this->reportFetchFailures($failures);
                return 1;
            }
        }

        if ($this->option('dry-run')) {
            return $this->dryRun($schemaDir);
        }

        return $this->realRun($root, $schemaDir);
    }

    private function dryRun(string $schemaDir): int
    {
        $scratch = sys_get_temp_dir() . '/teleframe-schema-update-' . bin2hex(random_bytes(6));
        if (!is_dir($scratch) && !mkdir($scratch, 0777, true) && !is_dir($scratch)) {
            $this->line("<error>cannot create scratch dir {$scratch}</error>");
            return 1;
        }

        $failure = SchemaAuditCommand::regenerateTo($scratch);
        if ($failure !== null) {
            self::cleanupScratch($scratch);
            $this->line("<error>Schema regeneration failed: {$failure}</error>");
            return 1;
        }

        $oldMtproto = SchemaAuditCommand::loadArtifact("{$schemaDir}/methods-mtproto.json");
        $newMtproto = SchemaAuditCommand::loadArtifact("{$scratch}/methods-mtproto.json");
        $layer = $newMtproto['layer'] ?? 0;
        file_put_contents("{$scratch}/schema-manifest.json", json_encode(['layer' => $layer], JSON_PRETTY_PRINT) . PHP_EOL);

        $this->line(SchemaAuditCommand::buildReport(
            $oldMtproto,
            $newMtproto,
            SchemaAuditCommand::loadArtifact("{$schemaDir}/methods-botapi.json"),
            SchemaAuditCommand::loadArtifact("{$scratch}/methods-botapi.json"),
        ));
        $this->line("<info>stamped schema-manifest.json layer {$layer} (dry-run, repo untouched)</info>");
        self::cleanupScratch($scratch);

        return 0;
    }

    private function realRun(string $root, string $schemaDir): int
    {
        $oldMtproto = SchemaAuditCommand::loadArtifact("{$schemaDir}/methods-mtproto.json");
        $oldBotapi = SchemaAuditCommand::loadArtifact("{$schemaDir}/methods-botapi.json");

        foreach (SchemaAuditCommand::pipelineSteps() as $step) {
            $this->line('<info>running ' . $step['name'] . '</info>');
            $result = SchemaAuditCommand::runProcess([PHP_BINARY, $step['bin']], $root);
            if ($result['exit'] !== 0) {
                $this->line("<error>step [{$step['name']}] failed (exit {$result['exit']}): {$result['output']}</error>");
                return 1;
            }
        }

        $newMtproto = SchemaAuditCommand::loadArtifact("{$schemaDir}/methods-mtproto.json");
        $newBotapi = SchemaAuditCommand::loadArtifact("{$schemaDir}/methods-botapi.json");
        $layer = $newMtproto['layer'] ?? 0;
        file_put_contents("{$schemaDir}/schema-manifest.json", json_encode(['layer' => $layer], JSON_PRETTY_PRINT) . PHP_EOL);

        $this->line(SchemaAuditCommand::buildReport($oldMtproto, $newMtproto, $oldBotapi, $newBotapi));

        if (!SchemaAuditCommand::anyDifference(
            SchemaDiffer::diff($oldMtproto, $newMtproto),
            SchemaDiffer::diff($oldBotapi, $newBotapi),
        )) {
            $this->line('<info>Sources updated; artifacts unchanged; stamped layer ' . $layer . '.</info>');
        } else {
            $this->line('<comment>Sources updated; artifacts regenerated with differences above — review and commit them. Stamped layer ' . $layer . '.</comment>');
        }

        return 0;
    }

    private static function cleanupScratch(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($dir);
    }

    /**
     * @param list<array{0: array{url: string, dest: string}, 1: array{exit: int, output: string}}> $failures
     */
    private function reportFetchFailures(array $failures): void
    {
        $lines = ['Schema update aborted — ' . count($failures) . ' source fetch(es) failed:'];
        foreach ($failures as [$fetch, $result]) {
            $lines[] = "  - {$fetch['dest']} (exit {$result['exit']})";
            $lines[] = "    manual command: curl -fsSL {$fetch['url']} -o {$fetch['dest']}";
            if ($result['output'] !== '') {
                $lines[] = '    ' . $result['output'];
            }
        }
        $this->line('<error>' . implode("\n", $lines) . '</error>');
    }
}
