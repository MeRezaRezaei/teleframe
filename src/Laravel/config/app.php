<?php

/*
|--------------------------------------------------------------------------
| Teleframe App Framework — declarative bot/user apps (design 2026-09-24 §7)
|--------------------------------------------------------------------------
| Merged into config under the `teleframe.app` key by AppServiceProvider
| (this package's OWN provider — registered via composer.json
| extra.laravel.providers, deliberately NOT hooked into
| TeleframeServiceProvider). Hosts may publish and override any key; the
| facade resolves `teleclient.app.service` from these.
|
| The verbatim's default program surface: a developer declares an app
| (name, mode bot|user, chat triggers → handler callbacks, scheduled
| telegram calls) in plain PHP through `TelegramApp::define()`/`register()`
| or as a seed row here — the framework registers the triggers onto the
| SHIPPED handler pipeline (tag 'app:{name}') and composes the recurring
| calls onto the EXISTING SchedulePublisher seam.
|
| Runtime posture: rows carry names, ids, usernames, peer refs only —
| NEVER session strings, auth keys or tokens. Sessions are resolved at
| call time through the injected transport (vault scopes, daemon pattern).
*/

return [

    /* AppDefinition seeds — registered at provider boot (G6). Rows map 1:1
       onto AppDefinition::define(): name/mode/triggers/calls. Triggers and
       calls are declared as arrays of the same data rows the facade takes.

       A trigger row: {match, handler, priority?, on_own?} — match uses the
       SHIPPED matcher grammar (*, exact, FooBar*, /start %s; zero extras);
       handler is a PSR-11 invokable string, [Class::class, 'method'], or a
       closure (config files are executed PHP, so closures are legal rows).
       A call row: {name, interval_seconds, method, params?} — method is a
       catalog name (messages.sendMessage, or a Bot API method for bot
       mode); params are ids/usernames/peer refs, never sessions.

       Invalid rows throw loud at resolve time (no silent drop). */
    'apps' => [
        // [
        //     'name' => 'echo-bot',
        //     'mode' => 'bot',            // AppMode: bot|user
        //     'triggers' => [
        //         ['match' => 'updateNewMessage', 'handler' => \App\Telegram\EchoBotHandler::class, 'priority' => 0, 'on_own' => false],
        //     ],
        //     'calls' => [
        //         ['name' => 'status-ping', 'interval_seconds' => 3600, 'method' => 'messages.sendMessage', 'params' => ['peer' => '@ops', 'message' => 'alive']],
        //     ],
        // ],
    ],

    /* Schedule seam: 'null' (off — probe only) or 'temporal'.
       The app-call schedules land on this seam, like backup/tstore.

       'temporal' needs the Temporal PHP SDK, which is an OPTIONAL runtime
       dependency of this package (require-dev here, on purpose). With
       `composer require temporal/sdk` present, the package builds its own
       SDK-backed recurring client and arms each call's interval_seconds with
       no code of yours. An app that binds
       MeRezaRezaei\Teleframe\Store\Schedules\RecurringScheduleClient WINS
       over that, verbatim; bind the one-shot
       ...\Schedules\TemporalScheduleClient instead and only a single run per
       install happens. With neither the SDK nor a binding, the package REFUSES
       AT BOOT by name and never degrades to 'off'. */
    'schedules' => env('TELEFRAME_APP_SCHEDULES', 'null'),
    /* Frontend address handed to your bound client. Nothing in this package
       dials it. */
    'schedules_dsn' => env('TELEFRAME_APP_TEMPORAL_DSN', '127.0.0.1:7233'),
    /* Task queue the temporal schedules land on. Read by the provider; it was
       previously read from a key that no config file defined, so it could only
       ever have been the default. */
    'schedules_task_queue' => env('TELEFRAME_APP_TEMPORAL_TASK_QUEUE', 'default'),

    /* Optional transport resolver override — a callable(AppMode): mixed.
       Absent: the provider builds the default from TeleframeClient
       (bot → TeleframeClient::bot(), user → TeleframeClient::user() —
       resolved at call time; unconfigured throws loud, never silent-drop). */
    'transport' => null,
];
