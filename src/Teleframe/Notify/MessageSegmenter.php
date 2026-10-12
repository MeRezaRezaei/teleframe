<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use DateTimeImmutable;

/**
 * Splits a notification payload into the two-section shape the design doc
 * rules (§7, spec G6): the Telegram message carries the brief start plus a
 * compact context-metadata block; the long section — the full body, any
 * structured context, and every exception/trace key — becomes ONE
 * rendered-HTML file attachment (design §7: "trace/exception keys are NEVER
 * inlined").
 *
 * Inline message shape (line 1, then the brief head, then the metadata):
 *
 *   [INFO] app.info · 2026-09-24T17:49:00+00:00
 *   backup completed                          ← brief, ≤ inline budget
 *   set=default bytes=48127                   ← scalar context, ≤ budget
 *
 * Anything that does not fit the inline budget, any non-scalar context value
 * and any `exception`/`trace` key forces the attachment; the full brief and
 * the full context dump always survive in the file. A truncated brief gets
 * the `… [truncated]` marker (design §6 verdict semantics).
 *
 * Zero-regex by design (`str_*`/`substr`/`strlen` vocabulary only) and
 * plain-PHP constructible: constructor wires the renderer + attachment
 * directory, no container calls.
 */
final class MessageSegmenter
{
    /** Context keys that are only ever rendered into the file (design §7). */
    private const FILE_ONLY_KEYS = ['exception', 'trace'];

    /** Truncation marker appended to an over-budget brief (design §6). */
    private const TRUNCATED_MARKER = '… [truncated]';

    public function __construct(
        private readonly HtmlAttachment $renderer,
        private readonly string $attachmentDir,
    ) {}

    /**
     * Split one notification into the inline message + optional HTML file.
     *
     * @param  array<string, mixed>  $context
     * @return array{text: string, brief: string, metadata_block: string, attachment?: array{name: string, path: string, mime: string, bytes: int}}
     *
     * `text` is the full inline message ready for `sendMessage`; `brief` is
     * the brief head (≤ `$inlineBudget`, `… [truncated]` when trimmed);
     * `metadata_block` is the compact `key=value` block (≤ the same budget);
     * `attachment` is the rendered HTML file when the long section exists.
     */
    public function split(Level $level, string $brief, array $context, ?string $body, int $inlineBudget): array
    {
        $badge = strtoupper($level->value);
        $topic = $level->defaultTopic();
        $stamp = (new DateTimeImmutable)->format(DATE_ATOM);

        [$briefPart, $briefTruncated] = $this->inlineBrief($brief, $inlineBudget);
        [$metadataBlock, $metadataOverflow] = $this->metadataBlock($context, $inlineBudget);

        $attachment = null;
        if ($this->needsFile($body, $briefTruncated, $metadataOverflow, $context)) {
            $attachment = $this->renderer->render($topic, $context, $body ?? $brief, $this->attachmentDir);
        }

        $text = "[{$badge}] {$topic} · {$stamp}";
        if ($briefPart !== '') {
            $text .= "\n".$briefPart;
        }
        if ($metadataBlock !== '') {
            $text .= "\n".$metadataBlock;
        }

        $split = [
            'text' => $text,
            'brief' => $briefPart,
            'metadata_block' => $metadataBlock,
        ];

        if ($attachment !== null) {
            $split['attachment'] = $attachment;
        }

        /** @var array{text: string, brief: string, metadata_block: string, attachment?: array{name: string, path: string, mime: string, bytes: int}} $split */
        return $split;
    }

    /**
     * The brief head: the full brief when it fits the inline budget, else the
     * head trimmed to budget (marker included) — the FULL brief then lives in
     * the attachment, never lost (design §6 truncated semantics).
     *
     * @return array{string, bool} the head and whether it was truncated
     */
    private function inlineBrief(string $brief, int $budget): array
    {
        if ($brief === '') {
            return ['', false];
        }

        if (strlen($brief) <= $budget) {
            return [$brief, false];
        }

        $keep = max(0, $budget - strlen(self::TRUNCATED_MARKER));

        $head = substr($brief, 0, $keep);

        // Back off trailing non-ASCII bytes so the cut never splits a
        // multi-byte character: the head stays valid UTF-8 for the wire.
        while ($head !== '' && ord($head[strlen($head) - 1]) >= 0x80) {
            $head = substr($head, 0, -1);
        }

        return [$head.self::TRUNCATED_MARKER, true];
    }

    /**
     * Compact `key=value` metadata block: scalar context pairs accumulated
     * until the inline budget runs out. `exception`/`trace` keys and any
     * non-scalar value are NEVER inlined — they overflow to the file.
     *
     * Public because the queue-consumer compose path (NotifyService::handle)
     * reuses it to render the job's context into the inline message — the
     * same rule set split() applies, without re-rendering the attachment.
     *
     * @param  array<string, mixed>  $context
     * @return array{string, bool} the block and whether anything overflowed
     */
    public function metadataBlock(array $context, int $budget): array
    {
        $lines = [];
        $used = 0;
        $overflow = false;

        foreach ($context as $key => $value) {
            if (in_array((string) $key, self::FILE_ONLY_KEYS, true) || ! is_scalar($value)) {
                $overflow = true;

                continue;
            }

            $line = (string) $key.'='.$this->displayValue($value);
            $extra = strlen($line) + ($lines === [] ? 0 : 1);

            if ($extra > max(0, $budget) - $used) {
                $overflow = true;

                continue;
            }

            $lines[] = $line;
            $used += $extra;
        }

        return [implode("\n", $lines), $overflow];
    }

    /**
     * The long section exists when the body is present, when the inline
     * budget overflowed, or when the context carries anything that must only
     * live in the file (trace/exception keys, structured values).
     *
     * @param  array<string, mixed>  $context
     */
    private function needsFile(?string $body, bool $briefTruncated, bool $metadataOverflow, array $context): bool
    {
        if ($body !== null && $body !== '') {
            return true;
        }

        if ($briefTruncated || $metadataOverflow) {
            return true;
        }

        foreach ($context as $key => $value) {
            if (in_array((string) $key, self::FILE_ONLY_KEYS, true) || ! is_scalar($value)) {
                return true;
            }
        }

        return false;
    }

    /** Scalar → display string (`true`/`false`/`null` spelled out). */
    private function displayValue(mixed $value): string
    {
        if ($value === true) {
            return 'true';
        }
        if ($value === false) {
            return 'false';
        }
        if ($value === null) {
            return 'null';
        }

        return (string) $value;
    }
}
