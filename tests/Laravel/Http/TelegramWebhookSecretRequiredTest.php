<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Http;

use Illuminate\Support\Facades\Route;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\TelegramWebhookController;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * AN UNCONFIGURED WEBHOOK MUST NOT ACCEPT INJECTED UPDATES.
 *
 * The controller already compared the secret correctly — constant time, and
 * BEFORE acting on the payload, both right. The gap was the case with no
 * secret at all: the comparison was skipped, so the endpoint quietly became an
 * unauthenticated write into the host's update pipeline. Anyone who learned the
 * URL could POST a fabricated `update_id` and have it dispatched into handlers,
 * the mirror, a Centrifugo publish and a TStore write, with nothing to
 * distinguish it from a real update.
 *
 * Silence is the part that made it dangerous. A first-release host that
 * installed the package, wired the webhook, and forgot the secret looked
 * completely healthy: requests returned 200, the process was up, and every
 * health signal was green while the endpoint was open.
 *
 * The default is now to refuse, with an explicit opt-out for a host that
 * already authenticates callers in front of the webhook.
 */
final class TelegramWebhookSecretRequiredTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('v', 32)));
    }

    // ---- the gap that was open -----------------------------------------

    public function test_with_no_secret_configured_the_webhook_refuses_the_update(): void
    {
        $this->app['config']->set('teleframe.webhook_secret', null);
        $this->app['config']->set('teleframe.webhook_require_secret', true);
        $this->registerRoute();

        $response = $this->postJson('/tg-webhook', ['update_id' => 1, 'message' => ['id' => 1]]);

        // 503, not 403: 403 means "your credential is wrong" and would send the
        // operator hunting for a bad secret that does not exist, while telling
        // Telegram the rejection is permanent.
        $response->assertStatus(503);
        $response->assertJsonPath('error', fn (string $e): bool => str_contains($e, 'TELEGRAM_WEBHOOK_SECRET'));
    }

    public function test_a_refused_webhook_dispatches_nothing(): void
    {
        $this->app['config']->set('teleframe.webhook_secret', null);
        $this->app['config']->set('teleframe.webhook_require_secret', true);
        $this->registerRoute();

        \Illuminate\Support\Facades\Event::fake([\MeRezaRezaei\Teleframe\Laravel\Events\TelegramUpdateReceived::class]);

        $this->postJson('/tg-webhook', ['update_id' => 99, 'message' => ['id' => 1]])
            ->assertStatus(503);

        \Illuminate\Support\Facades\Event::assertNotDispatched(
            \MeRezaRezaei\Teleframe\Laravel\Events\TelegramUpdateReceived::class,
            'a refused webhook must not put a fabricated update into the host pipeline',
        );
    }

    // ---- the three working paths ---------------------------------------

    public function test_a_configured_secret_still_accepts_a_matching_header(): void
    {
        $this->app['config']->set('teleframe.webhook_secret', 's3cret-value');
        $this->registerRoute();

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 's3cret-value')
            ->postJson('/tg-webhook', ['update_id' => 2])
            ->assertStatus(200)
            ->assertJson(['ok' => true]);
    }

    public function test_a_configured_secret_still_rejects_a_wrong_header(): void
    {
        $this->app['config']->set('teleframe.webhook_secret', 's3cret-value');
        $this->registerRoute();

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'wrong-value')
            ->postJson('/tg-webhook', ['update_id' => 3])
            ->assertStatus(403);
    }

    public function test_a_host_that_fronts_the_webhook_may_opt_out_explicitly(): void
    {
        // A reverse-proxy IP allowlist, mTLS, or a private network makes this a
        // considered decision rather than an accident. The opt-in must work —
        // otherwise the fix is just a way to break a legitimate deployment.
        $this->app['config']->set('teleframe.webhook_secret', null);
        $this->app['config']->set('teleframe.webhook_require_secret', false);
        $this->registerRoute();

        $this->postJson('/tg-webhook', ['update_id' => 4])
            ->assertStatus(200)
            ->assertJson(['ok' => true]);
    }

    // ---- helpers ---------------------------------------------------------

    private function registerRoute(): void
    {
        Route::post('/tg-webhook', TelegramWebhookController::class)->middleware('web');
    }
}
