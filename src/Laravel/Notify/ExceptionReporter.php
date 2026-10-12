<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Notify;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * ExceptionReporter — the real send path from a host exception to Telegram
 * (Task 1, Cycle 19).
 *
 * The user asked, verbatim (2026-09-24): "if it is laravel exception the
 * exception should be rendered and the final html gets there and so on", and
 * called notification "the first thing and it's too important also it's the
 * core". The renderer (HtmlAttachment) shipped in an earlier wave, but
 * nothing fed it: an exception only reached Telegram when the host app
 * remembered to call Notify::report() by hand. The first version of this
 * class was the missing DECISION and nothing more — a describe() nobody
 * called, bound as decoration, while the config file told hosts the
 * notification core was "bound to the host's exception handler". Both halves
 * are now true: this class has a send path, and the provider wires it.
 *
 * THREE RULES, and they are the whole design:
 *
 *   1. IT IS INERT BY DEFAULT. enabled() reads
 *      teleframe.notify.exceptions.enabled and ships false. Auto-sending
 *      every exception to a chat is an operator decision, not a default a
 *      package imposes on a host application.
 *   2. A FAILURE TO NOTIFY IS NEVER THROWN. Laravel runs reportable
 *      callbacks OUTSIDE its own try/catch
 *      (Foundation\Exceptions\Handler::reportThrowable), so a throwing
 *      callback surfaces the notification failure in place of the exception
 *      the host was reporting. The operator then debugs "telegram is down"
 *      instead of the outage. report() therefore catches everything, logs at
 *      error, and returns false. It is the one class in this package whose
 *      job is to fail SILENTLY-TO-THE-CALLER and loudly in the log.
 *   3. NOTHING UNREDACTED LEAVES THE PROCESS. This feature's entire job is
 *      to put exception text into a chat, so it is exactly where a session
 *      string, an auth key or a bot token would hide. The sink is handed
 *      describe()'s payload and nothing else: class, a redacted and bounded
 *      message, the code, the file:line. The raw Throwable is never passed
 *      on, and no trace is shipped at all — every trace frame carries host
 *      arguments, and a trace is a dump, not a summary.
 */
final class ExceptionReporter
{
    /** Cap on the shipped message; matches the renderer's chat posture. */
    public const MAX_MESSAGE = 240;

    /**
     * @param  bool  $enabled  teleframe.notify.exceptions.enabled (default false)
     * @param  (\Closure(array{class: string, message: string, code: string|int, file: string, line: int}): void)|null  $sink  the send; receives the redacted payload ONLY
     * @param  LoggerInterface|null  $logger  where a failed notify is recorded; the caller never sees it
     * @param  ExceptionRedactor|null  $redactor  the redaction policy (injected for tests; a fresh one by default)
     */
    public function __construct(
        private readonly bool $enabled = false,
        private readonly ?\Closure $sink = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?ExceptionRedactor $redactor = null,
    ) {}

    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * A notification-safe view of a Throwable: the summary that is allowed
     * to leave the process, already redacted and bounded.
     *
     * @return array{class: string, message: string, code: string|int, file: string, line: int}
     */
    public function describe(Throwable $e): array
    {
        return [
            'class' => $e::class,
            'message' => $this->redactor()->redact($e->getMessage(), self::MAX_MESSAGE),
            'code' => $this->scalar($e->getCode()),
            'file' => $this->redactor()->redact($e->getFile(), 180),
            'line' => $e->getLine(),
        ];
    }

    /**
     * Report an exception. Returns true when a notification was handed to the
     * sink, false when it was not (disabled, no sink, or the send failed).
     *
     * NEVER THROWS, by rule 2 above.
     */
    public function report(Throwable $e): bool
    {
        if (! $this->enabled || $this->sink === null) {
            return false;
        }

        try {
            // describe() is inside the try too: redaction is defensive code
            // and must not become the thing that breaks error reporting.
            ($this->sink)($this->describe($e));
        } catch (Throwable $failure) {
            $this->logger?->error('teleframe: exception notification failed; the host exception is unaffected', [
                'exception_class' => $e::class,
                'notify_failure' => $failure::class.': '.$failure->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    private function redactor(): ExceptionRedactor
    {
        return $this->redactor ?? new ExceptionRedactor(self::MAX_MESSAGE);
    }

    /**
     * An exception code is an int by contract but a string in the wild
     * (PDOException carries the SQLSTATE). Normalise to a redacted scalar so
     * the payload is plain data, never an object.
     */
    private function scalar(mixed $code): string|int
    {
        if (is_int($code)) {
            return $code;
        }

        if (is_string($code) || is_float($code) || is_bool($code)) {
            return $this->redactor()->redact((string) $code, 120);
        }

        return '';
    }
}
