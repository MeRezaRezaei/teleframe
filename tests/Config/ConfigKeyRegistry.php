<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Config;

/**
 * The dead-config audit registry (audit of 2026-09-27).
 *
 * THE DEFECT CLASS. docs/verbatim-goals.md records three separate instances
 * of the same bug, each found by a different agent: a config key that is
 * DECLARED, DOCUMENTED and READ BY NOTHING. The ledger's own ruling:
 * "a documented boundary is honest; a config key that is declared, documented
 * and read by nothing is a promise the code does not keep." This class is a
 * REGISTRY so the next key cannot join it unnoticed.
 *
 * FOUR CLASSES, and the fourth is the one a grep for the key name lies to
 * you about:
 *
 *   READ              — wired, and the value reaches behaviour.
 *   READ_BUT_IGNORED  — read, then absorbed by boolean algebra or discarded,
 *                       so it is still a promise the code does not keep.
 *                       Grep finds the reader and calls it healthy.
 *                       NOW EMPTY: the audit's one instance
 *                       (store.backup.enabled) was fixed. The class is kept
 *                       because the class itself is the finding.
 *   DEAD              — declared, documented, read by nothing.
 *   READ_CONDITIONAL  — read only on a posture the config file cannot itself
 *                       select (a row the reader only reaches when a sibling
 *                       key is set).
 *
 * The registry lists every leaf key of every file in src/Laravel/config/,
 * under the merge prefix its provider uses. A key that exists on disk but not
 * here fails DeclaredKeyCoverageTest — that is the rot guard for the AUDIT.
 * A key here that has since gained a reader fails DeadKeyRegressionTest —
 * that is the rot guard for the CLASSIFICATION.
 *
 * WHAT CHANGED 2026-09-27 (the audit's own follow-up pass): 11 DEAD -> 5,
 * 1 READ-BUT-IGNORED -> 0, 131 READ -> 139, declared surface 148 -> 149.
 * Eight keys were wired (six schedules, spool_dir, backup.enabled) and one
 * was ADDED (tstore.reference_policy) — the cold-copy posture shipped by
 * f5628079 was unreachable from config until this pass. The five that remain
 * DEAD are the Redis wire keys, kept dead ON PURPOSE: see DEAD's docblock.
 *
 * WHAT CHANGED 2026-09-30 (fifth pass, Cycle 38 — Goal D): the S3 durable-vault
 * tier added a teleframe.tstore.s3.* block to the config file, and all six keys
 * landed here as READ on one reader. Declared surface 162 -> 168, READ
 * 152 -> 158. The coverage test caught them UNCLASSIFIED first, in GitLab
 * pipeline 810, which is this registry refusing to let a new declaration join
 * the dead-config class unnoticed.
 *
 * WHAT CHANGED 2026-10-12 (generation retention): one key added,
 * teleframe.backup.keep_generations — how many newest generations a prune
 * keeps restorable. Declared surface 168 -> 169, READ 158 -> 159. Read by
 * BackupCommand::keepGenerations(), overridden per-run by
 * `--keep-generations=N`.
 */
final class ConfigKeyRegistry
{
    /** Merge prefix => the config file each provider merges it from. */
    public const FILES = [
        'teleframe.app' => 'app.php',
        'teleframe.notify' => 'notify.php',
        'teleframe.passport' => 'passport.php',
        'teleframe.store.backup' => 'store-backup.php',
        'teleframe' => 'teleframe.php',
    ];

    public const CLASS_READ = 'READ';

    public const CLASS_READ_BUT_IGNORED = 'READ-BUT-IGNORED';

    public const CLASS_DEAD = 'DEAD';

    public const CLASS_READ_CONDITIONAL = 'READ-CONDITIONAL';

    /**
     * READ — 158 keys, each with a proven reader.
     *
     * @var list<string>
     */
    public const READ = [
        'teleframe.api_hash',
        'teleframe.api_id',
        'teleframe.app.apps',
        'teleframe.app.schedules',
        'teleframe.app.schedules_dsn',
        'teleframe.app.schedules_task_queue',
        'teleframe.app.transport',
        'teleframe.backfill.flood_cap_seconds',
        'teleframe.backfill.request_budget',
        'teleframe.backup.account',
        'teleframe.backup.allow_volatile_vault',
        'teleframe.backup.chunk_size',
        'teleframe.backup.max_age_hours',
        'teleframe.backup.driver',
        // Retention: how many newest generations a prune keeps restorable.
        // READ by BackupCommand::keepGenerations(), which applies it as the
        // width of Pruner::retentionKeepHashes() — the keep-set the prune
        // deletes against. Reached on the `prune` action only.
        'teleframe.backup.keep_generations',
        'teleframe.backup.passphrase',
        'teleframe.backup.scheduled',
        'teleframe.backup.sets.default.excludes',
        'teleframe.backup.sets.default.paths',
        'teleframe.bot_session',
        'teleframe.bot_token',
        'teleframe.botmap.secret',
        'teleframe.bus.connection',
        'teleframe.daemon.accounts',
        'teleframe.dashboard.middleware',
        'teleframe.dashboard.prefix',
        'teleframe.dc_id',
        'teleframe.identity.default_sender',
        'teleframe.identity.miniapp_auth_max_age',
        'teleframe.live_mode',
        'teleframe.logging.logger',
        'teleframe.notify.amqp.connect_timeout',
        'teleframe.notify.amqp.heartbeat',
        'teleframe.notify.amqp.host',
        'teleframe.notify.amqp.password',
        'teleframe.notify.amqp.port',
        'teleframe.notify.amqp.read_timeout',
        'teleframe.notify.amqp.user',
        'teleframe.notify.amqp.vhost',
        'teleframe.notify.amqp.write_timeout',
        'teleframe.notify.attachment_dir',
        'teleframe.notify.budgets.enforce',
        'teleframe.notify.capabilities',
        'teleframe.notify.default_chat',
        'teleframe.notify.exceptions.enabled',
        'teleframe.notify.exceptions.level',
        'teleframe.notify.exceptions.topic',
        'teleframe.notify.inline_budget',
        'teleframe.notify.premium',
        'teleframe.notify.premium_probe.ttl_seconds',
        // Added by 4190089a, which made the MTProto user-scope sender reachable
        // from config. Classified here rather than discovered by the coverage
        // test failing in CI — which is exactly what happened the first time,
        // and is the whole point of this registry: an unclassified key is how
        // this defect class grows. Reader sites are in
        // NotifyServiceProvider::registerTransport():
        //   :492  teleframe.notify.transport  — bot|user_scope, unknown throws
        //   :523  teleframe.notify.user_scope.dialog_limit — pages getDialogs
        'teleframe.notify.transport',
        'teleframe.notify.user_scope.dialog_limit',
        'teleframe.notify.premium_probe.user_id',
        'teleframe.notify.queue',
        'teleframe.notify.queue_connection',
        'teleframe.notify.queue_fallback',
        'teleframe.notify.queue_name',
        'teleframe.notify.schedules',
        'teleframe.notify.schedules_dsn',
        'teleframe.notify.topics',
        'teleframe.passport.account_id',
        'teleframe.passport.allowed_types',
        'teleframe.passport.anonymous_login',
        'teleframe.passport.bot_id',
        'teleframe.passport.bot_username',
        'teleframe.passport.email_column',
        'teleframe.passport.enabled',
        'teleframe.passport.login_rate_limit',
        'teleframe.passport.nonce_store',
        'teleframe.passport.nonce_ttl',
        'teleframe.passport.phone_column',
        'teleframe.passport.private_key_path',
        'teleframe.passport.public_key',
        'teleframe.passport.require_atomic_nonce',
        'teleframe.passport.require_password_confirmation',
        'teleframe.passport.require_verified',
        'teleframe.passport.scope',
        'teleframe.passport.success_redirect',
        'teleframe.passport.unknown_user',
        'teleframe.passport.user_lookup',
        'teleframe.primary_account_id',
        'teleframe.realtime.apikey',
        'teleframe.realtime.secret',
        'teleframe.realtime.url',
        'teleframe.relay.accounts',
        'teleframe.relay.enabled',
        'teleframe.relay.listen_host',
        'teleframe.relay.port_base',
        'teleframe.relay.read_only.enforce',
        'teleframe.relay.read_only.session_minted_tickets',
        'teleframe.relay.read_only.undetermined_grant',
        'teleframe.relay.upstream_host',
        'teleframe.relay.upstream_port',
        'teleframe.relay.vault_terminal.enabled',
        'teleframe.relay.vault_terminal.rsa_key',
        'teleframe.schema_sources',
        'teleframe.ship_namespaces',
        'teleframe.store.backup.database.database',
        'teleframe.store.backup.database.driver',
        'teleframe.store.backup.database.host',
        'teleframe.store.backup.database.port',
        'teleframe.store.backup.database.socket',
        'teleframe.store.backup.database.tables',
        'teleframe.store.backup.database.user',
        'teleframe.store.backup.enabled',
        'teleframe.store.backup.queue',
        'teleframe.store.backup.schedule.dsn',
        'teleframe.store.backup.schedule.enabled',
        'teleframe.store.backup.schedule.interval_seconds',
        'teleframe.store.backup.schedule.publisher',
        'teleframe.store.backup.schedule.task_queue',
        'teleframe.store.backup.schedule.watchdog_expect_multiplier',
        'teleframe.store.backup.spool_dir',
        'teleframe.store.backup.target.account_id',
        'teleframe.store.backup.target.bucket',
        'teleframe.store.backup.target.passphrase',
        'teleframe.store.backup.targets',
        'teleframe.supervisor.env',
        'teleframe.supervisor.max_backoff_seconds',
        'teleframe.supervisor.max_restarts',
        'teleframe.supervisor.processes',
        'teleframe.supervisor.restart_delay_seconds',
        'teleframe.telegram_web.dist_path',
        'teleframe.telegram_web.enabled',
        'teleframe.telegram_web.live_endpoint',
        'teleframe.telegram_web.ticket_ttl_minutes',
        'teleframe.tstore.account_id',
        'teleframe.tstore.amqp.connect_timeout',
        'teleframe.tstore.amqp.heartbeat',
        'teleframe.tstore.amqp.host',
        'teleframe.tstore.amqp.password',
        'teleframe.tstore.amqp.port',
        'teleframe.tstore.amqp.publish_retry_budget_seconds',
        'teleframe.tstore.amqp.read_timeout',
        'teleframe.tstore.amqp.user',
        'teleframe.tstore.amqp.vhost',
        'teleframe.tstore.amqp.write_timeout',
        'teleframe.tstore.cache',
        'teleframe.tstore.cache_max_bytes',
        'teleframe.tstore.cache_ttl_seconds',
        'teleframe.tstore.listen',
        'teleframe.tstore.passphrase',
        'teleframe.tstore.peer_target',
        'teleframe.tstore.premium',
        'teleframe.tstore.queue',
        'teleframe.tstore.reference_policy',
        'teleframe.tstore.schedules',
        'teleframe.tstore.schedules_dsn',
        'teleframe.tstore.schedules_namespace',
        // Added 2026-09-30 with the S3 durable-vault tier (Cycle 38 — Goal D),
        // which promoted the S3 block from literals to CONFIG. Caught
        // UNCLASSIFIED by DeclaredKeyCoverageTest in GitLab pipeline 810, which
        // is the mechanism working rather than a chore.
        //
        // READ, and read as one block: TeleframeServiceProvider.php:726 does
        //   $s3 = $config->get('teleframe.tstore.s3');
        // and :729-734 index each leaf straight into the AwsSdkS3Gateway
        // constructor — the (B) parent-block-plus-leaf-index shape that
        // DeadKeyRegressionTest::readersOf() counts as a reader. Nothing is
        // folded into boolean algebra and nothing is discarded, so this is
        // READ and not READ_BUT_IGNORED.
        //
        // Not READ_CONDITIONAL either, and the amqp.* rows above are the
        // precedent: those are read only inside `if ($driver === 'amqp')` and
        // are still READ. The block is consumed whenever the S3GatewayContract
        // is resolved, which is a resolution-time posture, not a sibling-key
        // gate on the reader.
        //
        // Downstream, every one of the six reaches behaviour, not just the
        // constructor: AwsSdkS3Gateway::client() :121-130 passes region,
        // endpoint, use_path_style, key and secret to the S3Client, and
        // get/put/delete/list (:64-107) all read $this->config['bucket'].
        // Consumers are MinioHotCache (the TStore hot cache) and the new
        // S3DurableVault backup tier.
        'teleframe.tstore.s3.bucket',
        'teleframe.tstore.s3.endpoint',
        'teleframe.tstore.s3.key',
        'teleframe.tstore.s3.region',
        'teleframe.tstore.s3.secret',
        'teleframe.tstore.s3.use_path_style',
        'teleframe.user_session',
        'teleframe.vault.default_account',
        'teleframe.webhook_require_secret',
        'teleframe.webhook_secret',
    ];

    /**
     * DEAD — 5 keys. Read by nothing, anywhere in src/ or bin/.
     *
     * THE FIVE THAT REMAIN ARE A DELIBERATE RULING, NOT AN OVERSIGHT. They
     * name the core layer's Redis wire keys, and AGENTS.md declares those
     * opaque state: "do not rename tg:stream:updates / group teleclient /
     * tg:bus:routes / DL / reload channel (forward compat with the archived
     * teleclient)". The code reads StreamSchema::* and
     * ChannelRegistry::PREFIX_STORE as class constants, so the values are
     * owned by the class that defines them, NOT by a config row that a host
     * may repoint at its own Redis.
     *
     * That distinction is the point. A config key here is not a tuning knob,
     * it is a way to make this package speak a DIFFERENT wire protocol to a
     * DIFFERENT broker than every other teleframe process in the same host
     * speaks — including the archived teleclient, whose state is still in
     * that Redis and must stay readable. So "just wire it" would be a
     * compatibility break dressed as a feature: the honest states are
     * (a) leave the keys declared and honestly labelled inert (today), or
     * (b) remove them, which is a breaking config change.
     * Wiring them is the one option that is not on the table without an
     * explicit decision, and it is left for whoever owns the wire layer.
     *
     * @var list<string>
     */
    public const DEAD = [
        'teleframe.bus.group',
        'teleframe.bus.redis_client',
        'teleframe.bus.reload_channel',
        'teleframe.bus.stream',
        'teleframe.tstore.bucket_prefix',
    ];

    /**
     * READ-BUT-IGNORED — 0 keys.
     *
     * WAS ONE: teleframe.store.backup.enabled. It was read at two sites and
     * both folded it into `master || schedule.enabled` and then gated on
     * `! enabled || ! scheduled`, which simplifies to `! schedule.enabled` —
     * the master term was ABSORBED, so with the shipped schedule.enabled=true
     * the key could never turn anything off. The class a grep cannot see.
     *
     * FIXED 2026-09-27: the provider now has one reader
     * (StoreBackupServiceProvider::masterEnabled()) and both call sites gate on
     * its value with AND. It is READ, and
     * tests/Config/BackupMasterSwitchTest.php now FAILS if the `||` ever comes
     * back — the guard was inverted along with the code, deliberately.
     *
     * Kept as a named class (not deleted) because the class itself is the
     * finding, and a future key can join it. The empty list is the signal.
     *
     * @var list<string>
     */
    public const READ_BUT_IGNORED = [];

    /**
     * READ-CONDITIONAL — 5 keys, reachable only on a posture the reader
     * gates on a sibling key.
     *
     * target.peer_id / target.access_hash / target.peer_type /
     * target.mode / target.members are read by the same reader
     * (PeerTargetRegistry::targetFromRow, fed by
     * StoreBackupServiceProvider::targetRows() :460-482), and that reader is
     * only handed the singular target block when it carries a peer id
     * (:476-479). So all five are inert on the shipped owned-channel posture
     * and live only once a host sets peer_id.
     *
     * peer_id and access_hash were ADDED to the config file by this audit:
     * the reader already consumed them, but no config file declared them, so
     * the documented fallback was unreachable by configuration alone — the
     * same defect shape as the task_queue key Cycle 19 recorded.
     *
     * @var list<string>
     */
    public const READ_CONDITIONAL = [
        'teleframe.store.backup.target.access_hash',
        'teleframe.store.backup.target.members',
        'teleframe.store.backup.target.mode',
        'teleframe.store.backup.target.peer_id',
        'teleframe.store.backup.target.peer_type',
    ];

    /**
     * Containers whose SHAPE is only meaningful once a host fills them, so
     * the flattening walk stops at the container and these are audited as
     * leaves. A host that populates one adds real leaf keys, which the
     * coverage test deliberately ignores (they are host data, not package
     * declarations).
     *
     * @var list<string>
     */
    public const ROW_CONTAINERS = [
        'teleframe.app.apps',
        'teleframe.daemon.accounts',
        'teleframe.relay.accounts',
        'teleframe.store.backup.database.tables',
        'teleframe.store.backup.targets',
        'teleframe.supervisor.env',
    ];

    /** @return array<string, string> key => class */
    public static function classification(): array
    {
        $out = [];
        foreach (self::READ as $k) {
            $out[$k] = self::CLASS_READ;
        }
        foreach (self::READ_CONDITIONAL as $k) {
            $out[$k] = self::CLASS_READ_CONDITIONAL;
        }
        foreach (self::DEAD as $k) {
            $out[$k] = self::CLASS_DEAD;
        }
        foreach (self::READ_BUT_IGNORED as $k) {
            $out[$k] = self::CLASS_READ_BUT_IGNORED;
        }

        return $out;
    }

    /** @return list<string> */
    public static function dishonest(): array
    {
        return array_merge(self::DEAD, self::READ_BUT_IGNORED, self::READ_CONDITIONAL);
    }

    public static function configDir(): string
    {
        return dirname(__DIR__, 2).'/src/Laravel/config';
    }

    public static function repoRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * Every leaf key the config files actually declare, flattened under the
     * merge prefix each provider uses.
     *
     * @return list<string>
     */
    public static function declaredKeys(): array
    {
        $out = [];

        // The config files reference two real package classes (StreamSchema,
        // SchemaRegenerator) and Laravel's env() helper. Both resolve under the
        // composer autoloader the phpunit bootstrap registers. Only the VALUES
        // are env-shaped; the KEY STRUCTURE is not, so this stays
        // environment-independent the way the repo's other tests demand.
        foreach (self::FILES as $prefix => $file) {
            $path = self::configDir().'/'.$file;
            self::flatten((array) require $path, $prefix, $out);
        }

        $keys = array_values(array_unique($out));
        sort($keys);

        return $keys;
    }

    /**
     * @param  list<string>  $out
     */
    private static function flatten(array $array, string $prefix, array &$out): void
    {
        foreach ($array as $key => $value) {
            $path = $prefix.'.'.$key;

            // A list (or a row container) is host data, not a declaration.
            if (is_array($value) && $value !== [] && ! array_is_list($value)) {
                self::flatten($value, $path, $out);

                continue;
            }

            $out[] = $path;
        }
    }

    /**
     * The comment block documenting a declared key, so a test can ask whether
     * the key SAYS what it is.
     *
     * Block-comment state is resolved in a FORWARD pass (a backwards walk
     * cannot tell whether the line above is inside a block comment, because it
     * meets the closing marker before the opener), then the block is collected
     * by walking back over the contiguous run of comment lines. Blank lines
     * count while inside a block comment, because these config files separate
     * the paragraphs of ONE comment with them.
     *
     * @return string the comment text, or '' when the key has no comment above it
     */
    public static function commentAbove(string $file, string $needle): string
    {
        $lines = file(self::configDir().'/'.$file);
        if ($lines === false) {
            return '';
        }

        $isComment = self::commentMask($lines);

        foreach ($lines as $i => $line) {
            // A DECLARED key only — a commented-out example row (the shape
            // docs in the 'targets' block use) is not a declaration, and
            // matching one first would read that example's comment instead.
            if ($isComment[$i] || ! str_contains($line, $needle)) {
                continue;
            }

            $block = [];
            for ($j = $i - 1; $j >= 0 && $isComment[$j]; $j--) {
                $block[] = $lines[$j];
            }

            return implode('', array_reverse($block));
        }

        return '';
    }

    /**
     * Does a key's comment SAY that the key is dead, inert, read-but-ignored,
     * or only conditionally read?
     *
     * Case-insensitive on purpose: the marker is prose written by a human, so
     * pinning one capitalisation would turn this into a formatting lint instead
     * of an honesty check.
     */
    public static function marksAsDishonest(string $comment): bool
    {
        $haystack = strtolower($comment);

        $markers = [
            'dead',
            'inert',
            'read-but-ignored',
            'read by nothing',
            'read nothing',
            'read only',
            'conditionally read',
        ];

        foreach ($markers as $marker) {
            if (str_contains($haystack, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $lines
     * @return list<bool> is each line part of a comment
     */
    private static function commentMask(array $lines): array
    {
        $mask = [];
        $inBlock = false;

        foreach ($lines as $line) {
            $t = ltrim($line);
            $mask[] = $inBlock
                || str_starts_with($t, '*')
                || str_starts_with($t, '//')
                || str_starts_with($t, '#')
                || str_starts_with($t, '/*');

            $open = substr_count($line, '/*');
            $close = substr_count($line, '*/');
            $inBlock = ($inBlock + $open - $close) > 0;
        }

        return $mask;
    }
}
