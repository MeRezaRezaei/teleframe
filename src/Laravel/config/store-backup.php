<?php

/*
|--------------------------------------------------------------------------
| Teleframe Store Backup — the DB-dump surface (design 2026-09-24 §7)
|--------------------------------------------------------------------------
| Merged into config under the `teleframe.store.backup` key by
| StoreBackupServiceProvider (this package's OWN provider — registered via
| composer.json extra.laravel.providers, deliberately NOT hooked into
| TeleframeServiceProvider). Hosts may publish and override any key; the
| facade resolves `teleclient.store.backup.service` from these.
|
| The verbatim's default install: a Laravel host dumps its database on a
| schedule into a per-bucket Telegram channel (sealed by default), keeps a
| run ledger, and heartbeats the Notify gap watchdog so a missed dump
| alerts on `backup.missing`.
|
| Runtime posture: DB credentials are env-at-run ONLY (MYSQL_PWD /
| PGPASSWORD in the forked dump process env — ps-safe) — never argv,
| never config, never manifests/index rows/queue payloads/logs. There is
| deliberately NO TELEFRAME_STORE_BACKUP_PASSWORD key, ever.
*/

return [

    /* MASTER SWITCH — gates the backup WATCHDOG. false = no watchdog
       registration, and the hourly `teleframe:notify watch --check` is not
       scheduled either. The DUMP still runs: this is not a kill switch for
       teleframe:tstore backup, only for watching it.

       StoreBackupServiceProvider has ONE reader of this key — masterEnabled() —
       and both call sites require it to be true:
         masterEnabled()                returns this key's own value
         backupWatchSource()            registers the 'backup' expectation,
                                         so false = no expectation to miss
         scheduleBackupWatchdogCheck()  false = no hourly check scheduled
       (backupWatchSource's `! master || ! scheduled` is De Morgan over two
       INDEPENDENT conditions — both must hold. The two switches are never
       folded into one term, which is what used to absorb this key.)

       WHAT false DOES NOT DO, and does not claim to: it does not disable the
       dump, and it does not stop a successful run recording its heartbeat —
       the producer side is DatabaseBackupService's touch, which reads no
       config. What stops is the EXPECTATION and the CHECK: nothing looks for
       a missing dump and nothing polls. This switch governs the watchdog, not
       the module — to stop the module itself, use schedule.enabled below
       (which also silences the watchdog registration) or drop the binding.

       BEHAVIOUR CHANGE (dead-config audit 2026-09-27, deliberate): this key
       used to be folded into `master || schedule.enabled` and then gated on
       `! enabled || ! schedule.enabled`, and that reduces to
       `! schedule.enabled` — the master term was absorbed, so setting this to
       false with the shipped schedule.enabled=true skipped NOTHING: the
       expectation was still registered and the hourly check still scheduled.
       A host that set it false was paying for a watchdog it had switched off.
       It now gets neither, which is what it asked for. The change is visible
       (the entries simply are not registered) rather than silent-but-present.

       Default true, so a default install is unaffected. */
    'enabled' => (bool) env('TELEFRAME_STORE_BACKUP_ENABLED', true),

    'schedule' => [
        /* Per-schedule switch (design §5: the default install ships it ON
           — the watchdog heartbeat is live from day one, no Temporal
           required). GATES THE WATCHDOG REGISTRATION: with this false the
           'backup' heartbeat is not expected, so no missing dump is ever
           reported, whatever the master switch says.

           This comment used to say it was the only working switch "because
           the master `enabled` above is absorbed by the readers' boolean
           algebra". That absorption is gone — the master switch above is read
           honestly now (see its comment) and BOTH must be true for the
           watchdog to be armed. This key is the one that also stays false
           when a host only wants on-demand dumps with no no-show alerting. */
        'enabled' => (bool) env('TELEFRAME_STORE_BACKUP_SCHEDULE_ENABLED', true),
        /* Daily default (verbatim: "backup ... in schedules"). */
        'interval_seconds' => (int) env('TELEFRAME_STORE_BACKUP_INTERVAL', 86400),
        /* SchedulePublisher seam: 'null' (off — probe only) or 'temporal'.
           NULL DEFAULT = the one-line cron posture:
           `0 0 * * * cd $APP && php artisan teleframe:tstore backup`.

           READ THIS BEFORE SETTING IT TO 'temporal'. 'temporal' needs the
           Temporal PHP SDK, an OPTIONAL runtime dependency of this package
           (require-dev here, on purpose). With `composer require
           temporal/sdk` present the package builds its own SDK-backed
           recurring client and arms interval_seconds with no code of yours. An
           app that binds
           MeRezaRezaei\Teleframe\Store\Schedules\RecurringScheduleClient in the
           container WINS over that, verbatim; bind the one-shot
           ...\Schedules\TemporalScheduleClient instead and it can only start a
           single run per install. With neither, the key REFUSES AT BOOT by
           name and never degrades to 'off' — keep 'null' + cron until then. */
        'publisher' => env('TELEFRAME_STORE_BACKUP_SCHEDULES', 'null'),
        /* THE PACKAGE-OWNED TRIGGER (F7, 2026-10-13) — added because this was
           the only capability with no trigger the package owns: the `publisher`
           above defaults to 'null', so on a default install NOTHING in the
           package ever fires `teleframe:tstore backup`. The operator named the
           action — "so it can simply dump the laravel database and make a
           backup from it in schedules" — and the honest reading of that is
           `php artisan schedule:run`, not a host hand-written crontab.

Turn this ON and the package registers the dump on Laravel's own
            scheduler (StoreBackupServiceProvider::scheduleBackupDump(), console
            boot, alongside the hourly no-show check). Delete your cron line in
            the same change — see DOUBLE-FIRE below.

            Turning this ON IS ALSO THE DECLARATION THE WATCHDOG WAITS FOR
            (F13-1). The no-show expectation on the 'backup' heartbeat is armed
            only when a dump trigger is DECLARED, and this key is one of the two
            ways to declare it (the other is schedule.external_trigger below).
            Evidence that the trigger fires, so the expectation is not a guess.

           DOUBLE-FIRE — READ BEFORE ENABLING. Default is FALSE and it has to
           stay that way, because the two postures are invisible to each other:
           the shipped posture a host may already have is the one-line cron
           above, cron is invisible to the Laravel scheduler, and no reading of
           any config key can discover it. So there is NO way for the package to
           prove that defaulting this on cannot double-fire — and a double-fired
           dump is not a harmless duplicate, it is two full database dumps a day
           into the same bucket with two run-ledger rows. `withoutOverlapping()`
           and `onOneServer()` protect the package's own trigger against a
           second scheduler or a second app server; they do nothing against a
           crontab. Enabling this while your cron line still exists is the one
           mistake this key cannot protect you from, and it is the operator's
           to avoid.

           THE MASTER SWITCH DOES NOT GATE THIS, deliberately. `enabled` above
           governs the WATCHDOG ("what false DOES NOT DO: it does not disable
           the dump"), so making the dump trigger depend on it would silently
           repurpose a documented switch. This trigger is gated on its own key
           and on nothing else. */
        'package_schedule' => [
            'enabled' => (bool) env('TELEFRAME_STORE_BACKUP_PACKAGE_SCHEDULE_ENABLED', false),
            /* H:i, 24-hour. The one time of day the package-owned trigger
               fires, chosen off the file-vault sibling's 03:17 so a host
               running both does not stack two database dumps on one minute.
               An unparseable value THROWS at console boot by name rather than
               registering at a time you did not ask for — a backup that
               silently runs at the wrong minute is the same lie as one that
               silently never runs. */
            'daily_at' => env('TELEFRAME_STORE_BACKUP_PACKAGE_SCHEDULE_DAILY_AT', '03:47'),
        ],
        /* THE TRIGGER DECLARATION (F13-1, 2026-10-16) — the answer to the
           question the key above cannot answer FOR THE WATCHDOG: does
           something actually fire the dump, and does the package KNOW it?

           Read by StoreBackupServiceProvider::triggerArmed(), which gates the
           'backup' no-show EXPECTATION. `true` = something outside Laravel's
           scheduler fires the dump: a crontab line, a systemd timer, a Temporal
           workflow armed by `teleframe:tstore schedule --install`, a person.
           Declare it and the watchdog watches the 'backup' heartbeat against
           that trigger, alerting on `backup.missing` when it goes quiet.

           WHY IT EXISTS — THE FALSE-ALARM DEFAULT. Before it, a stock install
           (enabled=true, schedule.enabled=true) registered the 'backup'
           expectation and the hourly check, expecting a heartbeat every 48h,
           while nothing the package ships could produce one: the package-owned
           trigger is default-OFF (DOUBLE-FIRE above) and `publisher` is
           'null', so the dump was supposed to come from a crontab the package
           cannot see. Every host that followed the shipped instructions was
           told "backup missing" forever, about a backup that was running fine.
           An alarm you can silence only by deleting the thing it watches is an
           alarm nobody keeps — and the audit that found it was right that the
           default-OFF ruling itself is sound. The alarm was what was left out
           of it.

           DEFAULT false, and false is the honest direction: the package cannot
           discover a crontab, so with no evidence of a trigger it does not
           pretend to be waiting for one. Silence is the absence of a false
           alarm. Opt IN to the alert by declaring the trigger you actually
           run; schedule.package_schedule.enabled is the other declaration,
           because turning the package's own trigger on is itself the evidence
           that a dump will fire, so it needs no second key.

           WHAT false DOES NOT DO: it does not stop a dump — nothing in this
           block is on the dump path — and it does not stop a successful run
           recording its heartbeat (the producer side is
           DatabaseBackupService's touch, which reads no config). It means
           nothing is EXPECTING that heartbeat, so no missing-dump alert can be
           raised from it. Turn it on when a dump really is firing and you want
           the no-show alert; leave it off when dumps are run by hand and you do
           not want one. Verify either way with
           `php artisan teleframe:tstore schedule`, which prints whether
           "backup" is registered. */
        'external_trigger' => (bool) env('TELEFRAME_STORE_BACKUP_EXTERNAL_TRIGGER', false),
        /* Frontend address handed to your bound Temporal client. Nothing in
           this package dials it — your TemporalScheduleClient does. */
        'dsn' => env('TELEFRAME_STORE_BACKUP_TEMPORAL_DSN', '127.0.0.1:7233'),
        /* Task queue your bound client posts the schedules to. Read by the
           provider; it was previously read from a key no config file defined,
           so it could only ever have been the default. */
        'task_queue' => env('TELEFRAME_STORE_BACKUP_TEMPORAL_TASK_QUEUE', 'default'),
        /* Watchdog grace: expect-every = interval × this (default one
           missed cycle + grace); floor 3600 s enforced in the provider. */
        'watchdog_expect_multiplier' => (int) env('TELEFRAME_STORE_BACKUP_WATCHDOG_MULT', 2),
    ],

    /* WHERE A NO-SHOW FINDING IS WRITTEN DOWN (F8, 2026-10-13) — added because
       the watchdog's alert rode the queue it was watching: emit() enqueued on
       `{topic}.missing` through the same queue and transport as the heartbeat
       it watches, so a queue or transport failure — the operator's own scenario
       — took the alert with it, and all that survived was a PSR-3 line only
       the host can see. The finding is now written here FIRST, unconditionally,
       and the Telegram notification is an enhancement on top of it.

       Read by StoreBackupServiceProvider::registerWatchdog(), which binds a
       FileWatchAlertSink: one JSON object per line, appended, never rewritten
       (the directory is created 0700 on first write, the file 0600). It is the
       dumbest durable thing available on purpose — no cache, no queue, no
       database, no network — so its failure domain cannot be the failure being
       reported.

       '' = storage/teleframe/watchdog-alerts.jsonl under the host, falling
       back to the system temp directory when there is no application base path
       (a bare console). Point it at a mounted volume if system temp is
       ephemeral, or at whatever your log shipper already tails. */
    'watchdog' => [
        'alert_log' => env('TELEFRAME_STORE_BACKUP_WATCHDOG_ALERT_LOG', ''),
    ],

    'target' => [
        /* 0 = reuse teleframe.tstore.account_id (the MTProto account whose
           dialogs host the store buckets). */
        'account_id' => (int) env('TELEFRAME_STORE_BACKUP_ACCOUNT', 0),
        /* Bucket name → the registry title is 'teleframe-backup:'.$bucket
           (ChannelRegistry::PREFIX_BACKUP). */
        'bucket' => env('TELEFRAME_STORE_BACKUP_BUCKET', 'default'),
        /* 0 = the OWNED-channel posture: no peer identity, so the provider
           does not build a PeerTargetRegistry row from this block at all
           (StoreBackupServiceProvider::targetRows() returns [] unless a peer
           id is present) and DatabaseBackupService acquires the
           channel itself.

           ADDED (dead-config audit 2026-09-27). peer_id/access_hash were read
           by targetRows() and PeerTargetRegistry::targetFromRow() but were
           NOT DECLARED here, so the documented singular-block fallback was
           unreachable by configuration alone. Same defect shape as the
           task_queue key Cycle 19 recorded. Set peer_id to the peer id and
           access_hash to its access hash to route the default row at an
           existing channel/group; leave both 0 for the owned channel.

           READ only in combination with access_hash below, and only on the
           non-owned-peer posture. */
        'peer_id' => (int) env('TELEFRAME_STORE_BACKUP_PEER_ID', 0),
        // The access hash that goes with peer_id above (non-numeric is
        // refused loudly by PeerTargetRegistry::targetFromRow()).
        // READ only in combination with peer_id above.
        'access_hash' => (int) env('TELEFRAME_STORE_BACKUP_ACCESS_HASH', 0),
        /* channel|group — wave-2 non-bot peers; slice 1 = the owned
           broadcast channel. READ ONLY when peer_id above is non-zero (it is
           then consumed by PeerTargetRegistry, which refuses any value
           outside channel|group); ignored entirely on the owned-channel
           posture, which is always sealed. */
        'peer_type' => env('TELEFRAME_STORE_BACKUP_PEER_TYPE', 'channel'),
        /* Sealed default ("put pass on whatever you send there"). Read on
           BOTH postures: DatabaseBackupService takes it as the ctor secret
           (StoreBackupServiceProvider::registerService()). */
        'passphrase' => env('TELEFRAME_STORE_BACKUP_PASSPHRASE', ''),
        /* raw|sealed, default sealed. Read only on the peer_id posture, by
           PeerTargetRegistry (:158) — a group in raw mode is refused. The
           owned-channel posture is always sealed regardless of this value. */
        'mode' => env('TELEFRAME_STORE_BACKUP_MODE', 'sealed'),
        /* Wave 2: int[]|string[] user ids/usernames to invite — never
           session strings. Read only on the peer_id posture, by
           PeerTargetRegistry (:168). */
        'members' => [],
    ],

    /* Wave 2-D multi-target rows (design §6 channel/group targets). Each
       row is ONE backup destination beyond the singular 'default' target
       above — bucket (registry title suffix, ChannelRegistry::PREFIX_BACKUP
       . $bucket), peer_id + access_hash (the dialed inputPeerChannel),
       peer_type channel|group, mode, the seal passphrase, and members.

       peer_type: 'channel' (owned broadcast channel OR a channel the
       account was added to) | 'group' (supergroup the account joined or
       was invited into) — both dial as inputPeerChannel, so the existing
       send path works unchanged; what differs is membership semantics.

       members: int[]|string[] user ids/usernames to invite via
       PeerMembership::ensureMember (channels.inviteToChannel) — NEVER
       session strings.

       Non-owned peers REQUIRE mode=sealed ("put pass on whatever you send
       there") — a row IS a decision; config-merge refuses raw for them.
       The singular 'target' block stays the back-compat default: when
       'targets' is empty the provider builds the registry from the
       singular block (i.e. the default row), so existing installs are
       untouched. */
    'targets' => [
        // [
        //     'bucket' => 'nightly',
        //     'peer_id' => 0,
        //     'access_hash' => 0,
        //     'peer_type' => 'channel',   // channel|group
        //     'mode' => 'sealed',
        //     'passphrase' => env('TELEFRAME_STORE_BACKUP_TARGET_NIGHTLY_PASSPHRASE', ''),
        //     'members' => [],            // int[]|string[] user ids/usernames to invite
        // ],
    ],

    'database' => [
        /* '' = unconfigured (read the host DB config at run time). The
           provider binds a dumper that still lets the service construct
           and throws loud on first dump() — no silent no-op. */
        'driver' => env('TELEFRAME_STORE_BACKUP_DB_DRIVER', ''),
        'host' => env('TELEFRAME_STORE_BACKUP_DB_HOST', ''),
        'port' => (int) env('TELEFRAME_STORE_BACKUP_DB_PORT', 0),
        'database' => env('TELEFRAME_STORE_BACKUP_DB_DATABASE', ''),
        'user' => env('TELEFRAME_STORE_BACKUP_DB_USER', ''),
        'socket' => env('TELEFRAME_STORE_BACKUP_DB_SOCKET', ''),
        /* Optional table whitelist; [] = whole database. */
        'tables' => [],
        // password: env-only at run time (MYSQL_PWD / PGPASSWORD) — never in config/rows/logs
    ],

    /* WHERE A DUMP IS SPOOLED before it is uploaded. This comment used to be a
       DEAD warning (dead-config audit 2026-09-27): the key was read by nothing,
       because the only DumpWorkspace the backup path built was
       `new DumpWorkspace` with NO argument, so $dir was always '' and every
       dump landed in sys_get_temp_dir()/teleframe-dumps — never in host
       storage. The DumpWorkspace docblock repeated the same false claim.

       IT IS NOW WIRED. Chain, all three hops real:
         this key
           → StoreBackupServiceProvider::registerService() passes spoolDir:
           → DatabaseBackupService::__construct(… , string $spoolDir = '')
           → backup() builds `new DumpWorkspace($this->spoolDir)`
       So this comment no longer claims a default it does not have: a
       configured value IS the directory used, and '' (the default) means
       the sys_get_temp_dir()/teleframe-dumps fallback, verbatim.

       LAZY BY CONSTRUCTION, deliberately: nothing reads this at register()
       time, no directory is resolved or created, and the mkdir(0700) still
       happens on the first spool() — so a wrong path fails loud at the first
       dump instead of at boot, and the smoke gate still constructs the
       module with no container. Point it at a host volume when system temp is
       too small, ephemeral, or on another filesystem. */
    'spool_dir' => env('TELEFRAME_STORE_BACKUP_SPOOL_DIR', ''),

    /* sync|amqp — the upload queue driver the BACKUP runs on; reuses the
       tstore upload queue seams (there is ONE queue: the backup dump is
       uploaded by the same StoreService the tstore seam builds, so this key
       decides which driver builds the shared teleclient.tstore.queue).
       Read by StoreBackupServiceProvider::registerUploadQueue().

       LEDGER RULING SATISFIED — THIS KEY IS WIRED (dead-config audit
       2026-09-27). docs/verbatim-goals.md still records
       TELEFRAME_STORE_BACKUP_QUEUE as "deliberately NOT wired ... to be
       either wired or removed, not silently left". That record is STALE:
       registerUploadQueue() exists and reads this key inside
       StoreBackupServiceProvider::registerUploadQueue().

       It is not merely read, it WINS. Container::bind() is last-write-wins
       on the abstract, and composer.json extra.laravel.providers lists
       TeleframeServiceProvider (which binds teleclient.tstore.queue) at
       index 0 and this package's StoreBackupServiceProvider (which rebinds
       the same key) at index 2 — so 'amqp' here overrides the tstore driver,
       and the tstore StoreService singleton resolves the queue lazily, after
       every provider has registered. No provider change is outstanding for
       this key.

       '' | 'sync' (the default) DEFER to teleframe.tstore.queue — a host's
       existing tstore config is untouched. 'amqp' names a driver and
       overrides it (broker options stay teleframe.tstore.amqp.*). Any other
       value throws LOUD at resolve time — a backup that quietly degrades to
       an inline upload is the failure this key exists to prevent. */
    'queue' => env('TELEFRAME_STORE_BACKUP_QUEUE', 'sync'),
];
