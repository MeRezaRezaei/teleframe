<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\App;

use MeRezaRezaei\Teleframe\Laravel\Facades\TelegramApp;

/**
 * AppSupport — the plain-PHP bind-key anchor for the App framework module
 * (design §1/§2/G2, Notify FacadeSupport precedent). Holds the fixed
 * `teleclient.*` container seams so the Laravel facade, the provider and
 * the CLI command all resolve the same keys without the core module
 * depending on a service provider or an artisan Command class.
 *
 * Keys are opaque wire names (AGENTS.md: fixed `teleclient.*` convention,
 * never renamed): the service key is the facade accessor, the registry
 * key is the name⇒AppDefinition table, the transport key is the
 * callable(AppMode): mixed resolver seam (null default — TeleframeClient
 * bot/user at first use), and the schedules key is the SchedulePublisher
 * seam (NullSchedulePublisher default, Temporal later — the same posture
 * as `teleclient.notify.schedules` / `teleclient.store.backup.schedules`).
 *
 * Credentials ruling (design §6): every seam resolves credentials at call
 * time; these keys never carry session strings or tokens.
 *
 * @see TelegramApp
 */
final class AppSupport
{
    /** Container seam: AppRuntime (built by the AppServiceProvider). */
    public const SERVICE_KEY = 'teleclient.app.service';

    /** Container seam: AppRegistry (seeded from teleframe.app.apps at boot). */
    public const REGISTRY_KEY = 'teleclient.app.registry';

    /** Container seam: callable(AppMode): mixed (default TeleframeClient bot/user). */
    public const TRANSPORT_KEY = 'teleclient.app.transport';

    /** Container seam: SchedulePublisher (NullSchedulePublisher default, Temporal later). */
    public const SCHEDULES_KEY = 'teleclient.app.schedules';
}
