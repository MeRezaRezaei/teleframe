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
 *   - the MTProto CALL SURFACE both scopes reach (V1.4) answers from a host:
 *     the method catalogue and the TL registry, plus a constructible user
 *     scope AND bot scope, with nothing dialled and nothing configured.
 *   - Telegram Passport LOGIN (V1.5) runs the whole chain from a host that has
 *     CONFIGURED it: nonce issued, the credentials envelope through the shipped
 *     decryptor, the binding written to the host's own database, and Auth
 *     reflecting the login — in a second process, because the enabled posture
 *     cannot be reached in one that already resolved the service unconfigured.
 *     Still no api id, no hash, no token, no session string, no socket.
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
//
// TWO PROBES, because "can composer reach packagist" and "can a raw socket
// reach packagist" are not the same question. fsockopen() speaks to no proxy:
// on a host whose egress is an HTTP proxy (HTTPS_PROXY set), composer resolves
// and downloads through it while a raw TLS socket to the same host times out —
// so the single raw probe reported EXIT_NETWORK on a machine whose network was
// working fine, and the gate proved nothing about anything. The second probe
// asks the question composer will actually ask, over the same environment
// composer inherits.
//
// This makes the gate STRICTER, never looser: a machine must now satisfy one
// probe or the other, and a genuinely offline machine satisfies neither, so it
// still exits 3. Nothing here can turn a failure into a pass on its own — the
// host build below still has to succeed.
$socketError = 0;
$socketMessage = '';
$socket = @fsockopen('ssl://repo.packagist.org', 443, $socketError, $socketMessage, 10);
if ($socket !== false) {
    fclose($socket);
    proveLine('prove-host-install: network reachable (repo.packagist.org:443, direct).');
} else {
    // The proxy-aware retry. curl inherits HTTPS_PROXY from the environment,
    // exactly as composer does; a non-zero exit (or a missing curl) is not a
    // pass, it just means neither probe found a route.
    $proxyProbe = proveRun(
        ['curl', '-sS', '-o', '/dev/null', '--max-time', '25', 'https://repo.packagist.org/packages.json'],
        null,
        40,
    );
    if ($proxyProbe['code'] !== 0 || $proxyProbe['timedOut']) {
        proveLine('prove-host-install: cannot reach repo.packagist.org:443 — '.(string) $socketMessage);
        proveLine('  and the proxy-aware retry did not reach it either: '.proveTail($proxyProbe, 2));
        proveLine('  Building a real Laravel host needs the network to install Laravel itself.');
        proveLine('  This is NOT a pass and NOT a package regression: run it where the network works,');
        proveLine('  or pre-warm the composer cache. Nothing was proven.');
        exit(EXIT_NETWORK);
    }
    proveLine('prove-host-install: network reachable (repo.packagist.org:443, via the configured proxy).');
}

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

// The ASSERTION KIT. Shared by both probes below and written out once, because
// the second probe (V1.5, Passport ENABLED) must judge with the SAME predicates
// as the first — two copies of mustThrow() could drift, and a drifted predicate
// is a gate that reports a verdict it never computed. Nothing here is
// host-specific: the helpers are plain functions over a return value, so they
// are written once and required by whichever probe is running.
$probeKitPath = $host.'/teleframe-host-probe-kit.php';
file_put_contents($probeKitPath, <<<'PHP'
<?php

declare(strict_types=1);

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

/**
 * A predicate that MUST be true.
 *
 * The mirror of mustThrow, and the reason a capability section can FAIL rather
 * than merely report. mustReturn() cannot be reused here: a predicate that
 * answers `false` returns cleanly, so mustReturn() would report ok:true for a
 * dead capability — a gate nobody can fail is a gate nobody can trust.
 */
function mustBeTrue(callable $fn, string $what): array
{
    try {
        if ($fn() === true) {
            return ['ok' => true];
        }

        return ['ok' => false, 'why' => 'answered FALSE', 'value' => $what];
    } catch (Throwable $e) {
        return ['ok' => false, 'why' => 'threw instead of answering', 'error' => $e::class, 'message' => $e->getMessage()];
    }
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
PHP);

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

require_once $root.'/teleframe-host-probe-kit.php';

/** @var array<string, array<string, mixed>> $out */
$out = [];

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
    // THE FIRST REFUSAL MOVED, AND IT MOVED EARLIER — WHICH IS THE POINT.
    //
    // It used to be `no database driver configured`, thrown by the dumper resolver
    // inside the first real call. Since the service CLAIMS a run in the ledger
    // before it dumps (goal 46.87 item 4: a ledger INSERT failing AFTER the upload
    // left an orphaned remote copy with no local record), an UNCONFIGURED host is
    // now refused at the CLAIM — its account id is 0, no row claims it, and the
    // FOREIGN KEY refuses before any transport is touched.
    //
    // The contract this check exists to prove is unchanged and its boundary is
    // stricter: the facade throws loudly and never drops. What must NOT be pinned is
    // WHICH boundary, because pinning the later one would now assert a regression.
    // Both refusals are real; assert that it throws, and say what it threw by
    // category rather than by a message that moves when the code improves.
    'refuses-loudly-on-first-real-call' => mustThrow(
        static fn () => $sb::backup(),
        'the run ledger has no such account',
    ),
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

// --- 8. MTProto call surface (V1.4) ----------------------------------------
// The operator's own words: "this app must be able to do whatever telegram
// activity needed using mtproto call both for user and bot". A host proves
// that reachability two ways, with NO credential and NO socket:
//
//   (a) the METHOD SURFACE answers BY NAME. Methods and the TL registry are
//       plain static — no container, no transport — so the whole packaged
//       catalogue is askable from a host install, and a name outside the fluent
//       builder face is as callable as one inside it;
//   (b) BOTH scopes are CONSTRUCTIBLE. MTProtoClient defaults apiId=0 /
//       apiHash='' and SessionData is built from nothing, so a host obtains a
//       live UserAccountScope and a live BotAccountScope with no api id, no
//       hash, no token and no auth key.
//
// Constructing is not dialling: the socket opens on the first call(), and no
// check below makes one. That is the whole point — the surface is proven
// BEFORE the host has the credentials to use it with.
//
// The credential wall is not hidden, it is measured: the two entry points
// refuse BY NAME below (section 1 already covers user() and the HTTP bot()),
// and the wall is restated in the NOT REACHED block.
$uncurated = 'account.getWallPapers';
$described = MeRezaRezaei\Teleframe\Core\Methods\Methods::describe($uncurated);
$mtprotoProbe = static fn (): MeRezaRezaei\Teleframe\Core\MTProto\Client => new MeRezaRezaei\Teleframe\Core\MTProto\Client();
$mtprotoSession = static fn (): MeRezaRezaei\Teleframe\Core\MTProto\SessionData => new MeRezaRezaei\Teleframe\Core\MTProto\SessionData(dcId: 2, authKey: '');

$out['V1.4 mtproto-all'] = [
    // A curated name — the surface a host reaches on day one.
    'method-surface' => mustBeTrue(
        static fn (): bool => MeRezaRezaei\Teleframe\Core\Methods\Methods::isCallable('messages.sendMessage'),
        'Methods::isCallable(messages.sendMessage)',
    ),
    // A name the fluent builder face does NOT carry. This is "whatever
    // telegram activity needed": the ceiling is the packaged catalogue, not a
    // hand-kept list, so an uncurated method is callable today.
    'raw-name' => mustBeTrue(
        static fn (): bool => MeRezaRezaei\Teleframe\Core\Methods\Methods::isCallable($uncurated),
        'Methods::isCallable('.$uncurated.')',
    ),
    // The id is DERIVED, not invented: TLRegistry actually parsed the
    // constructor, and the id it holds is the one describe() reports.
    'signature-derived' => mustBeTrue(
        static function () use ($uncurated, $described): bool {
            $registry = 'MeRezaRezaei\\Teleframe\\Core\\MTProto\\TL\\TLRegistry';

            return $described !== null
                && $described['crc32'] !== 0
                && $registry::isRegistered($uncurated)
                && $registry::id($uncurated) === $described['crc32']
                && $registry::signature($uncurated) !== '';
        },
        'TLRegistry parsed '.$uncurated.' and its id is the one describe() reports',
    ),
    // A constructor TYPE is not a callable method — the boundary the docblock
    // names. Asserted so "reachable" cannot later widen to mean "known".
    'type-not-callable' => mustBeTrue(
        static fn (): bool => MeRezaRezaei\Teleframe\Core\Methods\Methods::isCallable('userProfilePhoto') === false,
        'Methods::isCallable(userProfilePhoto) is false — a type is not a method',
    ),
    // Both builder groups resolve from the host's autoloader: the mtproto one
    // and the bot one.
    'builder-groups' => mustBeTrue(
        static function (): bool {
            $methods = 'MeRezaRezaei\\Teleframe\\Core\\Methods\\Methods';

            return $methods::messages() instanceof MeRezaRezaei\Teleframe\Core\Methods\Generated\Messages
                && $methods::bots() instanceof MeRezaRezaei\Teleframe\Bot\Methods\Generated\Bots;
        },
        'Methods::messages() and Methods::bots() both return their generated group',
    ),
    // The two scopes, built from nothing.
    'user-scope' => mustReturn(
        static fn (): object => new MeRezaRezaei\Teleframe\Core\Services\UserAccountScope($mtprotoProbe(), $mtprotoSession()),
    ),
    'bot-scope' => mustReturn(
        static fn (): object => new MeRezaRezaei\Teleframe\Bot\Services\BotAccountScope($mtprotoProbe(), $mtprotoSession(), ''),
    ),
    // "both for user and bot" as ONE surface, not two implementations: the
    // bot scope inherits call(), so there is no second code path to drift.
    'scope-parity' => mustBeTrue(
        static function (): bool {
            $user = 'MeRezaRezaei\\Teleframe\\Core\\Services\\UserAccountScope';
            $bot = 'MeRezaRezaei\\Teleframe\\Bot\\Services\\BotAccountScope';

            return is_subclass_of($bot, $user)
                && (new ReflectionMethod($bot, 'call'))->getDeclaringClass()->getName() === $user
                && method_exists($user, 'call')
                && method_exists($bot, 'call');
        },
        'BotAccountScope extends UserAccountScope and inherits the same call()',
    ),
    // Both entry points exist on the container-resolved client a host holds.
    'entry-points' => mustBeTrue(
        static function (): bool {
            $client = app(MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient::class);

            return method_exists($client, 'user')
                && method_exists($client, 'botMtproto')
                && method_exists($client, 'dispatch');
        },
        'the resolved TeleframeClient exposes user(), botMtproto() and dispatch()',
    ),
    // The bot-over-MTProto refusal. Sections 1's transport-user/transport-bot
    // cover the user scope and the HTTP bot client; THIS is the entry point
    // V1.4's "both" hinges on, and it had no refusal assertion until now.
    'transport-bot-mtproto' => mustThrow(
        static fn () => $tf::botMtproto(),
        'Bot Token is required for MTProto bot authorization',
    ),
    // Informational, not a verdict — the two layer numbers are deliberately
    // reported under their own names and never blended.
    'layers' => 'wire='.(string) ($described['wire_layer'] ?? 0).' schema='.(string) ($described['schema_layer'] ?? 0),
];

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

// ---------------------------------------------------------------------------
// phase 2b — the V1.5 probe. The SAME host, a SECOND process, booted the way a
// host boots it ONCE Passport is configured.
//
// Section 4 above is not replaced and not weakened: `enabled=false` refusing
// `login()` by name is correct, and it stays asserted. It is also the whole of
// what section 4 proved, which is why the operator's own sentence for V1.5 —
// "it must be able to let laravel users login using it when one configures
// them" — had nothing behind it: every capability section stops at its first
// refusal, so the chain itself was never entered.
//
// IT IS A SEPARATE PROCESS, and that is the point rather than a workaround:
// `teleclient.passport.service` is a singleton and `PassportConfig` is a value
// object built from `teleframe.passport` when that singleton is FIRST resolved.
// Section 4 resolves it with `enabled=false`, and the key loader it pulls in
// captured `private_key_path=''`. Mutating config afterwards would be
// re-configuring objects that already captured the old values, which proves
// nothing about the posture a host actually gets. The gate exports the two
// `TELEFRAME_PASSPORT_*` variables a host puts in .env and this process boots
// with them — which is precisely what a restart after configuring does.
// ---------------------------------------------------------------------------

// The throwaway bot RSA key a configured host reads. Generated HERE, per run,
// written only inside the temp host, never printed and never committed: its
// only job is to let the SHIPPED PassportKeyLoader (0600 check and all) and the
// SHIPPED PassportDecryptor run for real. The PATH travels in the environment,
// which is what a host configures; the PEM never leaves the file.
$passportPem = $host.'/storage/framework/teleframe-host-probe-passport.pem';
$passportKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
$passportPrivate = '';
if ($passportKey === false || ! openssl_pkey_export($passportKey, $passportPrivate)) {
    proveLine('  ✗ openssl could not generate the throwaway passport keypair this probe needs');
    exit(EXIT_FAILED);
}
if (file_put_contents($passportPem, $passportPrivate) === false) {
    proveLine('  ✗ could not write the throwaway passport PEM into the host');
    exit(EXIT_FAILED);
}
// 0600, because PassportKeyLoader REFUSES a group/other-readable PEM — which
// makes the mode part of what is being proved, not a detail.
chmod($passportPem, 0o600);

$probePassportPath = $host.'/teleframe-host-passport-probe.php';
file_put_contents($probePassportPath, <<<'PHP'
<?php

declare(strict_types=1);

/**
 * Runs INSIDE the throwaway host, in a process of its own, booted with
 * teleframe.passport CONFIGURED — the two TELEFRAME_PASSPORT_* variables the
 * gate exported, which are exactly the two a host sets in .env.
 *
 * The operator's words for V1.5 (verbatim 2026-09-24:65-66): "next is telegram
 * passport it must be able to let laravel users login using it when one
 * configures them". The other probe proves the half of that sentence that reads
 * "when one has NOT configured them" — it stops at the `enabled=false` refusal.
 * This one ENTERS the chain behind that refusal and proves a Laravel user
 * really ends up logged in.
 *
 * NOTHING CREDENTIAL IS INVOLVED. The host still has no api id, no api hash,
 * no bot token, no user session string and no bot session string, and nothing
 * here dials a socket or an HTTP endpoint. The keypair that lets the real
 * decryptor run is generated per run, lives only in the temp host, and is
 * unlinked by the gate the moment this probe returns.
 *
 * Emits one ##PROBE##<json> line — the same shape the other probe emits, so the
 * parent judges both through one walker.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use MeRezaRezaei\Teleframe\Identity\TlUserBinding;
use MeRezaRezaei\Teleframe\Passport\LoginResult;
use MeRezaRezaei\Teleframe\Passport\MemoryPassportWire;
use MeRezaRezaei\Teleframe\Passport\PassportDocumentCoverage;
use MeRezaRezaei\Teleframe\Passport\PassportSupport;
use MeRezaRezaei\Teleframe\Passport\PassportWireContract;
use MeRezaRezaei\Teleframe\Tests\Passport\PassportElementFactory;
use MeRezaRezaei\Teleframe\Tests\Passport\SpecCredentialFactory;

$root = __DIR__;
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

require_once $root.'/teleframe-host-probe-kit.php';

/** @var array<string, array<string, mixed>> $out */
$out = [];

// The host's ALLOW-LIST: the shipped default plus one §5 document type, so the
// chain reaches a slot that has to go to Telegram for its bytes. A host edits
// `allowed_types` in its published config/teleframe/passport.php; the key has no
// env() driver in the shipped file, so it is set here through the same public
// config repository — BEFORE the service singleton is first resolved, which is
// the only moment it can still be read. Nothing private is touched and no guard
// is disabled.
config(['teleframe.passport.allowed_types' => ['personal_details', 'email', 'phone', 'passport']]);

// A real Laravel user in the HOST's own users table. `example.invalid` is
// reserved by RFC 2606 and can never resolve, so a row that outlived the temp
// host could not reach a mailbox; the password is random bytes and is never
// used, printed or stored anywhere but this throwaway row.
$userClass = (string) config('auth.providers.users.model');
$user = null;
if (class_exists($userClass) && is_subclass_of($userClass, Model::class)) {
    $user = $userClass::query()->create([
        'name' => 'Teleframe Passport Host Probe',
        'email' => 'passport-host-probe-'.bin2hex(random_bytes(4)).'@example.invalid',
        'password' => bcrypt(bin2hex(random_bytes(16))),
    ]);
}
$userId = $user instanceof Model ? (int) $user->getKey() : 0;

// A SECOND real user: the session that presents a nonce is not the session it
// was issued to. This is the account-takeover pair, and it is refused.
$otherUser = null;
if (class_exists($userClass) && is_subclass_of($userClass, Model::class)) {
    $otherUser = $userClass::query()->create([
        'name' => 'Teleframe Passport Host Probe (other session)',
        'email' => 'passport-host-probe-other-'.bin2hex(random_bytes(4)).'@example.invalid',
        'password' => bcrypt(bin2hex(random_bytes(16))),
    ]);
}
$otherUserId = $otherUser instanceof Model ? (int) $otherUser->getKey() : 0;

// The credential fixture is the SPEC-FAITHFUL factory this package's own suites
// seal with, REQUIRED rather than re-written here. Eight suites once each
// carried a private seal() built to fit the implementation, which is how a spec
// violation shipped green through CI; a second copy of the algorithm inside a
// gate would reproduce exactly that. One fixture, one home — and it is reported
// under its own name so a reader can see where the envelope came from.
$missingFixtures = [];
foreach (['SpecCredentialFactory', 'PassportElementFactory'] as $fixture) {
    $file = $root.'/vendor/merezarezaei/teleframe/tests/Passport/'.$fixture.'.php';
    if (is_file($file)) {
        require_once $file;
    } else {
        $missingFixtures[] = $fixture;
    }
}

// The host's in-memory wire is bound on the DOCUMENTED seam, and it is bound
// here — before anything resolves the service singleton — because that is the
// order a host's own provider has to use: `PassportLoginService` takes the wire
// in its constructor, so a rebind after the first resolve would be ignored.
// `teleclient.passport.wire` is a public bind key listed in
// `PassportSupport::seams()`, and `MemoryPassportWire` ships as the offline
// implementation of that same contract; `instance()` is how a host replaces any
// seam. No private is touched and no guard is disabled.
//
// Scripted, because an unscripted method refuses loudly by design — and a
// refusal would prove the seam is live but not that the chain can read what
// comes back over it. The answer is sealed against the SAME key the configured
// host reads, so the §4 FileCredentials on the secureFile really decrypt.
$pemPath = (string) config('teleframe.passport.private_key_path');
$privatePem = is_file($pemPath) ? (string) @file_get_contents($pemPath) : '';
$publicPem = '';
if ($privatePem !== '') {
    $loaded = openssl_pkey_get_private($privatePem);
    $details = $loaded === false ? false : openssl_pkey_get_details($loaded);
    $publicPem = is_array($details) && is_string($details['key'] ?? null) ? $details['key'] : '';
}
$factory = $missingFixtures === [] && $publicPem !== ''
    ? SpecCredentialFactory::withKeys($privatePem, $publicPem)
    : null;

/** @var MemoryPassportWire|null */
$wire = null;
/**
 * The §4 FileCredentials for the secureFile below, sealed ONCE. Both the wire's
 * scripted answer and the secureFile that asks for it have to come from the
 * SAME seal: sealing twice produces two different hashes, the file the wire
 * returns no longer matches the hash the envelope carries, and the §4 integrity
 * check refuses — correctly, and for a reason that has nothing to do with the
 * chain.
 *
 * @var array{content: string, hash: string, secret: string}|null
 */
$sealedFile = null;
if ($factory !== null) {
    $sealedFile = $factory->sealFileCredentials('obviously-fake-passport-bytes');
    $wire = new MemoryPassportWire([
        PassportWireContract::METHOD_GET_FILE => [
            ['_' => 'upload.file', 'mtime' => 0, 'bytes' => (string) $sealedFile['content']],
        ],
    ]);
    app()->instance(PassportSupport::WIRE_KEY, $wire);
}

/** The sender id every fixture in this probe speaks as — an obvious fake. */
const PROBE_TG_ID = 424242;

/** The second shape's sender, so its binding row is distinguishable. */
const PROBE_TG_ID_MTPROTO = 424243;

// --- the nonce, issued through the FACADE, for the real user --------------
$nonce = $factory !== null ? Passport::issueNonce($userId) : null;

/** A real Bot API update carrying a freshly sealed credentials envelope. */
$botApiUpdate = static function (string $nonce) use ($factory): array {
    $envelope = $factory->seal([
        // §5: `secure_data` carries the DOCUMENT types only. The verified email
        // is NOT here — §6 sends it as PassportData plaintext beside it.
        'secure_data' => ['personal_details' => ['first_name' => 'Probe', 'last_name' => 'Host']],
        'nonce' => $nonce,
    ]);

    return [
        'update_id' => 1,
        'message' => [
            'message_id' => 1,
            'from' => ['id' => PROBE_TG_ID, 'is_bot' => false],
            'passport_data' => PassportElementFactory::passportData($envelope, ['email' => 'probe@example.invalid']),
        ],
    ];
};

$update = $nonce !== null ? $botApiUpdate($nonce) : [];

// The session id BEFORE the login, so the ride's session regeneration is
// observable rather than assumed. Captured here because the login itself runs
// inside the first assertion below.
$sessionIdBefore = app('session.store')->getId();

/** @var LoginResult|null */
$loginResult = null;
/** @var LoginResult|null */
$mtprotoResult = null;

// --- 0. V1.5 — a Laravel user really logs in when a host configures Passport
$out['V1.5 passport-login'] = [
    // The premise. Every other credential this gate proves with is absent, so
    // the login below is a no-credential login and says so rather than implying
    // a Telegram account was involved.
    'no-credential' => mustBeTrue(
        static fn (): bool => (int) config('teleframe.api_id') === 0
            && (string) config('teleframe.api_hash') === ''
            && (string) config('teleframe.bot_token') === ''
            && (string) config('teleframe.user_session') === ''
            && (string) config('teleframe.bot_session') === '',
        'no api id, no api hash, no bot token, no user session, no bot session — the login below used none',
    ),
    // The configuration actually reached the booted application. Without this the
    // rest of the section could pass on a config() poke that never landed.
    'configured-enabled' => mustBeTrue(
        static fn (): bool => (bool) config('teleframe.passport.enabled') === true
            && (string) config('teleframe.passport.private_key_path') === (string) getenv('TELEFRAME_PASSPORT_PRIVATE_KEY'),
        'the two TELEFRAME_PASSPORT_* variables a host sets reached the booted config',
    ),
    // The fixture's provenance, so a reader knows the envelope was sealed by the
    // spec transcription and not by something invented next to the gate.
    'fixture-source' => mustBeTrue(
        static fn (): bool => $missingFixtures === []
            && class_exists(SpecCredentialFactory::class)
            && class_exists(PassportElementFactory::class),
        'the package ships SpecCredentialFactory + PassportElementFactory and the probe seals with those',
    ),
    // The nonce is issued and is a real one: 32 hexits off random_bytes, and a
    // second issue is never the same string (a uniqid/time nonce would be).
    'nonce-issued' => mustBeTrue(
        static fn (): bool => is_string($nonce)
            && strlen($nonce) === 32
            && ctype_xdigit($nonce)
            && Passport::issueNonce($userId) !== $nonce,
        'issueNonce() answers 32 hexits and never repeats one',
    ),
    // The chain ENTERS. mustReturn, not mustThrow: the point of this section is
    // that the documented call no longer refuses once a host configures it.
    'chain-entered' => mustReturn(static function () use (&$loginResult, $update, $userId): LoginResult {
        $loginResult = Passport::login($update, $userId);

        return $loginResult;
    }),
    // ...and what came out is the sealed response, not an empty success: the
    // sender id Telegram would have carried, the resolved host user, and the
    // decrypted §5 + §6 field TYPES (names only — never a value).
    'chain-result' => mustBeTrue(
        static fn (): bool => $loginResult instanceof LoginResult
            && $loginResult->tgUserId === PROBE_TG_ID
            && $loginResult->user->getKey() === $userId
            && $loginResult->user::class === $userClass
            && $loginResult->fieldsVerified === ['personal_details', 'email'],
        'the LoginResult carries the sealed sender id, the resolved host user, and the decrypted field TYPES',
    ),
    // The ONE Eloquent touchpoint, asserted against the HOST's own database
    // rather than against a returned object: a binding row that morphs to the
    // host user, for the telegram id the sender carried.
    'identity-bound' => mustBeTrue(
        static function () use (&$loginResult, $userId, $userClass): bool {
            $binding = TlUserBinding::query()->where('tl_user_id', PROBE_TG_ID)->first();

            return $userId > 0
                && $binding instanceof TlUserBinding
                && $binding->user_type === $userClass
                && (int) $binding->user_id === $userId
                && $loginResult?->binding instanceof TlUserBinding;
        },
        'a tl_user_bindings row in the host database morphs to the host user for the sender telegram id',
    ),
    // Provenance: "which of my users authenticated via Telegram, and with what
    // proof?" — TYPES only, which is what the column is specified to hold.
    'provenance-stamped' => mustBeTrue(
        static fn (): bool => ($binding = TlUserBinding::query()->where('tl_user_id', PROBE_TG_ID)->first()) instanceof TlUserBinding
            && $binding->passportFieldsVerified() === ['personal_details', 'email']
            && $binding->passport_verified_at !== null,
        'the binding row records the verified field TYPES and when the login completed',
    ),
    // "let laravel users login": the ride the provider installs when an auth
    // factory is bound actually ran, and Auth agrees.
    'auth-reflects-login' => mustBeTrue(
        static fn (): bool => $userId > 0 && Auth::check() === true && Auth::id() === $userId,
        'Auth::check() is true and Auth::id() is the passport user — the host-auth ride ran',
    ),
    // The session-fixation half of the ride: the id ROTATES on the way in.
    'session-rotated' => mustBeTrue(
        static fn (): bool => is_string($sessionIdBefore)
            && $sessionIdBefore !== ''
            && app('session.store')->getId() !== $sessionIdBefore,
        'the session id the login arrived on is not the id it left with',
    ),
    // §11 replay guard, from a host: the same envelope a second time is refused.
    'replay-refused' => mustThrow(
        static fn () => Passport::login($update, $userId),
        'unknown or expired passport nonce',
    ),
    // The account-takeover pair (5cbbde5b), from a host: a nonce issued to one
    // session and presented by another is refused BEFORE any binding.
    'foreign-session-refused' => mustThrow(
        static function () use ($factory, $botApiUpdate, $userId, $otherUserId): void {
            if ($factory === null || $otherUserId === 0) {
                throw new RuntimeException('the takeover fixture could not be built — nothing was proven');
            }
            Passport::login($botApiUpdate(Passport::issueNonce($userId)), $otherUserId);
        },
        'issued to a different session than the one presenting it',
    ),
    // §4's document table, on a REAL login: every allowed_types value has a row,
    // and the §6 verified email is a real document rather than a named absence.
    'document-table' => mustBeTrue(
        static function () use (&$loginResult): bool {
            $coverage = $loginResult?->documents;
            if (! $coverage instanceof PassportDocumentCoverage) {
                return false;
            }
            $verified = [];
            foreach ($coverage->toArray()['rows'] as $row) {
                if (($row['type'] ?? '') === 'email' && ($row['outcome'] ?? '') === 'document') {
                    $verified[] = (string) ($row['slot'] ?? '');
                }
            }

            return $coverage->isComplete() && $verified === ['plain_data'];
        },
        'every allowed_types value has a document row, and the verified email row is a real document',
    ),
    // The HOST-SUPPLIED WIRE SEAM, in the chain. `teleclient.passport.wire` is a
    // documented bind key and MemoryPassportWire ships as the offline
    // implementation of the same contract; the probe binds it the way a host
    // binds any seam (container instance, no private touched), and the §1
    // MTProto shape then makes the chain actually USE it.
    'mtproto-chain-entered' => mustReturn(
        static function () use (&$mtprotoResult, $factory, $sealedFile, $userId): LoginResult {
            $mtprotoResult = Passport::login([
                'update_id' => 2,
                '_' => 'updateNewMessage',
                'message' => [
                    '_' => 'messageService',
                    'id' => 2,
                    'from_id' => ['_' => 'peerUser', 'user_id' => PROBE_TG_ID_MTPROTO],
                    'peer_id' => ['_' => 'peerUser', 'user_id' => PROBE_TG_ID_MTPROTO],
                    'action' => [
                        '_' => 'messageActionSecureValuesSentMe',
                        'values' => [[
                            '_' => 'secureValue',
                            'type' => ['_' => 'secureValueTypePassport'],
                            'hash' => random_bytes(16),
                            'front_side' => [
                                '_' => 'secureFile',
                                'id' => 4242,
                                'access_hash' => 777,
                                'size' => strlen('obviously-fake-passport-bytes'),
                                'dc_id' => 2,
                                'date' => 1700000000,
                                'file_hash' => (string) $sealedFile['hash'],
                                'secret' => (string) $sealedFile['secret'],
                            ],
                        ]],
                        'credentials' => $factory->seal([
                            'secure_data' => ['personal_details' => ['first_name' => 'Probe', 'last_name' => 'Host']],
                            'nonce' => Passport::issueNonce($userId),
                        ]),
                    ],
                ],
            ], $userId);

            return $mtprotoResult;
        },
    ),
    // ...and the wire is what made it work: `upload.getFile` really left on the
    // seam the host bound, addressed by the official `inputSecureFileLocation`,
    // and the front_side row came back as a DECRYPTED document — the §4
    // FileCredentials through the shipped decryptor, not a stub.
    'wire-document-decrypted' => mustBeTrue(
        static function () use (&$mtprotoResult): bool {
            $wire = app(PassportSupport::WIRE_KEY);
            if (! $wire instanceof MemoryPassportWire) {
                return false;
            }
            $dispatch = $wire->firstDispatchOf(PassportWireContract::METHOD_GET_FILE);
            if (! is_array($dispatch)) {
                return false;
            }
            $location = $dispatch['params']['location'] ?? [];
            $row = null;
            foreach ($mtprotoResult?->documents?->toArray()['rows'] ?? [] as $candidate) {
                if (($candidate['type'] ?? '') === 'passport' && ($candidate['slot'] ?? '') === 'front_side') {
                    $row = $candidate;
                }
            }

            return is_array($row)
                && ($row['outcome'] ?? '') === 'document'
                && ($row['payload_class'] ?? '') === 'File'
                && ($location['_'] ?? '') === 'inputSecureFileLocation'
                && (int) ($location['id'] ?? 0) === 4242
                && (int) ($location['access_hash'] ?? 0) === 777;
        },
        'upload.getFile left on the host-bound wire as an inputSecureFileLocation and the file decrypted to a document',
    ),
    // Informational, not a verdict — the posture this run actually booted with,
    // and which seams answered, reported under their own names and never blended.
    'configured' => 'enabled='.var_export((bool) config('teleframe.passport.enabled'), true)
        .' allowed_types='.implode(',', array_map('strval', (array) config('teleframe.passport.allowed_types')))
        .' nonce_registry='.get_class(app(PassportSupport::NONCES_KEY))
        .' wire='.get_class(app(PassportSupport::WIRE_KEY)),
];

// ---- THE SIX VERSION-ONE CAPABILITY SECTIONS, ONE FILE EACH.
//
// WHY A DIRECTORY AND NOT MORE $out[...] HERE. This script carried one inline
// section per capability, so the four that were MISSING could not be added in
// parallel without four agents editing the same 1769-line file — the hazard that
// costs a proof script silently, because nothing goes red until a pipeline is
// red. Each capability is therefore ONE FILE under bin/prove-v1/, and this merges
// them.
//
// WHY THE MERGE ASSERTS INSTEAD OF TRUSTING. A glob that finds nothing, a probe
// file that fatals, a section named differently from what the operator asked for
// — all three produce the SAME symptom as "this capability is proven": the
// report simply does not mention it. A proof that goes quiet on the thing it
// exists to prove is worse than a proof that fails, because silence reads as
// agreement. So the six names are asserted here, by hand, from the operator's own
// scope statement — .brain/verbatim/2026-09-24-notification-logger-backup-and-bot-framework.md:
// "I need a version of this package to be ready and it must only have these
// things that I am about to say" — and a missing or renamed section FAILS the run
// rather than quietly shrinking the report.
$V1_REQUIRED = [
    'V1.1 notify-logger',
    'V1.2 telegram-backup-store',
    'V1.3 laravel-db-backup',
    'V1.4 mtproto-all',
    'V1.5 passport-login',
    'V1.6 bot-user-apps',
];

$v1Dir = $root.'/vendor/merezarezaei/teleframe/bin/prove-v1';
$v1Found = [];
foreach ((array) (glob($v1Dir.'/*.php') ?: []) as $probeFile) {
    $sections = require $probeFile;
    if (! is_array($sections)) {
        fwrite(STDERR, sprintf('PROBE FILE RETURNED NO SECTIONS: %s%s', $probeFile, PHP_EOL));
        $v1Found[] = '(unreadable) '.$probeFile;
        continue;
    }
    foreach ($sections as $section => $checks) {
        $out[$section] = $checks;
        $v1Found[] = (string) $section;
    }
}
sort($v1Found);

$v1Missing = array_values(array_diff($V1_REQUIRED, $v1Found));
if ($v1Missing !== []) {
    $out['V1 completeness — the operator asked for six and named them'] = [
        'all-six-present' => mustBeTrue(
            static fn (): bool => $v1Missing === [],
            'every one of the six Version One capability sections is present and named as asked for',
        ),
    ];
    fwrite(STDERR, sprintf(
        'V1 CAPABILITY SECTIONS MISSING: %s%s  (found: %s)%s',
        implode(', ', $v1Missing), PHP_EOL,
        $v1Found === [] ? '(none)' : implode(', ', $v1Found), PHP_EOL,
    ));
}

echo '##PROBE##'.json_encode($out, JSON_UNESCAPED_SLASHES)."\n";
PHP);

// proc_open REPLACES the environment, so the parent's is merged in rather than
// dropped — the host still needs PATH, HOME and its own dotenv variables.
$inheritedEnv = getenv();
$passportEnv = array_merge(is_array($inheritedEnv) ? $inheritedEnv : [], [
    'TELEFRAME_PASSPORT_ENABLED' => 'true',
    'TELEFRAME_PASSPORT_PRIVATE_KEY' => $passportPem,
]);

proveStep('the V1.5 probe: a second host process, Passport CONFIGURED, still no api id/hash/token');
$passportRun = proveRun([PHP_BINARY, $probePassportPath], $host, 180, $passportEnv);
// Nothing this gate generated outlives the run that generated it, kept host or
// not. The key material is gone before the result is even read.
@unlink($passportPem);

$passportJson = null;
foreach (array_reverse(preg_split('/\R/', $passportRun['out'].$passportRun['err']) ?: []) as $line) {
    if (str_starts_with(trim($line), '##PROBE##')) {
        $passportJson = json_decode(substr(trim($line), strlen('##PROBE##')), true);
        break;
    }
}

if (! is_array($passportJson)) {
    proveLine('  ✗ the V1.5 passport probe did not report (the host fataled booting with Passport enabled?)');
    proveLine('    '.proveTail($passportRun, 14));
    exit(EXIT_FAILED);
}

// MERGED, not appended: both probes report through the ONE $out table and the
// ONE walker below, so a V1.5 verdict is printed in exactly the shape every
// other verdict in this gate is printed in.
$probeJson = array_merge($probeJson, $passportJson);

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

/** @var list<string> the composer.json facade aliases this gate names. */
const GATED_FACADE_ALIASES = ['TF', 'Teleframe', 'TStore', 'Notify', 'StoreBackup', 'TelegramApp', 'Passport'];

// Every alias this gate names must be declared in composer.json. The count is
// DERIVED from the list, not written as a word: a hand-typed "six" beside a
// seven-item loop is how this label became a lie in the first place.
$checks[sprintf('all %d facade aliases are declared in composer.json', count(GATED_FACADE_ALIASES))] = static function () use ($repoRoot, &$proveFailures): bool {
    $composer = json_decode((string) file_get_contents($repoRoot.'/composer.json'), true);
    $aliases = $composer['extra']['laravel']['aliases'] ?? [];
    foreach (GATED_FACADE_ALIASES as $alias) {
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

// V1.4 is a CAPABILITY, not a seventh facade: it has no alias of its own, and
// what it must show is that BOTH scopes reach the same call surface. Same
// builder, so an informational key (layers) is skipped identically.
$checks['V1.4 mtproto-all is reachable from the host install for BOTH the user and the bot scope (nothing dials)'] = $facadeCheck('V1.4 mtproto-all');

// V1.5 is a CAPABILITY too, and it is the mirror of §4 rather than a repeat of
// it. The `Passport` label above proves the refusal half — with no
// configuration, `login()` refuses BY NAME and that refusal is asserted. This
// one proves the half the operator's sentence is actually about: with
// teleframe.passport CONFIGURED, a Laravel user ends up logged in. Same shape
// (no alias of its own, informational keys skipped identically), different
// process, because the enabled posture cannot be reached in one that already
// resolved the singleton with `enabled=false`.
$checks['V1.5 passport-login is reachable from the host install: with Passport CONFIGURED a Laravel user really logs in (no credential, no socket)'] = $facadeCheck('V1.5 passport-login');

// ---- THE OTHER FOUR, AND WHY THE LIST BELOW IS HAND-WRITTEN ON PURPOSE.
//
// The operator scoped V1 in one sentence: "it must only have these things that I am
// about to say" (.brain/verbatim/2026-09-24-notification-logger-backup-and-bot-framework.md).
// Six things. So six labels, written out by hand, from that sentence.
//
// A loop over "whatever sections happen to exist" is the tempting shape and it is
// the wrong one: a probe file that fatals, a section renamed by a typo, a glob that
// matches nothing — every one of them produces the SAME symptom as "this capability
// is proven", which is a report that simply does not mention it. A gate that goes
// quiet on the thing it exists to prove is worse than a gate that fails, because
// silence reads as agreement. Naming each capability here means a missing one FAILS
// with its own name in the message.
$V1_CAPABILITIES = [
    'V1.1 notify-logger' => 'V1.1 the notify/logger system: a level becomes a topic, a topic becomes a thread_id, and a long payload splits into a message context plus files the browser can render',
    'V1.2 telegram-backup-store' => 'V1.2 Telegram as backup storage: 4 GB chunking, a password, and a coded context string so Telegram cannot read the payload',
    'V1.3 laravel-db-backup' => 'V1.3 the default behaviour: the host\'s own Laravel database dumped on a schedule into a channel',
    'V1.4 mtproto-all' => 'V1.4 mtproto-all is reachable from the host install for BOTH the user and the bot scope (nothing dials)',
    'V1.5 passport-login' => 'V1.5 passport-login is reachable from the host install: with Passport CONFIGURED a Laravel user really logs in (no credential, no socket)',
    'V1.6 bot-user-apps' => 'V1.6 a bot or user app responds to an update from the host install: a registered handler actually resolves and runs',
];

foreach ($V1_CAPABILITIES as $section => $label) {
    if ($section === 'V1.4 mtproto-all' || $section === 'V1.5 passport-login') {
        continue; // already asserted above, in the shape they were written for
    }

    $checks[$label] = $facadeCheck($section);
}

// The six, counted. A capability that is present but reported by nobody is still
// unproven, and this is the line that says so in one place.
$checks['V1 the operator asked for six capabilities and this host install proves all six by name'] = static function () use ($V1_CAPABILITIES, $probeJson): bool {
    foreach (array_keys($V1_CAPABILITIES) as $section) {
        $block = $probeJson[$section] ?? null;
        if (! is_array($block)) {
            return false;
        }
    }

    return true;
};

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
proveLine('  - V1.4 past the method surface: a scope obtained THROUGH the facade is unreachable here, because');
proveLine('    TeleframeClient::user() and ::botMtproto() guard on api id/hash/token BEFORE they construct anything,');
proveLine('    and that refusal is exactly what the gate asserts — so section 8 proves the catalogue, the TL registry,');
proveLine('    and both scopes being CONSTRUCTIBLE without any credential, not a scope the facade hands over;');
proveLine('  - auth.importBotAuthorization, the one call only a bot makes: it is constructed and refuses, never dialed, so the bot path');
proveLine('    stays structurally proven rather than proven against a real DC;');
proveLine('  - V1.5 past the host-supplied wire: the credential wall begins AT THE FIRST REAL TELEGRAM CALL. Every byte');
proveLine('    the V1.5 section decrypts was encrypted here, in this run, against a throwaway keypair generated per run');
proveLine('    and unlinked the moment the probe returns — so `upload.getFile` is dispatched on an in-memory seam and is');
proveLine('    NEVER dialled, and no api id/hash/token/session/auth key is used anywhere in the login. What stays unproven');
proveLine('    is therefore everything downstream of a live DC: the Bot API webhook carrying a REAL Telegram-issued');
proveLine('    envelope, the REAL bot public key (so the real bot can decrypt what it is sent), the mtproto');
proveLine('    account.getAuthorizationForm / acceptAuthorization handshake, the file arriving from Telegram rather than');
proveLine('    from a scripted answer, the anonymous_login capability (a separate switch, false by default, and a');
proveLine('    different chain), and users.setSecureValueErrors — which names a refusal to Telegram and so needs Telegram;');
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
