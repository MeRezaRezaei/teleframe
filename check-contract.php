<?php

declare(strict_types=1);

// Why this exists: widening RedisConnectionContract with a method that a test
// double does not implement is a FATAL at composer-verify bootstrap, not a test
// failure — CI died on it with a 1-minute job and no test name. This loads the
// named implementers the way the autoloader does, so the same fatal surfaces
// locally in a second. The anonymous double in AccountLifecycleObserverTest is
// deliberately NOT checked here: it only exists inside a method body, and
// running that test is what constructs it.

require __DIR__ . '/vendor/autoload.php';

$contract = MeRezaRezaei\Teleframe\Bus\RedisConnectionContract::class;
$need = array_map(
    static fn (ReflectionMethod $m): string => $m->getName(),
    (new ReflectionClass($contract))->getMethods(),
);

$classes = [
    MeRezaRezaei\Teleframe\Bus\LaravelRedisAdapter::class,
    MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis::class,
];

// RecordingRedis is a SECOND class inside a test file, so composer skips it
// for PSR-4 (CI logs the warning) and it is only loadable once the file is
// included — which is exactly what PHPUnit's bootstrap does, and why the
// missing method surfaced as a boot-time fatal rather than a test failure.
$testFile = __DIR__ . '/tests/Ingest/MirrorIngesterFactoryRoutingCacheTest.php';
if (is_file($testFile)) {
    require_once $testFile;
    $source = (string) file_get_contents($testFile);
    if (preg_match('/namespace\s+([^;]+);/', $source, $ns) === 1) {
        $classes[] = trim($ns[1]) . '\\RecordingRedis';
    }
}

$bad = 0;

foreach ($classes as $class) {
    try {
        $reflection = new ReflectionClass($class);
    } catch (Throwable $e) {
        echo "REFLECT FAIL  {$class}: {$e->getMessage()}\n";
        $bad++;
        continue;
    }

    $missing = [];
    foreach ($need as $method) {
        if (! $reflection->hasMethod($method)) {
            $missing[] = $method;
        }
    }

    if ($missing !== []) {
        echo 'INCOMPLETE    ' . $class . ' missing: ' . implode(', ', $missing) . "\n";
        $bad++;
        continue;
    }

    echo "COMPLETE      {$class}\n";
}

echo 'incomplete=' . $bad . "\n";

exit($bad === 0 ? 0 : 1);
