<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

/**
 * WatchAlert (design §2/G10, wave 2) — one no-show finding from
 * NoShowWatchdog::check(): the watched topic, the expected heartbeat
 * interval, the last observed heartbeat and the measured age (now -
 * lastSeen). Immutable row; carries the two surfaces the wave needs:
 * toNotify() — the design §8 alert payload routed to the dedicated
 * `{topic}.missing` topic at Level::Alert (ruling §6.5: a separate
 * topic so operators mute the no-show alarm without throttling the
 * level topics); toRow() — the CLI status rendering.
 */
final class WatchAlert
{
    public function __construct(
        public readonly string $topic,      // the watched topic, e.g. 'backup'
        public readonly int $expectedEvery, // expected heartbeat interval, seconds
        public readonly int $lastSeen,      // unix ts of the last observed heartbeat
        public readonly int $age,           // seconds since lastSeen (now - lastSeen)
    ) {}

    /**
     * The notify shape (design §8 alert action: `Notify::to($topic . '.missing',
     * …)->level(Level::Alert)->send()`). Topic/level/brief/context only —
     * a target chat is resolved at consume time by the topic registry's
     * loud gate, never carried here (credentials stay out of payloads).
     *
     * @return array<string, mixed>
     */
    public function toNotify(): array
    {
        return [
            'topic' => $this->topic.'.missing',
            'level' => Level::Alert->value,
            'brief' => 'no update arrived for '.$this->topic.' in '.$this->age.'s',
            'context' => [
                'topic' => $this->topic,
                'expected_every_seconds' => $this->expectedEvery,
                'last_seen' => $this->lastSeen,
                'age_seconds' => $this->age,
            ],
        ];
    }

    /** The CLI row (teleframe:notify watch --check). */
    public function toRow(): array
    {
        return [
            'topic' => $this->topic,
            'expected_every_s' => $this->expectedEvery,
            'last_seen_s' => $this->lastSeen,
            'age_s' => $this->age,
        ];
    }
}
