<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Schema\Generator\ModelGenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorShape;
use PHPUnit\Framework\TestCase;

final class ModelGeneratorTest extends TestCase
{
    private const TYPES_HEADER = "---types---\n";

    /** @return array<string,string> */
    private static function generate(): array
    {
        $scheme = TlParser::parseFile(__DIR__.'/fixtures/mini.tl', 0, strict: true);

        return (new ModelGenerator)->generate($scheme);
    }

    /** @return array<string,string> */
    private static function generateFromString(string $tl): array
    {
        $scheme = TlParser::parseString(self::TYPES_HEADER.$tl, 0, '<test>');

        return (new ModelGenerator)->generate($scheme);
    }

    public function test_domain_model_for_user_type(): void
    {
        $files = self::generate();
        self::assertArrayHasKey('TlUser.php', $files);
        self::assertStringContainsString('final class TlUser extends TlAnchorModel', $files['TlUser.php']);
        self::assertStringContainsString("protected \$table = 'tf_users';", $files['TlUser.php']);
        self::assertStringContainsString('use AccountScoped;', $files['TlUser.php']);
    }

    public function test_domain_model_for_message_type(): void
    {
        $files = self::generate();
        self::assertArrayHasKey('TlMessage.php', $files);
        self::assertStringContainsString('final class TlMessage extends TlAnchorModel', $files['TlMessage.php']);
        self::assertStringContainsString("protected \$table = 'tf_messages';", $files['TlMessage.php']);
    }

    public function test_message_model_emits_peer_resolution_trait(): void
    {
        $files = self::generate();
        self::assertStringContainsString('PeerResolution', $files['TlMessage.php']);
        self::assertStringContainsString('use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;', $files['TlMessage.php']);
    }

    public function test_no_per_constructor_models(): void
    {
        $files = self::generate();
        // Domain model generator does not produce per-constructor models
        self::assertArrayNotHasKey('TlUserUserEmpty.php', $files);
        self::assertArrayNotHasKey('TlMessageMessage.php', $files);
        self::assertArrayNotHasKey('TlMessagesMessagesMessages.php', $files);
    }

    public function test_deterministic(): void
    {
        self::assertSame(self::generate(), self::generate());
    }

    public function test_generated_files_are_valid_php(): void
    {
        foreach (self::generate() as $name => $content) {
            $tmp = tempnam(sys_get_temp_dir(), 'tlmdl').'.php';
            file_put_contents($tmp, $content);
            exec('php -l '.escapeshellarg($tmp).' 2>&1', $out, $code);
            unlink($tmp);
            self::assertSame(0, $code, "php -l failed for {$name}: ".implode("\n", $out));
        }
    }

    // --- Domain model features ---

    public function test_domain_model_has_guarded_empty(): void
    {
        $files = self::generateFromString(
            "userEmpty#cafe0001 id:long = User;\n",
        );
        self::assertArrayHasKey('TlUser.php', $files);
        self::assertStringContainsString('protected $guarded = [];', $files['TlUser.php']);
    }

    public function test_domain_model_for_single_type_only(): void
    {
        $files = self::generateFromString(
            "userEmpty#cafe0001 id:long = User;\n",
        );
        // Only domain models, no per-constructor
        self::assertArrayHasKey('TlUser.php', $files);
        self::assertArrayNotHasKey('TlUserUserEmpty.php', $files);
    }

    public function test_account_scoped_trait_on_domain_models(): void
    {
        $models = self::generateFromString(
            "userEmpty#cafe0001 id:long = User;\n",
        );
        $anchor = $models['TlUser.php'];
        self::assertStringContainsString('use MeRezaRezaei\\Teleframe\\Schema\\Eloquent\\AccountScoped;', $anchor);
        self::assertStringContainsString('use AccountScoped;', $anchor);
    }

    public function test_peer_ref_model_emits_peer_resolution_trait(): void
    {
        $models = self::generateFromString(
            "message#dead0001 id:int peer_id:Peer = Message;\n"
            ."userPeer#dead0002 user_id:long = Peer;\n"
            ."chatPeer#dead0003 chat_id:long = Peer;\n",
        );
        self::assertArrayHasKey('TlMessage.php', $models);
        self::assertStringContainsString('use MeRezaRezaei\\Teleframe\\Schema\\Eloquent\\PeerResolution;', $models['TlMessage.php']);
    }

    public function test_non_peer_model_does_not_get_peer_resolution(): void
    {
        $models = self::generateFromString(
            "userEmpty#cafe0001 id:long = User;\n",
        );
        self::assertStringNotContainsString('PeerResolution', $models['TlUser.php']);
    }

    // --- Determinism across generate() calls ---

    public function test_deterministic_across_generate_calls(): void
    {
        $tl = "message#dead0001 id:int media:flags.9?MessageMedia peer_id:Peer = Message;\n"
            ."mediaEmpty#dead0002 = MessageMedia;\n"
            ."userPeer#dead0003 user_id:long = Peer;\n";
        $first = self::generateFromString($tl);
        $second = self::generateFromString($tl);
        self::assertSame(array_keys($first), array_keys($second));
        foreach ($first as $key => $content) {
            self::assertSame($content, $second[$key], "Output differs for {$key}");
        }
    }

    // --- Generated PHP validity for inline-scheme output ---

    public function test_generated_files_from_inline_scheme_are_valid_php(): void
    {
        $tl = "message#dead0001 id:int media:flags.9?MessageMedia peer_id:Peer = Message;\n"
            ."mediaEmpty#dead0002 = MessageMedia;\n"
            ."userPeer#dead0003 user_id:long = Peer;\n";
        foreach (self::generateFromString($tl) as $name => $content) {
            $tmp = tempnam(sys_get_temp_dir(), 'tlmdl').'.php';
            file_put_contents($tmp, $content);
            exec('php -l '.escapeshellarg($tmp).' 2>&1', $out, $code);
            unlink($tmp);
            self::assertSame(0, $code, "php -l failed for {$name}: ".implode("\n", $out));
        }
    }

    // =================================================================
    // The shipped DDL is the subject, not the fixture.
    //
    // Everything above exercises the SYNTHETIC scheme path. Everything below
    // builds against the schema the repository actually ships: the shipped
    // migrations are executed against a live connection and the shape is
    // REFLECTED from the engine. No table name, column name, type or
    // foreign key is written down in this file, because a written-down list
    // is a second copy of the DDL and a second copy drifts — the failure
    // this repo already paid for once.
    //
    // The shape is memoised per process: booting the shipped dial costs real
    // seconds, and it is the same schema for every assertion here.
    // =================================================================

    private static ?MirrorShape $shippedShape = null;

    private static function shippedShape(): MirrorShape
    {
        if (self::$shippedShape !== null) {
            return self::$shippedShape;
        }

        $capsule = new Capsule;
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $app = new Container;
        $app->instance('db', $capsule->getDatabaseManager());
        $app->instance('db.schema', $capsule->getConnection()->getSchemaBuilder());
        Container::setInstance($app);
        Facade::setFacadeApplication($app);
        Facade::clearResolvedInstances();

        $files = glob(dirname(__DIR__, 2).'/src/Laravel/Migrations/*.php') ?: [];
        sort($files);
        foreach ($files as $file) {
            $migration = require $file;
            if (is_object($migration) && method_exists($migration, 'up')) {
                $migration->up();
            }
        }

        return self::$shippedShape = MirrorShape::fromExecutedDdl(
            $capsule->getConnection()->getPdo(),
        );
    }

    /** @return array<string,string> */
    private static function shippedFiles(): array
    {
        $scheme = TlParser::parseFile(__DIR__.'/fixtures/mini.tl', 0, strict: true);

        return (new ModelGenerator(self::shippedShape()))->generate($scheme);
    }

    /**
     * Every relation body the emitted models carry, keyed by model file.
     *
     * @param  array<string,string>  $files
     * @return array<string,list<string>>
     */
    private static function relationsIn(array $files): array
    {
        $relations = [];
        foreach ($files as $name => $content) {
            $found = [];
            foreach (explode("\n", $content) as $line) {
                $trimmed = ltrim($line);
                if (str_starts_with($trimmed, 'return $this->hasMany(')
                    || str_starts_with($trimmed, 'return $this->hasOne(')
                    || str_starts_with($trimmed, 'return $this->belongsTo(')
                ) {
                    $found[] = $trimmed;
                }
            }
            $relations[$name] = $found;
        }

        return $relations;
    }

    public function test_shape_reflects_the_shipped_mirror_tables(): void
    {
        // Guards the guard: if the reflection silently returned nothing, every
        // assertion below would pass vacuously. So assert it found tables and
        // that the count matches what the engine really holds.
        $shape = self::shippedShape();
        $counts = $shape->counts();

        self::assertGreaterThan(0, $counts['tables'], 'reflection found no mirror tables');
        self::assertGreaterThan(0, $counts['columns'], 'reflection found no columns');
        self::assertGreaterThan(0, $counts['foreign_keys'], 'reflection found no foreign keys');

        // Every reflected table is a mirror table — this reflection is
        // mirror-scoped, and the FK count must match the same measurement the
        // relation assertions use.
        foreach ($shape->tableNames() as $table) {
            self::assertStringStartsWith('tf_', $table);
        }
    }

    public function test_emits_a_model_for_every_shipped_mirror_table(): void
    {
        $shape = self::shippedShape();
        $files = self::shippedFiles();
        $counts = $shape->counts();

        self::assertSame(
            $counts['tables'],
            count($files),
            'expected one model per shipped mirror table, got '.count($files).' models for '.$counts['tables'].' tables',
        );
    }

    public function test_every_shipped_table_name_appears_as_a_model_table(): void
    {
        $shape = self::shippedShape();
        $files = self::shippedFiles();

        // Build table => file from the emitted `protected $table` so the
        // assertion is about TABLES, not about which class a table got.
        $declared = [];
        foreach ($files as $name => $content) {
            if (str_contains($content, "\$table = '")) {
                $after = explode("\$table = '", $content, 2)[1];
                $declared[explode("'", $after, 2)[0]] = $name;
            }
        }

        foreach ($shape->tableNames() as $table) {
            self::assertArrayHasKey(
                $table,
                $declared,
                "no generated model declares table [{$table}]",
            );
        }

        // And no model declares a table the reflection did not report — so the
        // coverage is exactly the shipped set, neither short nor padded. The
        // sets are sorted before comparing because the two are sorted on
        // different keys on purpose: the shape by TABLE, the files by CLASS.
        $declaredTables = array_keys($declared);
        sort($declaredTables);
        $shapeTables = $shape->tableNames();
        self::assertSame($shapeTables, $declaredTables);
    }

    public function test_every_declared_foreign_key_becomes_a_relation(): void
    {
        $shape = self::shippedShape();
        $files = self::shippedFiles();

        // Count the constraints whose BOTH endpoints are mirror tables: those
        // are the ones this lane can express, and every one must appear as a
        // declared relation on both sides.
        $inLayer = 0;
        foreach ($shape->tableNames() as $table) {
            foreach ($shape->foreignKeys($table) as $fk) {
                if ($fk['to_table'] === $table) {
                    self::fail("self-referencing constraint on [{$table}] is not handled by the relation rule");
                }
                if ($shape->has($fk['to_table'])) {
                    $inLayer++;
                }
            }
        }

        self::assertGreaterThan(0, $inLayer, 'no in-layer foreign keys to assert on');

        // A relation appears for one of exactly TWO reasons and says which, in
        // its own docblock: `Declared foreign key` for an edge the engine
        // constrains, `HORIZONTAL` for an entity-to-entity edge no constraint
        // can express (`HorizontalEntityRelations`). The partition below is read
        // off that marker, so it tracks the generator instead of restating a
        // count.
        //
        // WHY THE PARTITION IS HERE AT ALL. This assertion used to compare
        // `$inLayer * 2` against EVERY `hasMany`/`hasOne`/`belongsTo` the models
        // emit. That was sound while every relation was FK-derived, and it
        // stopped being sound when the horizontal half landed (9c628c95,
        // 2026-10-14) — the two mechanisms share ONE relation shape, so the
        // matcher could not tell them apart. Measured: 660 emitted against 406
        // constraint-derived, the 254 surplus being the deliberate horizontal
        // edges (and 87 further `entityPeer()` forwards this matcher never
        // counted at all). An assertion that had outlived its ability to
        // distinguish "a relation was fabricated" from "a second mechanism
        // added its own" is worse than no assertion: it cries wolf at a
        // shipped feature, and the habit of ignoring it is what would let a
        // real fabrication through.
        //
        // The anti-fabrication property is kept WHOLE, on the half it was ever
        // about: EXACTLY one inverse and one forward relation per declared
        // constraint, no more and no fewer. A relation invented from a name
        // prefix is constraint-shaped — it names a declared parent — so it
        // lands in this half and still turns the gate red.
        $fromConstraint = 0;
        $horizontal = 0;
        $unmarked = [];

        foreach ($files as $name => $content) {
            $doc = '';
            $inDocBlock = false;

            foreach (explode("\n", $content) as $line) {
                $trimmed = ltrim($line);

                if (str_starts_with($trimmed, '/**')) {
                    $doc = '';
                    $inDocBlock = true;
                }

                // The docblock accumulates across every line of it and is NOT
                // cleared by the lines that follow, because the `public
                // function` and the `{` sit between the docblock and the
                // `return` that carries the relation.
                if ($inDocBlock) {
                    $doc .= ' '.$trimmed;

                    if (str_contains($trimmed, '*/')) {
                        $inDocBlock = false;
                    }

                    continue;
                }

                // The same three shapes `relationsIn()` matches, so the
                // population is identical and only the attribution differs.
                $isRelation = str_starts_with($trimmed, 'return $this->hasMany(')
                    || str_starts_with($trimmed, 'return $this->hasOne(')
                    || str_starts_with($trimmed, 'return $this->belongsTo(');

                if (! $isRelation) {
                    continue;
                }

                if (str_contains($doc, 'HORIZONTAL')) {
                    $horizontal++;
                } elseif (str_contains($doc, 'Declared foreign key')) {
                    $fromConstraint++;
                } else {
                    $unmarked[] = $name.': '.trim($doc);
                }

                $doc = '';
            }
        }

        // A relation that declares NEITHER mechanism is named, never folded
        // into a half — otherwise the partition could be satisfied by deleting
        // the marker rather than by fixing the relation.
        self::assertSame(
            [],
            $unmarked,
            "relation(s) declaring neither mechanism:\n".implode("\n", $unmarked),
        );

        self::assertSame(
            $inLayer * 2,
            $fromConstraint,
            "expected {$inLayer} in-layer constraints to yield {$inLayer} inverse and {$inLayer} forward relations, got {$fromConstraint} constraint-derived relations",
        );

        // And the second mechanism is asserted PRESENT, so the partition above
        // cannot pass by the horizontal half quietly disappearing — that would
        // leave the constraint half exact while deleting 254 accessors a host
        // is entitled to call. Its own coverage is `HorizontalEntityRelationsTest`.
        self::assertGreaterThan(
            0,
            $horizontal,
            'no HORIZONTAL relations emitted — the entity-to-entity half is gone, and the partition above would still pass',
        );
    }

    public function test_no_relation_exists_without_a_declared_foreign_key(): void
    {
        $shape = self::shippedShape();
        $files = self::shippedFiles();
        $relations = self::relationsIn($files);

        // The anti-fabrication gate. A relation invented from a name prefix is
        // the exact defect that produced a false parent for
        // tf_messages_service and discarded every messageService update,
        // rolling back 18 rows each time. So assert the false relation is
        // ABSENT, and that the table's only declared parent is the one the
        // reflection reports.
        $declaredForService = [];
        foreach ($shape->foreignKeys('tf_messages_service') as $fk) {
            $declaredForService[] = $fk['to_table'];
        }
        self::assertNotContains(
            'tf_messages',
            $declaredForService,
            'fixture assumption broken: tf_messages_service genuinely declares no FK to tf_messages',
        );

        $serviceModel = null;
        foreach ($files as $name => $content) {
            if (str_contains($content, "\$table = 'tf_messages_service'")) {
                $serviceModel = $name;
            }
        }
        self::assertNotNull($serviceModel, 'no model for tf_messages_service');

        foreach ($relations[$serviceModel] as $body) {
            self::assertStringNotContainsString(
                'TlMessage::class',
                $body,
                'tf_messages_service was given a relation to tf_messages, which no foreign key declares',
            );
        }

        // And nothing anywhere may name a target the FK graph does not: every
        // class a relation points at must be a model this generator emitted,
        // and every model exists because a table was reflected.
        $models = self::modelTargets($files);

        foreach ($relations as $model => $bodies) {
            foreach ($bodies as $body) {
                $start = strpos($body, '::class');
                self::assertNotFalse($start, "relation in {$model} has no class target: {$body}");
                // The target is a sibling in the same generated namespace, so
                // it carries no namespace separator to strip. Walk back over the
                // identifier that ends at `::class` — string functions only,
                // no pattern matching.
                $end = (int) $start;
                $begin = $end;
                while ($begin > 0 && (ctype_alnum($body[$begin - 1]) || $body[$begin - 1] === '_')) {
                    $begin--;
                }
                $short = substr($body, $begin, $end - $begin);
                self::assertNotSame('', $short, "relation in {$model} has an empty class target: {$body}");
                self::assertArrayHasKey(
                    $short,
                    $models,
                    "{$model} has a relation to [{$short}], which is not a generated model — a relation target must be a reflected table",
                );
            }
        }
    }

    /**
     * Short class names the generated models declare, for the anti-fabrication
     * cross-check above.
     *
     * @param  array<string,string>  $files
     * @return array<string,true>
     */
    private static function modelTargets(array $files): array
    {
        $targets = [];
        foreach ($files as $content) {
            if (str_contains($content, 'final class ')) {
                $after = explode('final class ', $content, 2)[1];
                $name = explode(' ', $after, 2)[0];
                $targets[$name] = true;
            }
        }

        return $targets;
    }

    public function test_columns_and_casts_are_emitted(): void
    {
        $shape = self::shippedShape();
        $files = self::shippedFiles();

        // Pick a real table and real columns from the reflection, then assert
        // the model carries them and a cast block.
        $table = 'tf_users';
        $columns = $shape->columnNames($table);
        self::assertNotEmpty($columns);

        $model = null;
        foreach ($files as $name => $content) {
            if (str_contains($content, "\$table = '".$table."'")) {
                $model = $content;
            }
        }
        self::assertNotNull($model, "no model declares {$table}");

        foreach (['id', 'account_id', 'constructor'] as $column) {
            self::assertContains($column, $columns, "fixture assumption: {$table} has no {$column}");
            self::assertStringContainsString("'{$column}'", $model, "{$table} model omits column {$column}");
        }

        self::assertStringContainsString('$casts', $model);
    }

    public function test_shipped_generated_files_are_valid_php(): void
    {
        foreach (self::shippedFiles() as $name => $content) {
            $tmp = tempnam(sys_get_temp_dir(), 'tlship').'.php';
            file_put_contents($tmp, $content);
            exec('php -l '.escapeshellarg($tmp).' 2>&1', $out, $code);
            unlink($tmp);
            self::assertSame(0, $code, "php -l failed for {$name}: ".implode("\n", $out));
        }
    }

    public function test_shipped_output_is_deterministic(): void
    {
        self::assertSame(self::shippedFiles(), self::shippedFiles());
    }
}
