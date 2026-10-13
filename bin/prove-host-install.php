#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * The "does it install and boot in a real host app" gate.
 *
 * The owner's stated definition of done for Version One is that this package
 * integrates into ANOTHER project. That was proved once by hand and thrown
 * away, which left the two fixes it produced (binding BotClient so the Notify
 * facade resolves on a bare install; publishing config under config/teleframe/
 * and stopping teleframe:doctor hanging non-interactively) pinned by NOTHING.
 * The provider set then drifted — SupervisorServiceProvider was added after
 * that manual run, so its auto-discovery was never once exercised.
 *
 * This script makes the proof a gate. It builds a THROWAWAY laravel/laravel
 * host in a temp dir, installs this package into it as a path repository, and
 * asserts the onboarding path end to end:
 *
 *   - all six providers are auto-discovered by a real PackageManifest;
 *   - every teleframe vendor:publish tag lands its config file;
 *   - teleframe:doctor terminates (does not hang) and fails loudly unconfigured;
 *   - `php artisan list` carries every package command;
 *   - all six facades resolve, are callable, THROW on first real transport
 *     use, and never silently drop.
 *
 * It also asserts that this repository's `git status --porcelain` is
 * byte-identical before and after — the run must not touch the package tree.
 * The temp host is removed on every exit path, including a crash.
 *
 * EXIT CODES (deliberately distinct — a gate that cannot say WHY it failed is
 * a gate people learn to skip):
 *   0  clean tree: the package installs and boots in a fresh host
 *   1  a real regression: an assertion below failed
 *   2  usage / precondition: not run from the package root, or no composer
 *   3  the host build could not reach the network (clear message, not a
 *      silent pass and not a confusing composer stack trace)
 *   4  the run MODIFIED this repository (git status drifted)
 *
 * Network is required: installing Laravel means resolving packages. This
 * script adds no Composer dependency of its own and shells out to the host's
 * own composer. It never prints a credential, session string, auth key, token
 * or passphrase — every value it writes is obviously fake by construction
 * (there are none; the host runs deliberately UNCONFIGURED).
 *
 * Run from the repo root:  php bin/prove-host-install.php
 */
const EXIT_OK = 0;
const EXIT_FAILED = 1;
const EXIT_USAGE = 2;
const EXIT_NETWORK = 3;
const EXIT_DIRTY_REPO = 4;

/** Obviously-fake values only. Nothing here is a credential. */
const FAKE_API_ID = '0';
const FAKE_API_HASH = 'not-a-real-api-hash';

/** @var list<string> the six providers composer.json must auto-discover. */
const EXPECTED_PROVIDERS = [
    'MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider',
    'MeRezaRezaei\Teleframe\Laravel\NotifyServiceProvider',
    'MeRezaRezaei\Teleframe\Laravel\StoreBackupServiceProvider',
    'MeRezaRezaei\Teleframe\Laravel\AppServiceProvider',
    'MeRezaRezaei\Teleframe\Laravel\PassportServiceProvider',
    'MeRezaRezaei\Teleframe\Laravel\SupervisorServiceProvider',
];

/**
 * publish tag => the file it must land. Every tag is asserted; a provider that
 * loses its publishes() call fails the gate rather than quietly publishing
 * nothing.
 *
 * @var array<string, string>
 */
const EXPECTED_PUBLISHES = [
    'teleframe-config' => 'config/teleframe.php',
    'teleframe-notify-config' => 'config/teleframe/notify.php',
    'teleframe-store-backup-config' => 'config/teleframe/store/backup.php',
    'teleframe-app-config' => 'config/teleframe/app.php',
    'teleframe-passport-config' => 'config/teleframe/passport.php',
];

/**
 * The tag a host reaches for first, and EVERY config file it must land.
 *
 * The measured friction this exists for: the package ships N config files but
 * published them one-per-tag, so a host that published only the obvious
 * `teleframe-config` tag got one file and silently inherited package defaults
 * for the rest — no error, no warning. This list is the assertion that the
 * umbrella tag covers all of them, and the count is asserted below rather than
 * trusted.
 *
 * @var array<string, string> host-relative config path => the config key that
 *                            file must visibly define (proves it is real config, not a stub).
 */
const EXPECTED_CONFIG_FILES = [
    'config/teleframe.php' => 'api_id',
    'config/teleframe/notify.php' => 'queue',
    'config/teleframe/passport.php' => 'enabled',
    'config/teleframe/app.php' => 'apps',
    'config/teleframe/store/backup.php' => 'database',
];

/** The umbrella tag. One command must publish every file in EXPECTED_CONFIG_FILES. */
const UMBRELLA_TAG = 'teleframe-config';

/**
 * @var list<string> every command the package must contribute to `artisan list`.
 *
 * The check is a `str_contains` over the `php artisan list` output, so every
 * entry here is assertable on a fresh host: a command the providers register
 * appears in the list, and one that is not registered does not. Nothing in this
 * list needs a credential, a broker, Redis or a Telegram account.
 */
const EXPECTED_COMMANDS = [
    'teleframe:app',
    'teleframe:backfill',
    'teleframe:backup',
    'teleframe:daemon',
    'teleframe:doctor',
    'teleframe:ingest',
    'teleframe:ingest:gate',
    'teleframe:login',
    // Added 2026-09-27: registered by TeleframeServiceProvider but never
    // gated, so dropping it from the $this->commands([...]) list was
    // invisible to this gate.
    'teleframe:notify',
    'teleframe:passport',
    'teleframe:poll',
    'teleframe:regenerate',
    'teleframe:relay',
    // Same: the routing write path is the only way a host can turn act_on on.
    'teleframe:routing',
    'teleframe:schema-audit',
    'teleframe:schema-update',
    'teleframe:supervise',
    'teleframe:tstore',
    'teleframe:vault-add-account',
    'teleframe:vault-add-app',
    'teleframe:vault-list',
    'teleframe:vault-use-default',
];

// ---------------------------------------------------------------------------
// tiny process + filesystem helpers (no new dependency, no symfony/process)
// ---------------------------------------------------------------------------

/**
 * Run a command with a hard timeout. Returns the exit code, both streams and
 * whether the timeout fired — the doctor hang is a real failure mode, so the
 * timeout must be observable, not a silent "0".
 *
 * @param  list<string>  $argv
 * @param  array<string, string>  $env
 * @return array{code: int, out: string, err: string, timedOut: bool, seconds: float}
 */
function proveRun(array $argv, ?string $cwd = null, int $timeoutSec = 300, array $env = []): array
{
    $started = microtime(true);

    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open($argv, $descriptors, $pipes, $cwd, $env === [] ? null : $env);
    if (! is_resource($process)) {
        return ['code' => 127, 'out' => '', 'err' => 'proc_open failed', 'timedOut' => false, 'seconds' => 0.0];
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $out = '';
    $err = '';
    $timedOut = false;
    $code = 0;

    while (true) {
        $out .= (string) stream_get_contents($pipes[1]);
        $err .= (string) stream_get_contents($pipes[2]);

        $status = proc_get_status($process);
        if (! $status['running']) {
            $code = (int) $status['exitcode'];
            break;
        }

        if ((microtime(true) - $started) > $timeoutSec) {
            $timedOut = true;
            // SIGTERM then SIGKILL: a hung doctor must not wedge the gate.
            proc_terminate($process, 15);
            usleep(200000);
            $status = proc_get_status($process);
            if ($status['running']) {
                proc_terminate($process, 9);
            }
            break;
        }

        usleep(20000);
    }

    $out .= (string) stream_get_contents($pipes[1]);
    $err .= (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    return [
        'code' => $code,
        'out' => $out,
        'err' => $err,
        'timedOut' => $timedOut,
        'seconds' => microtime(true) - $started,
    ];
}

/**
 * @param  array{code: int, out: string, err: string, timedOut: bool, seconds: float}  $r
 */
function proveTail(array $r, int $lines = 6): string
{
    if ($r['timedOut']) {
        return 'TIMED OUT';
    }
    $blob = trim($r['err']."\n".$r['out']);
    if ($blob === '') {
        return '(no output)';
    }
    $all = preg_split('/\R/', $blob) ?: [];
    $all = array_values(array_filter($all, static fn (string $l): bool => trim($l) !== ''));

    return implode(' / ', array_slice($all, -$lines));
}

/** Recursive delete used ONLY on the temp dir this script created. */
function proveRmTree(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($items as $item) {
        /** @var SplFileInfo $item */
        if ($item->isDir() && ! $item->isLink()) {
            @rmdir($item->getPathname());
        } else {
            @unlink($item->getPathname());
        }
    }
    @rmdir($dir);
}

function proveLine(string $s = ''): void
{
    fwrite(STDOUT, $s."\n");
}

function proveStep(string $s): void
{
    proveLine('  … '.$s);
}

// ---------------------------------------------------------------------------
// phase 0 — preconditions. These exit with their OWN code, never 1: a machine
// without network must not look like a package regression, and vice versa.
// ---------------------------------------------------------------------------

$repoRoot = dirname(__DIR__);

if (basename($repoRoot) === 'bin' || ! is_file($repoRoot.'/composer.json') || ! is_dir($repoRoot.'/src')) {
    proveLine('prove-host-install: run this from the teleframe package root (found '.$repoRoot.').');
    exit(EXIT_USAGE);
}

$composer = trim((string) shell_exec('command -v composer 2>/dev/null'));
if ($composer === '') {
    proveLine('prove-host-install: composer is not on PATH; the host build needs it. Nothing was proven.');
    exit(EXIT_USAGE);
}

// The git tree must be byte-identical before and after. Snapshot the EXACT
// bytes `git status --porcelain` prints, and assert it again at the end.
$statusBefore = (string) shell_exec('cd '.escapeshellarg($repoRoot).' && git status --porcelain 2>/dev/null');

// A path repository is only honest if composer can reach packagist for the
// host's own dependencies. Probe it directly so an offline machine gets a
// clear, dedicated exit code instead of a composer stack trace.
$socketError = 0;
$socket = @fsockopen('ssl://repo.packagist.org', 443, $socketError, $socketMessage, 10);
if ($socket === false) {
    proveLine('prove-host-install: cannot reach repo.packagist.org:443 — '.(string) $socketMessage);
    proveLine('  Building a real Laravel host needs the network to install Laravel itself.');
    proveLine('  This is NOT a pass and NOT a package regression: run it where the network works,');
    proveLine('  or pre-warm the composer cache. Nothing was proven.');
    exit(EXIT_NETWORK);
}
fclose($socket);
proveLine('prove-host-install: network reachable (repo.packagist.org:443).');

// ---------------------------------------------------------------------------
// phase 1 — build the throwaway host
// ---------------------------------------------------------------------------

$tempRoot = getenv('TELEFRAME_HOST_TMPDIR') ?: sys_get_temp_dir();
$host = rtrim($tempRoot, '/').'/teleframe-host-'.getmypid().'-'.bin2hex(random_bytes(4));
$keepHost = getenv('TELEFRAME_HOST_KEEP') === '1';

proveLine();
proveLine('teleframe host-install proof');
proveLine('  package: '.$repoRoot);
proveLine('  host:    '.$host);
proveLine();

// Registered BEFORE anything can fail, so a fatal, an uncaught throw or a
// SIGINT still takes the 40 MB skeleton with it.
$cleanup = static function () use ($host, $keepHost): void {
    if ($keepHost) {
        proveLine('  (TELEFRAME_HOST_KEEP=1 — host left at '.$host.')');

        return;
    }
    if (is_dir($host)) {
        proveRmTree($host);
    }
};
register_shutdown_function($cleanup);

$mk = static fn (string $dir): bool => @mkdir($dir, 0777, true) && is_dir($dir);
if (! $mk($host)) {
    proveLine('prove-host-install: cannot create the temp host dir at '.$host);
    exit(EXIT_USAGE);
}

proveStep('composer create-project laravel/laravel (no scripts, no install)');
$create = proveRun([$composer, 'create-project', 'laravel/laravel', $host, '--no-interaction', '--prefer-dist', '--no-scripts', '--no-install'], null, 600);
if ($create['code'] !== 0 || ! is_file($host.'/artisan')) {
    proveLine('  ✗ could not create the Laravel host skeleton');
    proveLine('    '.proveTail($create, 10));
    exit(EXIT_FAILED);
}

// Point the host at THIS tree as a path repository and require the package.
// symlink:true means the host runs the live working tree, so an edit to a
// provider here is exercised in the host, not a stale copy.
$hostComposer = $host.'/composer.json';
$manifest = json_decode((string) file_get_contents($hostComposer), true);
if (! is_array($manifest)) {
    proveLine('  ✗ the host composer.json is not readable JSON');
    exit(EXIT_FAILED);
}
$manifest['repositories'] = [[
    'type' => 'path',
    'url' => $repoRoot,
    'options' => ['symlink' => true, 'versions' => ['merezarezaei/teleframe' => 'dev-hostproof']],
]];
$manifest['require']['merezarezaei/teleframe'] = '*@dev';
file_put_contents($hostComposer, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

// A bare .env + a real sqlite file: the host is deliberately UNCONFIGURED for
// teleframe (no TELEFRAME_API_ID, no token, no session). That is the state
// the facade contract is written about.
copy($host.'/.env.example', $host.'/.env');
if (! is_file($host.'/database/database.sqlite')) {
    @touch($host.'/database/database.sqlite');
}

proveStep('composer update (installs Laravel + this package, runs package:discover)');
$update = proveRun([$composer, 'update', '--no-interaction', '--prefer-dist', '--no-progress'], $host, 900);
if ($update['code'] !== 0) {
    if (! $create['timedOut'] && preg_match('/network|connection|resolve host|curl error 6|could not resolve/i', $update['err'].$update['out']) === 1) {
        proveLine('  ✗ the host build could not fetch its dependencies.');
        proveLine('    '.proveTail($update, 6));
        proveLine('  Nothing was proven — this looks like no network, not a package regression.');
        exit(EXIT_NETWORK);
    }
    proveLine('  ✗ composer update failed in the host');
    proveLine('    '.proveTail($update, 12));
    exit(EXIT_FAILED);
}

$artisan = static fn (array $args, int $timeout = 120): array => proveRun(
    array_merge([PHP_BINARY, 'artisan'], $args),
    $host,
    $timeout,
);

$keygen = $artisan(['key:generate', '--force', '--no-interaction']);
if ($keygen['code'] !== 0) {
    proveLine('  ✗ php artisan key:generate failed');
    proveLine('    '.proveTail($keygen));
    exit(EXIT_FAILED);
}

$discover = $artisan(['package:discover', '--ansi', '--no-interaction']);
if ($discover['code'] !== 0) {
    proveLine('  ✗ php artisan package:discover failed — the package does not boot in a host');
    proveLine('    '.proveTail($discover, 12));
    exit(EXIT_FAILED);
}

// Migrations are a REQUIRED onboarding step (friction #6), so the gate runs
// them: probing a never-migrated host would only prove that any package using
// Cache throws SQLSTATE, which is not what this gate is for.
proveStep('php artisan migrate --force (the host must migrate — friction #6)');
$migrate = $artisan(['migrate', '--force', '--no-interaction'], 900);
if ($migrate['code'] !== 0) {
    proveLine('  ✗ php artisan migrate failed in the host — the package ships migrations that do not run');
    proveLine('    '.proveTail($migrate, 14));
    exit(EXIT_FAILED);
}

proveLine('  host built; package discovered, booted and migrated.');
proveLine();

// ---------------------------------------------------------------------------
// phase 2 — the facade probe. Written INTO the host (never into this repo) so
// it boots the host's own application, through the host's own alias loader.
// ---------------------------------------------------------------------------

$probePath = $host.'/teleframe-host-probe.php';
file_put_contents($probePath, <<<'PHP'
<?php

declare(strict_types=1);

/**
 * Runs INSIDE the throwaway host. Boots the host's real application (which is
 * what exercises PackageManifest auto-discovery and the package alias loader),
 * then probes all six facades against the package's own documented contract:
 *
 *   resolves  -> app(<accessor>) yields the expected class
 *   callable  -> the documented safe call returns, and does not throw
 *   transport -> the first REAL transport call THROWS, naming the fix
 *   no-drop   -> a returning call is a FAILURE, not a quiet success
 *
 * Emits one ##PROBE##<json> line. Prints no credential-shaped value: the host
 * is unconfigured, so every message it can surface is a "not configured"
 * message.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\ServiceProvider;

$root = __DIR__;
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/** @var array<string, array<string, mixed>> $out */
$out = [];

/** Describe a return value without ever rendering a credential. */
function describe(mixed $v): string
{
    if (is_scalar($v) || $v === null) {
        return get_debug_type($v);
    }
    if (is_array($v)) {
        return 'array('.count($v).')';
    }
    if (is_object($v)) {
        return $v::class;
    }

    return get_debug_type($v);
}

/** A call that MUST return: throws here is a fatal-when-unconfigured bug. */
function mustReturn(callable $fn): array
{
    try {
        return ['ok' => true, 'value' => describe($fn())];
    } catch (Throwable $e) {
        return ['ok' => false, 'why' => 'threw instead of returning', 'error' => $e::class, 'message' => $e->getMessage()];
    }
}

/**
 * A call that MUST throw, with a message naming the fix.
 *
 * A facade that no-ops quietly is the failure mode that matters, so a
 * successful return is a failure here — and so is a BindingResolutionException
 * (the facade not resolving at all), because that message will not contain the
 * needle.
 */
function mustThrow(callable $fn, string $needle): array
{
    try {
        $value = $fn();
    } catch (Throwable $e) {
        if (! str_contains($e->getMessage(), $needle)) {
            return ['ok' => false, 'why' => 'threw the wrong thing', 'error' => $e::class, 'message' => $e->getMessage(), 'wanted' => $needle];
        }

        return ['ok' => true, 'error' => $e::class];
    }

    return ['ok' => false, 'why' => 'RETURNED INSTEAD OF THROWING (silent drop)', 'value' => describe($value), 'wanted' => $needle];
}

/** @return array{aliases: array<string, bool>} */
function aliasesExist(array $names): array
{
    $out = [];
    foreach ($names as $n) {
        $out[$n] = class_exists($n);
    }

    return $out;
}

// --- 0. the host really is unconfigured (else we proved nothing) ----------
$out['host-is-unconfigured'] = [
    'ok' => (int) config('teleframe.api_id') === 0
        && (string) config('teleframe.api_hash') === ''
        && (string) config('teleframe.bot_token') === '',
    'value' => 'api_id='.((int) config('teleframe.api_id')).' bot_token='.((string) config('teleframe.bot_token') === '' ? '(empty)' : '(set)'),
];

// --- 1. Teleframe / TF ----------------------------------------------------
$tf = 'TF';
$out['Teleframe/TF'] = [
    'aliases' => aliasesExist(['TF', 'Teleframe']),
    'resolves' => mustReturn(static fn () => app(MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient::class)),
    'callable' => mustReturn(static fn () => $tf::schemaLayer()),
    'transport-user' => mustThrow(static fn () => $tf::user(), 'API ID and API Hash are required'),
    'transport-bot' => mustThrow(static fn () => $tf::bot(), 'Bot Token is required'),
    'compose-face' => mustReturn(static fn () => $tf::onMessage(static function (): void {})),
];

// --- 2. TStore ------------------------------------------------------------
$ts = 'TStore';
$out['TStore'] = [
    'aliases' => aliasesExist(['TStore']),
    'resolves' => mustReturn(static fn () => app(MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand::SERVICE_KEY)),
    'callable' => mustReturn(static fn () => $ts::buckets()),
    'transport-put' => mustThrow(
        static function () use ($ts): void {
            $source = tempnam(sys_get_temp_dir(), 'tf-probe-');
            file_put_contents((string) $source, 'obviously-fake-probe-bytes');
            try {
                $ts::put('probe-bucket', 'probe-name', $source);
            } finally {
                @unlink((string) $source);
            }
        },
        'teleframe.tstore.account_id',
    ),
];

// --- 3. Notify ------------------------------------------------------------
$nt = 'Notify';
$out['Notify'] = [
    'aliases' => aliasesExist(['Notify']),
    // Resolution is the regression the manual install run found: without the
    // BotClient bind, NotificationSender's non-nullable string $botToken cannot
    // be autowired and getFacadeRoot() fatals on EVERY install.
    'resolves' => mustReturn(static fn () => app(MeRezaRezaei\Teleframe\Notify\NotifyCommand::SERVICE_KEY)),
    // The transport send. Must throw on the CALLER's thread — a job id back is
    // a silent drop.
    'transport-log' => mustThrow(
        static fn () => $nt::log(MeRezaRezaei\Teleframe\Notify\Level::Error, 'host-install probe'),
        'no target chat',
    ),
    'queue-driver' => describe(config('queue.default')),
];

// --- 4. Passport ----------------------------------------------------------
$pp = 'Passport';
$out['Passport'] = [
    'aliases' => aliasesExist(['Passport']),
    'resolves' => mustReturn(static fn () => app(MeRezaRezaei\Teleframe\Passport\PassportSupport::SERVICE_KEY)),
    'callable' => mustReturn(static fn () => $pp::issueNonce(1)),
    'service' => mustReturn(static fn () => $pp::service()),
    'transport-login' => mustThrow(static fn () => $pp::login(['_' => 'probe'], 1), 'passport login not enabled'),
];

// --- 5. TelegramApp -------------------------------------------------------
$ta = 'TelegramApp';
$out['TelegramApp'] = [
    'aliases' => aliasesExist(['TelegramApp']),
    'resolves' => mustReturn(static fn () => app(MeRezaRezaei\Teleframe\App\AppSupport::SERVICE_KEY)),
    'callable' => mustReturn(static fn () => $ta::list()),
    'define' => mustReturn(static fn () => $ta::define('host-probe-app', MeRezaRezaei\Teleframe\App\AppMode::User)),
    'transport' => mustThrow(static fn () => $ta::transport(), 'app transport: cannot resolve'),
];

// --- 6. StoreBackup -------------------------------------------------------
$sb = 'StoreBackup';
$out['StoreBackup'] = [
    'aliases' => aliasesExist(['StoreBackup']),
    'resolves' => mustReturn(static fn () => app(MeRezaRezaei\Teleframe\Store\Backup\BackupSupport::SERVICE_KEY)),
    'callable' => mustReturn(static fn () => $sb::runs()),
    'service' => mustReturn(static fn () => $sb::service()),
    'schedule' => mustReturn(static fn () => $sb::schedule()),
    'transport-backup' => mustThrow(static fn () => $sb::backup(), 'no database driver configured'),
];

// --- 7. the publish surface -------------------------------------------------
// What the package actually registers, read from the HOST's booted ServiceProvider
// registry rather than from this script's expectations. The parent cross-checks
// it against EXPECTED_CONFIG_FILES and against the config files on disk.
$publishGroups = [];
foreach (ServiceProvider::publishableGroups() as $group) {
    $paths = ServiceProvider::pathsToPublish(null, $group);
    if (! is_array($paths)) {
        continue;
    }
    // array_keys(), NOT array_values(): the publish map is source => target, and
    // this gate compares against the config files on DISK, which are sources.
    // Reading the values here compares source files against host target paths and
    // silently misses every file whose published name differs from its source name.
    $publishGroups[$group] = array_keys($paths);
}
$out['publish-surface'] = ['ok' => true, 'value' => 'groups='.count($publishGroups), 'groups' => $publishGroups];

echo '##PROBE##'.json_encode($out, JSON_UNESCAPED_SLASHES)."\n";
PHP);

$probeRun = proveRun([PHP_BINARY, $probePath], $host, 180);
$probeJson = null;
foreach (array_reverse(preg_split('/\R/', $probeRun['out'].$probeRun['err']) ?: []) as $line) {
    if (str_starts_with(trim($line), '##PROBE##')) {
        $probeJson = json_decode(substr(trim($line), strlen('##PROBE##')), true);
        break;
    }
}

if (! is_array($probeJson)) {
    proveLine('  ✗ the facade probe did not report (the host fataled while booting the package?)');
    proveLine('    '.proveTail($probeRun, 14));
    exit(EXIT_FAILED);
}

/** Format one probe step for the ✓/✗ table, with the reason on failure. */
$step = static function (array $step): string {
    if (($step['ok'] ?? false) === true) {
        return '';
    }
    $why = (string) ($step['why'] ?? 'threw');
    $detail = (string) ($step['message'] ?? $step['value'] ?? '');

    return $why.($detail !== '' ? ': '.$detail : '');
};

/** Build one "facade X" check from its probe block. */
$facadeCheck = static function (string $key) use ($probeJson, $step): callable {
    return static function () use ($probeJson, $step, $key): bool {
        $block = $probeJson[$key] ?? null;
        if (! is_array($block)) {
            printf("  ✗ %s — the probe never reached this facade\n", $key);
            $GLOBALS['proveFailures'][] = $key.': not probed';

            return false;
        }

        $ok = true;
        $reasons = [];

        foreach (($block['aliases'] ?? []) as $alias => $exists) {
            if ($exists !== true) {
                $ok = false;
                $reasons[] = 'alias '.$alias.' is not registered by the host alias loader';
            }
        }

        foreach ($block as $name => $result) {
            if ($name === 'aliases' || ! is_array($result) || ! array_key_exists('ok', $result)) {
                continue; // informational (queue-driver) or not a verdict
            }
            if ($result['ok'] !== true) {
                $ok = false;
                $reasons[] = $name.' → '.$step($result);
            }
        }

        if ($reasons !== []) {
            printf("  ✗ %s — %s\n", $key, implode(' | ', $reasons));
            $GLOBALS['proveFailures'][] = $key.': '.implode(' | ', $reasons);
        }

        return $ok;
    };
};

// ---------------------------------------------------------------------------
// phase 3 — the checks
// ---------------------------------------------------------------------------

$proveFailures = [];
$checks = [];

// Auto-discovery, read from the HOST's own package manifest.
$checks['all six providers are auto-discovered in the host manifest'] = static function () use ($host, &$proveFailures): bool {
    $manifestPath = $host.'/bootstrap/cache/packages.php';
    if (! is_file($manifestPath)) {
        $proveFailures[] = 'packages.php manifest missing — package:discover never ran';

        return false;
    }
    $manifest = require $manifestPath;
    $discovered = $manifest['merezarezaei/teleframe']['providers'] ?? [];
    $missing = array_values(array_diff(EXPECTED_PROVIDERS, $discovered));
    if ($missing !== []) {
        $proveFailures[] = 'not auto-discovered: '.implode(', ', array_map(static fn (string $p): string => substr($p, strrpos($p, '\\') + 1), $missing));

        return false;
    }

    return true;
};

// Every declared provider class must actually exist in the package tree.
$checks['every composer.json provider class exists in src/'] = static function () use ($repoRoot, &$proveFailures): bool {
    $composer = json_decode((string) file_get_contents($repoRoot.'/composer.json'), true);
    $declared = $composer['extra']['laravel']['providers'] ?? [];
    $missing = [];
    foreach ($declared as $class) {
        $relative = str_replace('MeRezaRezaei\\Teleframe\\', '', $class);
        $relative = 'src/'.str_replace('\\', '/', $relative).'.php';
        if (! is_file($repoRoot.'/'.$relative)) {
            $missing[] = $class;
        }
    }
    if ($missing !== []) {
        $proveFailures[] = 'declared but absent: '.implode(', ', $missing);

        return false;
    }
    // And it must be the six we gate on — a seventh declared provider is drift
    // this gate has not seen, not a reason to fail.
    $undeclaredHere = array_values(array_diff(EXPECTED_PROVIDERS, $declared));

    return $undeclaredHere === [];
};

// The six aliases must all be declared in composer.json.
$checks['all six facade aliases are declared in composer.json'] = static function () use ($repoRoot, &$proveFailures): bool {
    $composer = json_decode((string) file_get_contents($repoRoot.'/composer.json'), true);
    $aliases = $composer['extra']['laravel']['aliases'] ?? [];
    foreach (['TF', 'Teleframe', 'TStore', 'Notify', 'StoreBackup', 'TelegramApp', 'Passport'] as $alias) {
        if (! isset($aliases[$alias])) {
            $proveFailures[] = 'alias '.$alias.' is not declared';

            return false;
        }
    }

    return true;
};

// vendor:publish must LAND config, for every tag, into the host.
$checks['every vendor:publish tag lands its config file'] = static function () use ($host, $artisan, &$proveFailures): bool {
    $ok = true;
    foreach (EXPECTED_PUBLISHES as $tag => $relative) {
        $target = $host.'/'.$relative;
        if (is_file($target)) {
            @unlink($target);
        }
        $r = $artisan(['vendor:publish', '--tag='.$tag, '--force', '--no-interaction']);
        if ($r['code'] !== 0 || ! is_file($target)) {
            $ok = false;
            $proveFailures[] = sprintf('tag %s did not land %s (exit %d) %s', $tag, $relative, $r['code'], proveTail($r, 2));
        }
    }

    return $ok;
};

/**
 * THE UMBRELLA GATE. One command must publish every config file the package
 * ships.
 *
 * This is the assertion, not the count: the targets are deleted first, the
 * single umbrella tag is published ONCE, and every file must land. A tag that
 * quietly covers four of five files fails here instead of being discovered by a
 * host that never knew to publish the fifth.
 */
$checks[sprintf('publishing the umbrella tag "%s" lands ALL %d config files', UMBRELLA_TAG, count(EXPECTED_CONFIG_FILES))] = static function () use ($host, $artisan, &$proveFailures): bool {
    // Delete EVERY expected target so a file left over from an earlier check's
    // publish cannot be mistaken for this check's work.
    foreach (array_keys(EXPECTED_CONFIG_FILES) as $relative) {
        $target = $host.'/'.$relative;
        if (is_file($target)) {
            @unlink($target);
        }
    }

    $r = $artisan(['vendor:publish', '--tag='.UMBRELLA_TAG, '--force', '--no-interaction']);
    if ($r['code'] !== 0) {
        $proveFailures[] = 'umbrella publish failed (exit '.$r['code'].'): '.proveTail($r, 3);

        return false;
    }

    $missing = [];
    foreach (array_keys(EXPECTED_CONFIG_FILES) as $relative) {
        if (! is_file($host.'/'.$relative)) {
            $missing[] = $relative;
        }
    }

    if ($missing !== []) {
        $proveFailures[] = sprintf(
            'the umbrella tag "%s" did NOT land %d of %d config files — missing: %s',
            UMBRELLA_TAG,
            count($missing),
            count(EXPECTED_CONFIG_FILES),
            implode(', ', $missing),
        );

        return false;
    }

    return true;
};

/**
 * No config file may be UNREACHABLE by publishing. The tag list is a hand-kept
 * expectation and can fall behind the tree; this reads the config directory in
 * THIS repository and the publish groups the host actually booted, and fails if
 * a file exists on disk that no tag publishes.
 *
 * Direction matters and both are asserted: a file missing from the umbrella is
 * caught by the check above, and a file missing from EVERY tag is caught here.
 */
$checks['no config file is unreferenced by any publish tag'] = static function () use ($repoRoot, $probeJson, &$proveFailures): bool {
    $onDisk = glob($repoRoot.'/src/Laravel/config/*.php') ?: [];
    if ($onDisk === []) {
        $proveFailures[] = 'no config files found in src/Laravel/config/ — the scan found nothing to check';

        return false;
    }

    $groups = $probeJson['publish-surface']['groups'] ?? null;
    if (! is_array($groups) || $groups === []) {
        $proveFailures[] = 'the host reported no publish groups at all — nothing can be asserted';

        return false;
    }

    // Every published SOURCE, from every group, reduced to its basename so the
    // host's absolute package paths can be compared with this repo's disk.
    $published = [];
    foreach ($groups as $paths) {
        foreach ((array) $paths as $from) {
            if (is_string($from)) {
                $published[basename($from)] = true;
            }
        }
    }

    $orphans = [];
    foreach ($onDisk as $file) {
        if (! isset($published[basename($file)])) {
            $orphans[] = basename($file);
        }
    }

    if ($orphans !== []) {
        sort($orphans);
        $proveFailures[] = 'config file(s) that NO publish tag publishes: '.implode(', ', $orphans);

        return false;
    }

    return true;
};

/**
 * The published config must be REAL config, and the host must still boot with
 * it in place — a published file that shadows the provider's mergeConfigFrom
 * and breaks the boot is the failure mode here, not a missing key.
 */
$checks['the host still boots with the published config in place'] = static function () use ($host, $artisan, &$proveFailures): bool {
    // Driven by EXPECTED_CONFIG_FILES, not by a second hand-written list: the
    // duplication is what let the tag list and the key list drift apart before.
    foreach (EXPECTED_CONFIG_FILES as $relative => $key) {
        $file = $host.'/'.$relative;
        $contents = (string) @file_get_contents($file);
        if ($contents === '' || ! str_contains($contents, "'".$key."'")) {
            $proveFailures[] = $relative.' does not define '.$key;

            return false;
        }
    }

    $clear = $artisan(['config:clear', '--no-interaction']);
    if ($clear['code'] !== 0) {
        $proveFailures[] = 'config:clear failed with the published config in place: '.proveTail($clear, 3);

        return false;
    }

    $about = $artisan(['about', '--only=environment', '--no-interaction']);
    if ($about['code'] !== 0) {
        $proveFailures[] = 'the host no longer boots once config is published: '.proveTail($about, 5);

        return false;
    }

    return true;
};

// doctor must TERMINATE and fail loudly, not hang, non-interactively.
$checks['teleframe:doctor terminates and fails loudly non-interactive'] = static function () use ($artisan, &$proveFailures): bool {
    $r = $artisan(['teleframe:doctor', '--no-interaction'], 60);
    if ($r['timedOut']) {
        $proveFailures[] = 'teleframe:doctor HUNG in a non-interactive run (the defect the manual run fixed)';

        return false;
    }
    $blob = $r['out'].$r['err'];
    if ($r['code'] === 0) {
        $proveFailures[] = 'teleframe:doctor exited 0 with no credentials — it should fail loudly';

        return false;
    }
    if (! str_contains($blob, 'TELEGRAM_API_ID')) {
        $proveFailures[] = 'teleframe:doctor did not name the missing config: '.proveTail($r, 3);

        return false;
    }
    if ($r['seconds'] > 30) {
        $proveFailures[] = sprintf('teleframe:doctor took %.1fs unconfigured — it is doing work it should refuse', $r['seconds']);

        return false;
    }

    return true;
};

// Every package command must reach `artisan list`.
$checks['php artisan list carries every teleframe command'] = static function () use ($artisan, &$proveFailures): bool {
    $r = $artisan(['list', '--no-interaction']);
    if ($r['code'] !== 0) {
        $proveFailures[] = 'php artisan list exited '.$r['code'].': '.proveTail($r, 3);

        return false;
    }
    $missing = [];
    foreach (EXPECTED_COMMANDS as $command) {
        if (! str_contains($r['out'], $command)) {
            $missing[] = $command;
        }
    }
    if ($missing !== []) {
        $proveFailures[] = 'missing from artisan list: '.implode(', ', $missing);

        return false;
    }

    return true;
};

// The host is genuinely unconfigured — otherwise the facade proofs above are
// testing nothing.
$checks['the probe host really is unconfigured'] = static function () use ($probeJson, &$proveFailures): bool {
    $block = $probeJson['host-is-unconfigured'] ?? null;
    if (! is_array($block) || $block['ok'] !== true) {
        $proveFailures[] = 'the host was NOT unconfigured — the facade proofs prove nothing';

        return false;
    }

    return true;
};

// Now the six facades.
foreach (['Teleframe/TF', 'TStore', 'Notify', 'Passport', 'TelegramApp', 'StoreBackup'] as $facade) {
    $checks[sprintf('%s resolves, is callable, and throws on first transport use (never drops)', $facade)] = $facadeCheck($facade);
}

$failed = false;
foreach ($checks as $label => $check) {
    $line = '';
    ob_start();
    $ok = false;
    try {
        $ok = $check();
    } catch (Throwable $e) {
        $line = sprintf('  ✗ %s — %s: %s', $label, $e::class, $e->getMessage());
        $proveFailures[] = $label.': '.$e::class;
    }
    $printed = (string) ob_get_clean();

    if ($line === '') {
        printf("%s %s\n", $ok ? '  ✓' : '  ✗', $label);
    } else {
        proveLine($line);
    }
    $failed = $failed || ! $ok;
}

// ---------------------------------------------------------------------------
// phase 4 — the repo must be untouched, and the friction list is the deliverable
// ---------------------------------------------------------------------------

$statusAfter = (string) shell_exec('cd '.escapeshellarg($repoRoot).' && git status --porcelain 2>/dev/null');
$repoDirty = $statusAfter !== $statusBefore;

// The NUMBERED INTEGRATOR-FRICTION LIST. This is the artefact the owner asked
// for ("friction nobody wrote down is friction the next integrator
// rediscovers"); docs/usage/host-install.md carries the committed copy.
proveLine();
proveLine('INTEGRATOR-FRICTION LIST (from this run)');
proveLine('  A host integrator hits these in this order. Every one is a step the');
proveLine('  integrator has to perform that the package does not do for them.');
proveLine();
$numbered = [
    'composer require the package — the only install step. Nothing else is needed for the boot to work.',
    'php artisan vendor:publish --tag=teleframe-config — ONE umbrella command that publishes every config file the package ships ('.count(EXPECTED_CONFIG_FILES).' in this tree; the check above asserts that each one actually lands). The per-file tags (teleframe-notify-config, teleframe-store-backup-config, teleframe-app-config, teleframe-passport-config) still work and are still asserted, for a host that wants to publish one file at a time.',
    'Set TELEGRAM_API_ID / TELEGRAM_API_HASH in .env before any MTProto call. Unconfigured, Teleframe::user() throws "Telegram API ID and API Hash are required" — the facade resolves, the call does not.',
    'Set TELEGRAM_BOT_TOKEN before Notify sends. Unconfigured, Notify resolves and the send throws — but only on the thread that actually sends (see the queue note below).',
    'Run teleframe:doctor as the first post-install check. It is the one command that tells you whether the host can reach Telegram, and it refuses fast (non-zero, no hang) when the API id/hash are unset.',
    'The host must run its own migrations for anything backed by the database (tstore index, backup run ledger, vault). The package ships its migrations in src/Laravel/Migrations; teleframe:* commands that touch those tables fail with a database error, not a friendly one, until migrate runs.',
    'Decide the queue driver for Notify. teleframe.notify.queue defaults to "auto", which takes the Laravel driver unless it is literally "sync" — so on a default QUEUE_CONNECTION=database host a Notify send is handed to the queue and the "unconfigured" throw happens in a worker, not at the call site.',
    'Bring up the bus (Redis) and run teleframe:ingest / teleframe:daemon for updates to arrive at all. Nothing polls on its own, and no facade call starts a listener.',
];
foreach ($numbered as $i => $text) {
    proveLine(sprintf('  %d. %s', $i + 1, $text));
}
if ($proveFailures !== []) {
    proveLine();
    proveLine('FAILURES (a regression, not a friction):');
    foreach ($proveFailures as $f) {
        proveLine('  ✗ '.$f);
    }
}

proveLine();
proveLine('NOT REACHED BY THIS GATE (stated so nobody reads a green run as more than it is):');
proveLine('  - a live Telegram connection: no account, session string, bot token or auth key is used anywhere;');
proveLine('  - a non-sqlite database: the host is the stock sqlite skeleton, so the Postgres and MySQL tracks are unproven here;');
proveLine('  - a queue worker actually running: Notify is asserted at the call boundary, not end-to-end through a worker;');
proveLine('  - npm/build assets, and the dashboard views, beyond the config publish above;');
proveLine('  - a second host, or a host with an existing queue/cache/redis configured.');

if ($repoDirty) {
    $before = $statusBefore === '' ? [] : explode("\n", $statusBefore);
    $after = $statusAfter === '' ? [] : explode("\n", $statusAfter);
    $added = array_values(array_diff($after, $before));
    $removed = array_values(array_diff($before, $after));

    proveLine();
    proveLine('  ✗ `git status --porcelain` CHANGED WHILE THIS GATE RAN (exit '.EXIT_DIRTY_REPO.').');
    proveLine('    This script writes only inside its own temp host, so a drift here is either');
    proveLine('    a bug in this script or ANOTHER PROCESS editing the package tree mid-run.');
    proveLine('    Either way a host install must not be able to touch this repository, so the');
    proveLine('    gate refuses to call the run a pass:');
    foreach ($removed as $line) {
        proveLine('      - '.$line);
    }
    foreach ($added as $line) {
        proveLine('      + '.$line);
    }
    if ($added === [] && $removed === []) {
        proveLine('      (the same set of lines in a different order)');
    }
    exit(EXIT_DIRTY_REPO);
}

$cleanup();
proveLine();
proveLine('  temp host removed.');
proveLine($failed ? 'RESULT: FAIL' : 'RESULT: PASS — the package installs and boots in a fresh Laravel host.');

exit($failed ? EXIT_FAILED : EXIT_OK);
