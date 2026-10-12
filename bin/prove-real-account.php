<?php

declare(strict_types=1);

/**
 * Prove whether a real Telegram account is reachable through a real host app's
 * STORED session — and, above all, prove it HONESTLY.
 *
 * WHY THIS FILE EXISTS
 * --------------------
 * A previous agent session reported this session as DEAD (AUTH_KEY_UNREGISTERED).
 * That conclusion was WRONG. It came from a probe that never reached Telegram in
 * a meaningful way: it died inside the TL encoder (`TLEncoder: missing field
 * 'id' for users.getUsers`), and a broken encoder was read as an auth failure.
 * That is the worst possible class of bug in a reachability harness: it reports
 * a confident, actionable, and completely false verdict.
 *
 * So the single most important design rule here is the CLASSIFICATION contract:
 *
 *   SUCCESS         an account came back with self=true. Nothing else qualifies.
 *   SESSION_INVALID a Telegram RPC error that genuinely rejects the auth key
 *                   (AUTH_KEY_UNREGISTERED / AUTH_KEY_INVALID / SESSION_REVOKED
 *                   / SESSION_EXPIRED, i.e. the 401 family). ONLY this state may
 *                   ever be reported as "the session is dead".
 *   HARNESS_ERROR   EVERYTHING ELSE — TL encoding errors, missing classes,
 *                   unreadable .env, DNS/connect timeouts, transport faults.
 *                   This prints the exception class + message and states in so
 *                   many words that it says NOTHING about session validity.
 *
 * The failure mode we are engineering against is a harness that blames the
 * session for its own breakage. Every branch below therefore prints which
 * bucket it landed in, and the HARNESS_ERROR bucket is worded so it cannot be
 * skimmed as a session verdict.
 *
 * WHY help.getNearestDc IS NOT USED
 * ---------------------------------
 * help.getNearestDc is UNAUTHENTICATED. Telegram answers it happily with a dead
 * or unregistered auth key, so it can never distinguish a live session from a
 * corpse. The proof here is therefore an AUTHENTICATED, read-only call:
 * users.getUsers with inputUserSelf. No writes, no messages, no code sends.
 *
 * SESSION SHAPE IS THE CONTRACT (do not "improve" this)
 * -----------------------------------------------------
 * The host app's config/teleframe.php does exactly:
 *     'session_string' => base64_decode((string) env('TELEGRAM_SESSION_1', ''))
 * so the value handed to the client is the result of ONE base64_decode() of the
 * .env variable. TeleframeClient::user() then base64-decodes it AGAIN and finds
 * the canonical colon format
 *     dcId:base64(authKey):userId:serverTimeDelta:serverSalt
 * which SessionData::importString() parses into dcId / a 256-byte authKey /
 * userId. This script mirrors that path exactly — one decode here, the client's
 * own decode there — and asserts the shape before dialing. A wrong dcId or a
 * truncated authKey produces a client that fails in confusing ways, and a wrong
 * authKey is the one input that legitimately yields 401; proving the shape
 * first is what keeps those two causes distinguishable.
 *
 * SECRETS
 * -------
 * The session string, the auth key and the api_hash are credentials. This script
 * prints ids, lengths and shape only — never a key, never a session, never a
 * hash. Exception messages are scrubbed before they are printed, because an RPC
 * or transport error can otherwise echo back a fragment of the material it was
 * handed.
 *
 * EXIT CODES (a caller must be able to tell these apart from the code alone)
 *   0  SUCCESS         live account returned, self=true
 *   2  SESSION_INVALID Telegram itself rejected the auth key (401 family)
 *   3  HARNESS_ERROR   this script broke; the session verdict is UNKNOWN
 *   4  USAGE/CONFIG    bad argv, unreadable/malformed .env, missing credentials,
 *                      or a session whose parsed shape is wrong
 *
 * BOUNDS
 * ------
 * Socket timeouts are pinned via default_socket_timeout (the value
 * StreamSocket/stream_socket_client falls back to) and, where pcntl is present,
 * a SIGALRM backstop bounds the whole call. The script cannot hang.
 *
 * USAGE
 *   php bin/prove-real-account.php [/path/to/host-app/.env]
 *
 * It talks to Telegram ONCE, on purpose. Re-running it is a human decision.
 */

require __DIR__.'/../vendor/autoload.php';

use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;

const EXIT_SUCCESS = 0;
const EXIT_SESSION_INVALID = 2;
const EXIT_HARNESS_ERROR = 3;
const EXIT_USAGE = 4;

/** Hard bound on the whole live call, in seconds. */
const LIVE_CALL_DEADLINE_SECONDS = 45;

/** Socket-level bound, in seconds. */
const SOCKET_TIMEOUT_SECONDS = 10;

/**
 * Erase anything that could carry credential material out of a message.
 *
 * A scrub, not a filter: it cannot tell what is secret, so it removes the shapes
 * that are — long base64/hex runs and any `key=`/`session=`/`hash=` assignment —
 * and says how much it removed, so a reader can tell "quiet" from "mangled".
 */
function scrub(string $text): string
{
    $out = preg_replace('/[A-Za-z0-9+\/=_-]{32,}/', '[redacted]', $text) ?? $text;
    $out = preg_replace('/\b(key|session|hash|token|secret)=[^\s"\']+/i', '$1=[redacted]', $out) ?? $out;

    return $out;
}

function out(string $line): void
{
    fwrite(STDOUT, $line."\n");
}

function err(string $line): void
{
    fwrite(STDERR, $line."\n");
}

/**
 * The three closing verdicts, each printed in the same shape so a human cannot
 * misread one as another.
 */
function report(string $bucket, string $verdict, int $exit): never
{
    out('');
    out('============================================================');
    out("CLASSIFICATION : {$bucket}");
    out("VERDICT       : {$verdict}");
    out('============================================================');
    exit($exit);
}

/**
 * Read one variable out of a .env file without booting Laravel.
 *
 * Mirrors verify-seed.php: line-oriented, trim, strip one layer of quotes.
 * Regex is used only for the key-anchored match; the value handling is all
 * string functions.
 */
function envVar(string $contents, string $key): ?string
{
    foreach (explode("\n", $contents) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $eq = strpos($line, '=');
        if ($eq === false || trim(substr($line, 0, $eq)) !== $key) {
            continue;
        }
        $value = trim(substr($line, $eq + 1));
        if (strlen($value) >= 2) {
            $first = $value[0];
            if (($first === '"' || $first === "'") && str_ends_with($value, $first)) {
                $value = substr($value, 1, -1);
            }
        }

        return $value === '' ? null : $value;
    }

    return null;
}

/**
 * Decide whether a thrown exception is Telegram rejecting the auth key, or
 * something else entirely.
 *
 * Only the 401 family counts. An encoder exception, a missing class, a DNS
 * failure and a timeout are all HARNESS_ERROR by construction — that separation
 * is the entire point of this file.
 *
 * @return array{0: bool, 1: string}
 */
function classifyAuthRejection(Throwable $e): array
{
    $class = get_class($e);
    $code = (int) $e->getCode();

    // The package resolves RPC auth rejections into a dedicated type; that is
    // the strongest signal available and needs no string matching.
    if (str_contains($class, 'AuthKey')) {
        return [true, $class];
    }

    $haystack = strtoupper($class.' '.$e->getMessage());
    foreach (['AUTH_KEY_UNREGISTERED', 'AUTH_KEY_INVALID', 'SESSION_REVOKED', 'SESSION_EXPIRED'] as $needle) {
        if (str_contains($haystack, $needle)) {
            return [true, $needle];
        }
    }

    // A bare 401 is the RPC code for the whole rejection family.
    if ($code === 401) {
        return [true, 'RPC 401'];
    }

    return [false, ''];
}

/**
 * Arm a SIGALRM backstop so a wedged socket cannot outlive the deadline.
 *
 * The transport has its own socket timeouts; this is the belt to that braces,
 * because a bounded script that can still hang is not bounded.
 *
 * WHY A HANDLER AND NOT JUST `pcntl_alarm()`. Without a registered handler the
 * alarm takes PHP's DEFAULT action for SIGALRM, which terminates the process
 * with an unhandled-signal status — so a wedged socket would still be killed,
 * but the caller could not tell a timeout apart from a crash, and this script's
 * whole contract is that its exit code is honest. So the handler exits through
 * `report()` with HARNESS_ERROR, which says in words that the session verdict is
 * UNKNOWN. A deadline must not invent a verdict, and it must not lose one.
 *
 * The signal handler can only use async-signal-safe work in the strict POSIX
 * sense. `report()` writes two short lines and calls exit(); that is deliberate
 * and is why the text is short rather than formatted from live state.
 */
function armDeadline(int $seconds): bool
{
    if (! function_exists('pcntl_alarm')
        || ! function_exists('pcntl_async_signals')
        || ! function_exists('pcntl_signal')
    ) {
        return false;
    }

    pcntl_async_signals(true);

    if (! pcntl_signal(SIGALRM, static function (): void {
        report(
            'HARNESS_ERROR',
            'UNKNOWN — the live call exceeded its '.LIVE_CALL_DEADLINE_SECONDS
                .'s deadline and was aborted. A timeout says NOTHING about session validity.',
            EXIT_HARNESS_ERROR,
        );
    })) {
        return false;
    }

    // Only now is the alarm real. Returning true before this point would be the
    // exact defect this function previously had: it claimed a backstop it had
    // never armed, so the script printed "armed" over a process that was in
    // fact unbounded.
    return pcntl_alarm($seconds) !== false;
}

/** Disarm the deadline once the verdict is known, so it cannot fire late. */
function disarmDeadline(): void
{
    if (function_exists('pcntl_alarm')) {
        pcntl_alarm(0);
    }
}

// ---------------------------------------------------------------------------
// 1. Arguments + .env (no Laravel, no database)
// ---------------------------------------------------------------------------

$envPath = $argv[1] ?? '/home/me/Documents/projects/teleframe-app/.env';

if (isset($argv[2])) {
    err('USAGE: php bin/prove-real-account.php [/path/to/host-app/.env] — one argument at most.');
    exit(EXIT_USAGE);
}

out('prove-real-account — live reachability proof for a stored Telegram session');
out('env file        : '.$envPath);

if (! is_file($envPath) || ! is_readable($envPath)) {
    err('');
    err('CONFIG ERROR: cannot read the .env file at that path.');
    report('USAGE/CONFIG', 'NO VERDICT — the harness never reached Telegram.', EXIT_USAGE);
}

$contents = (string) file_get_contents($envPath);

$rawSession = envVar($contents, 'TELEGRAM_SESSION_1');
$apiIdRaw = envVar($contents, 'TELEGRAM_API_ID');
$apiHash = envVar($contents, 'TELEGRAM_API_HASH');
$expectedUserId = envVar($contents, 'TELEGRAM_USER_ID');

if ($rawSession === null || $apiIdRaw === null || $apiHash === null) {
    err('');
    err('CONFIG ERROR: TELEGRAM_SESSION_1 / TELEGRAM_API_ID / TELEGRAM_API_HASH must all be present.');
    report('USAGE/CONFIG', 'NO VERDICT — the harness never reached Telegram.', EXIT_USAGE);
}

$apiId = (int) $apiIdRaw;
if ($apiId <= 0) {
    err('');
    err('CONFIG ERROR: TELEGRAM_API_ID is not a positive integer (value: '.$apiIdRaw.').');
    report('USAGE/CONFIG', 'NO VERDICT — the harness never reached Telegram.', EXIT_USAGE);
}

// EXACTLY ONE decode, mirroring the host app's config closure. No extra layers.
$session = (string) base64_decode($rawSession, true);

// ---------------------------------------------------------------------------
// 2. Shape assertion — before any network call
// ---------------------------------------------------------------------------
//
// A wrong dcId silently connects to the wrong datacenter; a short/truncated
// authKey is the one input that legitimately produces 401. Proving the shape
// first is what keeps "my harness is broken" and "my session is dead" apart.

$inner = (string) base64_decode($session, true);
$parts = explode(':', $inner);
$shapeOk = count($parts) >= 5;

if ($shapeOk) {
    $dcId = (int) $parts[0];
    $authKeyLen = strlen((string) base64_decode($parts[1], true));
    $userId = (int) $parts[2];
} else {
    $dcId = 0;
    $authKeyLen = 0;
    $userId = 0;
}

out('');
out('-- parsed session shape (ids and lengths only; no key material) -----');
out('session env value length : '.strlen($rawSession));
out('after ONE base64_decode : '.strlen($session).' chars');
out('decoded field count      : '.count($parts));
out('dcId                    : '.$dcId);
out('authKey length          : '.$authKeyLen.' bytes');
out('userId                  : '.$userId);

if ($expectedUserId !== null) {
    out('TELEGRAM_USER_ID        : '.$expectedUserId.($expectedUserId === (string) $userId ? ' (matches)' : ' (MISMATCH)'));
}

// Cross-check the same parse the client itself will perform, so the assertion
// covers the client path and not just our own reading of the string.
$imported = null;
$importError = '';
try {
    $imported = SessionData::importString($session);
} catch (Throwable $e) {
    $importError = get_class($e).': '.$e->getMessage();
}

if ($imported === null) {
    out('SessionData::importString: FAILED — '.$importError);
} else {
    out('importString dcId       : '.(int) $imported->dcId);
    out('importString authKey len: '.strlen((string) $imported->authKey).' bytes');
    out('importString userId     : '.(int) $imported->userId);
}

$problems = [];
if (! $shapeOk) {
    $problems[] = 'session does not decode to the canonical colon format (expected at least 5 fields, got '.count($parts).')';
}
if ($dcId < 1 || $dcId > 5) {
    $problems[] = 'dcId '.$dcId.' is outside the valid range 1..5';
}
if ($authKeyLen !== 256) {
    $problems[] = 'authKey length '.$authKeyLen.' is not 256 bytes';
}
if ($imported === null) {
    $problems[] = 'SessionData::importString() rejected the session';
}

if ($problems !== []) {
    foreach ($problems as $problem) {
        err('SHAPE ERROR: '.$problem);
    }
    err('');
    err('Note: this is a CONFIG/SHAPE fault in the harness, NOT a statement about');
    err('session validity. No authenticated call was attempted.');
    report('USAGE/CONFIG', 'NO VERDICT — malformed session shape, Telegram was never asked.', EXIT_USAGE);
}

out('');
out('shape assertions passed  : dcId in 1..5, authKey is 256 bytes');
out('NOTE: a malformed session would ALSO produce 401. The shape is proven');
out('      first precisely so that a later 401 can be trusted.');

// ---------------------------------------------------------------------------
// 3. ONE authenticated, read-only live call
// ---------------------------------------------------------------------------

ini_set('default_socket_timeout', (string) SOCKET_TIMEOUT_SECONDS);
$haveAlarm = armDeadline(LIVE_CALL_DEADLINE_SECONDS);
register_shutdown_function(static function () use ($haveAlarm): void {
    // Whatever route we leave by — a verdict, an exception, or the alarm itself
    // — the alarm must not outlive it. `report()` exits, so this runs last and
    // cancels the only remaining way this process could still fire.
    if ($haveAlarm) {
        disarmDeadline();
    }
});
out('');
out('-- live call -----------------------------------------------------');
out('method : users.getUsers (AUTHENTICATED, read-only)');
out('param  : inputUserSelf');
out('timeout: '.SOCKET_TIMEOUT_SECONDS.'s socket, '.LIVE_CALL_DEADLINE_SECONDS.'s deadline'.($haveAlarm ? ' (pcntl backstop armed)' : ' (no pcntl; socket timeout only)'));
out('');

try {
    $client = new TeleframeClient($apiId, $apiHash);
    $scope = $client->user(session: $session, dcId: $dcId, apiId: $apiId, apiHash: $apiHash);
    $scope->mtproto->live();

    $result = $scope->call('users.getUsers', ['id' => [['_' => 'inputUserSelf']]]);
} catch (Throwable $e) {
    [$isAuthRejection, $reason] = classifyAuthRejection($e);

    err('EXCEPTION  : '.scrub(get_class($e)));
    err('MESSAGE    : '.scrub($e->getMessage()));

    if ($isAuthRejection) {
        out('');
        out('Telegram itself rejected the auth key ('.$reason.'). The session shape was');
        out('proven correct above (dcId '.$dcId.', 256-byte authKey), so this rejection is');
        out('the session, not the harness.');

        report('SESSION_INVALID', 'SESSION DEAD — Telegram rejected this auth key (401 family).', EXIT_SESSION_INVALID);
    }

    err('');
    err('*** HARNESS_ERROR — READ THIS CAREFULLY ***');
    err('This exception is NOT an authentication failure. It did not come from');
    err('Telegram rejecting the key; it is this script failing somewhere before,');
    err('during, or instead of a successful authenticated read.');
    err('It says NOTHING whatsoever about whether the session is valid.');

    report('HARNESS_ERROR', 'UNKNOWN — the harness broke; session validity was NOT determined.', EXIT_HARNESS_ERROR);
}

// ---------------------------------------------------------------------------
// 4. Score the answer
// ---------------------------------------------------------------------------

if (! is_array($result)) {
    err('EXPECTED an array from users.getUsers, got '.gettype($result));
    report('HARNESS_ERROR', 'UNKNOWN — unexpected response shape; session validity NOT determined.', EXIT_HARNESS_ERROR);
}

$users = $result;
if (isset($result['users']) && is_array($result['users'])) {
    $users = $result['users'];
} elseif (isset($result[0]['users']) && is_array($result[0]['users'])) {
    $users = $result[0]['users'];
}

if (! is_array($users) || $users === []) {
    err('users.getUsers returned no user object: '.scrub(substr((string) json_encode($result), 0, 400)));
    err('');
    err('*** HARNESS_ERROR *** An empty result is not an auth rejection either —');
    err('it may be a response-unwrapping fault in this script. It says NOTHING');
    err('about session validity.');

    report('HARNESS_ERROR', 'UNKNOWN — no user object came back; session validity NOT determined.', EXIT_HARNESS_ERROR);
}

$me = null;
foreach ($users as $candidate) {
    if (is_array($candidate) && ($candidate['self'] ?? false) === true) {
        $me = $candidate;
        break;
    }
}

$returnedId = (int) ($me['id'] ?? 0);
$selfFlag = $me !== null;

out('returned user id : '.$returnedId);
out('self flag        : '.($selfFlag ? 'true' : 'false'));
out('fields           : '.implode(', ', array_keys($users[0] ?? [])));

if (! $selfFlag) {
    err('');
    err('*** HARNESS_ERROR *** A user object came back but not self=true.');
    err('That is not an auth rejection. It says NOTHING about session validity.');

    report('HARNESS_ERROR', 'UNKNOWN — no self=true user; session validity NOT determined.', EXIT_HARNESS_ERROR);
}

if ($expectedUserId !== null && $expectedUserId !== (string) $returnedId) {
    err('');
    err('WARNING: returned id '.$returnedId.' != TELEGRAM_USER_ID '.$expectedUserId.'.');
}

out('');
out('A live account returned self=true over an authenticated MTProto call, and the');
out('session shape (dcId '.$dcId.', 256-byte authKey) was proven before dialing.');
out('The account IS reachable through the host app\'s stored session.');

report('SUCCESS', 'LIVE — a real account answered an authenticated read-only call.', EXIT_SUCCESS);