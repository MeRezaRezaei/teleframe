<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorMigrationWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactoryWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorModelWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;

final class TeleframeMirrorCommand extends Command
{
    protected $signature = 'teleframe:mirror
        {--stage=1 : 0=proof (5 parents) | 1=all | 2=+factories | 3=+union queries}
        {--out= : output dir (default = repo generated/)}';

    protected $description = 'Generate the NF5 relational mirror (migrations + models) from the committed catalog';

    private const STAGE0 = ['tf_users', 'tf_messages', 'tf_messages_service', 'tf_message_medias', 'tf_message_entities'];

    public function handle(): int
    {
        $out = $this->option('out') ?: base_path('generated');
        $tl  = base_path('schema/sources/TL_telegram_v227.tl');
        $catalogPath = base_path('docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json');

        $scheme  = TlParser::parseFile($tl);
        $catalog = MirrorCatalog::load($catalogPath, $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);

        $stage = (int) $this->option('stage');
        $parents = $stage === 0
            ? $resolver->resolveAll(self::STAGE0)
            : $resolver->resolveAll($catalog->tableNames());
        if ($parents === []) {
            $this->error('No mirror tables resolved.');
            return self::FAILURE;
        }

        $migs = (new MirrorMigrationWriter($out.'/migrations/mirror'))->writeAll($parents);
        $mods = (new MirrorModelWriter($out))->writeAll($parents);

        $facts = [];
        if ($stage >= 2) {
            $facts = (new MirrorFactoryWriter($out))->writeAll($parents);
        }

        $this->info(sprintf('Mirror generated: %d migrations, %d models, %d factories.', count($migs), count($mods), count($facts)));
        foreach ($parents as $p) {
            $this->line(sprintf("  %-28s (%d children)", $p->tfName, count($p->children)));
        }
        return self::SUCCESS;
    }
}
