<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use RuntimeException;

/**
 * NotifyException (design §2): the notification core's domain exception.
 * Raised for refused sends (capability refusal), broken queue wiring (no
 * queue bound at send/queue time), and unknown topics at send()/queue()
 * time — the loud gate the design rules (§4: "unknown topic at send()
 * throws NotifyException — loud, like BotMap::for()"). The engine
 * ALWAYS rethrows (spec §7: logging never swallows) — the notify queue's
 * retry/dead-letter owns recovery, never a silent drop.
 */
final class NotifyException extends RuntimeException {}
