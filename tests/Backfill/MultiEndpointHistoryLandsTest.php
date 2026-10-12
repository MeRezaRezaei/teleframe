<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backfill;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * PHASE 5, STEP 3 ON SQLITE — the multi-endpoint acceptance run.
 *
 * The cases live in `MultiEndpointHistoryLandsCases` so the MySQL lane runs the
 * identical assertions; this class supplies the SQLite seams only: an in-memory
 * database built by EXECUTING `UpdateIngestor::migrationPaths()`, and
 * `PRAGMA foreign_key_check` as the FK read.
 *
 * @see MultiEndpointHistoryLandsCases for the cases and the evidence behind them
 * @see \MeRezaRezaei\Teleframe\Tests\MySql\MultiEndpointHistoryLandsOnMysqlTest
 */
final class MultiEndpointHistoryLandsTest extends TestCase
{
    use MultiEndpointHistoryLandsCases;

    protected function db(): Connection
    {
        if (self::$capsule === null) {
            self::$capsule = $this->bootMigratedSurface();
        }

        $db = self::$capsule->getConnection();
        $this->seedSurface($db);

        return $db;
    }

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
                    continue;
                }

                $applied[$file] = true;
                (require $file)->up();
            }
        }

        $capsule->getConnection()->statement('PRAGMA foreign_keys = ON');

        return $capsule;
    }

    /**
     * FK violations, from the ENGINE rather than from a source scan.
     *
     * @return list<mixed>
     */
    protected function fkViolations(Connection $db): array
    {
        return $db->select('PRAGMA foreign_key_check');
    }
}
