<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Queue;

use RuntimeException;

/**
 * AmqpExtension — the ONE place that answers "can this process speak AMQP?",
 * and the one place that holds the refusal when it cannot.
 *
 * The gap this closes: the answer used to be an inline
 * `class_exists(\AMQPConnection::class)` inside AmqpUploadQueue's own
 * constructor, so it was a property of the MACHINE and nothing else. A test
 * that constructed an amqp queue therefore inherited whatever extensions the
 * host happened to have — GitLab pipeline #206 failed with
 * "tstore.queue.driver=amqp selected but ext-amqp is not loaded" from a test
 * whose subject (the wiring) was never the extension. The same defect shape
 * had just been fixed for temporal/sdk by TemporalClientResolver, whose rule
 * is the rule here: the code decides, the caller (or a test) supplies the
 * fact, and the machine never picks the branch.
 *
 * RESOLUTION ORDER — fixed, and detection by default:
 *   1. A per-instance extension handed to the constructor
 *      (`new AmqpUploadQueue($config, $extension)`) wins. A host with a
 *      vendored AMQP shim knows things this package does not.
 *   2. Otherwise the ambient declaration, `AmqpExtension::declare()`.
 *   3. Otherwise DETECTION: `class_exists(\AMQPConnection::class)`, i.e. what
 *      this process actually has.
 *
 * WHY THE AMBIENT DECLARATION EXISTS (and it is not a test-only toy): both
 * provider wirings (the tstore seam in TeleframeServiceProvider and the
 * backup-queue seam in StoreBackupServiceProvider) construct this queue as
 * `new AmqpUploadQueue([...])` from a deferred container closure. The
 * container cannot reach a constructor argument, so with the answer computed
 * inline in the constructor there was nowhere to inject a fact at all — the
 * only honest way to reach those wirings was a static seam. Nothing in this
 * package calls declare(); an undeclared process behaves exactly as it did
 * before (detection, then the same refusal).
 *
 * THE REFUSAL IS NOT SOFTENED, IT IS ONLY MOVED TO A NAME. assertLoaded()
 * throws the same RuntimeException, at the same moment (construction), with
 * the same message. There is no silent degrade to SyncUploadQueue anywhere on
 * this path: a host that configured `amqp` and has no transport is told so by
 * name. missingMessage() is public so a doctor-style preflight can print the
 * install instruction without constructing anything.
 *
 * Plain-PHP, no container, no dial, zero regex.
 */
final class AmqpExtension
{
    /**
     * The ambient declaration, or null = detect. Deliberately private: the only
     * way to move it is declare()/detect(), so a reader can see every write.
     */
    private static ?bool $declared = null;

    private function __construct(private readonly bool $loaded) {}

    /**
     * SEAM (per-instance) — "this process can speak AMQP", supplied to a
     * constructor that takes an extension. Distinct from declare() on purpose:
     * this one is scoped to the object being built, that one to the process.
     */
    public static function present(): self
    {
        return new self(true);
    }

    /**
     * SEAM (per-instance) — "this process cannot speak AMQP", so a caller can
     * assert the refusal arm on a machine where the extension IS installed.
     */
    public static function absent(): self
    {
        return new self(false);
    }

    /**
     * The answer for right now: the ambient declaration when one is set,
     * detection otherwise. This is the value a bare `new AmqpUploadQueue($config)`
     * consults.
     */
    public static function detected(): self
    {
        return new self(self::$declared ?? class_exists(\AMQPConnection::class));
    }

    /**
     * SEAM — pin the answer for this process. `$loaded = true` models a host
     * whose AMQP transport is present (a vendored shim, a test that must prove
     * the amqp path on a CI image without ext-amqp); `$loaded = false` models a
     * machine without it, so the refusal arm can be asserted where the
     * extension IS installed instead of only where it happens to be missing.
     */
    public static function declare(bool $loaded): void
    {
        self::$declared = $loaded;
    }

    /**
     * Drop the ambient declaration and go back to detection. Callers that
     * declare (every test) restore this in tearDown — a leaked declaration
     * would silently disable the refusal for the rest of the process.
     */
    public static function detect(): void
    {
        self::$declared = null;
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * @throws RuntimeException when this process cannot speak AMQP
     */
    public function assertLoaded(): void
    {
        if ($this->loaded) {
            return;
        }

        throw new RuntimeException(self::missingMessage());
    }

    /**
     * The refusal text, in one place so the throw site and any preflight
     * cannot drift apart. Names the missing extension and both ways out.
     */
    public static function missingMessage(): string
    {
        return 'tstore.queue.driver=amqp selected but ext-amqp is not loaded — install ext-amqp or switch to the sync queue';
    }
}
