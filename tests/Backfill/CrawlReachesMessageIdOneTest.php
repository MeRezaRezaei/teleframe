<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backfill;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use PHPUnit\Framework\TestCase;

/**
 * PHASE 5, STEP 2 ON SQLITE — the crawl to message id 1.
 *
 * The assertions live in `CrawlToMessageIdOneCases` so the MySQL lane runs the
 * identical cases; this class supplies the SQLite seams only: an in-memory
 * database built by EXECUTING `UpdateIngestor::migrationPaths()`, and
 * `PRAGMA foreign_key_check` as the FK read.
 *
 * WHY SQLITE IS STILL WORTH HAVING ALONGSIDE MySQL: it is the engine every
 * plain `composer verify` runs, so these cases are the ones that guard the
 * default lane. MySQL proves the same properties where the errors actually
 * exist.
 *
 * @see CrawlToMessageIdOneCases for the cases and the evidence behind them
 * @see \MeRezaRezaei\Teleframe\Tests\MySql\BackfillCrawlReachesMessageIdOneOnMysqlTest
 */
final class CrawlReachesMessageIdOneTest extends TestCase
{
    use CrawlToMessageIdOneCases;

    protected function db(): Connection
    {
        if (self::$capsule === null) {
            self::$capsule = $this->bootMigratedSurface();
        }

        $db = self::$capsule->getConnection();
        $this->seedSurface($db);

        return $db;
    }

    /**
     * The shipped dial, EXECUTED from `UpdateIngestor::migrationPaths()` — the
     * same list the ingest seam runs — plus nothing typed in.
     */
    protected function bootMigratedSurface(): Capsule
    {
        $capsule = new Capsule;
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $app = new Container;
        $app->instance('db', $capsule->getDatabaseManager());
        $app->instance('db.schema', $capsule->getConnection()->getSchemaBuilder());
        self::$previousContainer = Container::getInstance();
        Container::setInstance($app);
        Facade::setFacadeApplication($app);
        Facade::clearResolvedInstances();

        $applied = [];
        foreach (UpdateIngestor::migrationPaths() as $path) {
            foreach ((is_dir($path) ? glob($path.'/*.php') : [$path]) as $file) {
                if (isset($applied[$file])) {
                    continue; // the entity-anchor paths overlap the dial directory
                }

                $applied[$file] = true;
                (require $file)->up();
            }
        }

        $capsule->getConnection()->statement('PRAGMA foreign_keys = ON');

        return $capsule;
    }
}
