<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use InvalidArgumentException;

/**
 * CapabilityCheck (design §6 "enhanced check"): length/file/album/rate
 * against the CapabilityTable rows + the premium flag. Every limit comes
 * from the table — this class contains NO hardcoded numbers; the only
 * literals are feature names to look up and verdict wording.
 *
 * Verdict semantics (design §6): allowed (send as-is); truncated (text
 * over the cap — the pipeline trims to the cap and keeps the full text in
 * the attachment); refused (file/album/rate over the cap — nothing
 * silently drops, the caller raises NotifyException with the reason).
 *
 * The companion result object (CapabilityCheckResult, same file) is
 * produced exclusively by this checker; the design doc's CapabilityVerdict
 * stays its own sibling-owned file.
 */
final class CapabilityCheck
{
    public function __construct(private readonly CapabilityTable $table = new CapabilityTable) {}

    /** text_message row (4096 chars, ×1 — not premium-raised). */
    public function checkText(string $text, PremiumStatus $premium): CapabilityCheckResult
    {
        $effective = $this->table->effective('text_message', $premium->premium());
        $length = mb_strlen($text);

        return $length > $effective
            ? CapabilityCheckResult::truncate(
                'text of '.$length.' chars exceeds the '.$effective.' char cap — will be truncated to the cap',
                $effective,
            )
            : CapabilityCheckResult::allow($effective);
    }

    /** caption row (1024 chars, ×1) — media captions trim to the cap. */
    public function checkCaption(string $caption, PremiumStatus $premium): CapabilityCheckResult
    {
        $effective = $this->table->effective('caption', $premium->premium());
        $length = mb_strlen($caption);

        return $length > $effective
            ? CapabilityCheckResult::truncate(
                'caption of '.$length.' chars exceeds the '.$effective.' char cap — will be trimmed to the cap',
                $effective,
            )
            : CapabilityCheckResult::allow($effective);
    }

    /** media_album row (10 items, ×1) — an album cannot be trimmed, so over-cap refuses. */
    public function checkAlbum(int $items, PremiumStatus $premium): CapabilityCheckResult
    {
        $effective = $this->table->effective('media_album', $premium->premium());

        return $items > $effective
            ? CapabilityCheckResult::refuse(
                'media album of '.$items.' items exceeds the '.$effective.' item cap — refusing',
                $effective,
            )
            : CapabilityCheckResult::allow($effective);
    }

    /**
     * file_upload row for the transport (bot: 50 MiB ×1; mtproto: 2 GiB
     * ×2 → 4 GiB premium). $bytes is compared in the table's checkable
     * unit (bytes); the earlier MIME acceptability gate from the design
     * doc is a later wave — this checker is byte-only.
     */
    public function checkFile(int $bytes, PremiumStatus $premium, string $transport = 'bot'): CapabilityCheckResult
    {
        if (! in_array($transport, ['bot', 'mtproto'], true)) {
            throw new InvalidArgumentException('unknown transport: '.$transport.' (expected bot|mtproto)');
        }

        $effective = $this->table->effective('file_upload_'.$transport, $premium->premium());

        return $bytes > $effective
            ? CapabilityCheckResult::refuse(
                'file of '.$bytes.' bytes exceeds the '.$effective.' byte cap — refusing',
                $effective,
            )
            : CapabilityCheckResult::allow($effective);
    }

    /**
     * Rate rows (send_rate 30 msg/s ×4.5, per_chat_rate 1 msg/s ×4.5,
     * topic_budget 60 msg/h ×4.5). Enforcement is wave 3 — this row ships
     * the verdict the enforcement will honor; the checker only reports.
     */
    public function checkRate(int $perSecond, PremiumStatus $premium, string $feature = 'send_rate'): CapabilityCheckResult
    {
        $effective = $this->table->effective($feature, $premium->premium());

        return $perSecond > $effective
            ? CapabilityCheckResult::refuse(
                'rate of '.$perSecond.' exceeds the '.$effective.' cap for '.$feature.' — refusing',
                $effective,
            )
            : CapabilityCheckResult::allow($effective);
    }
}

/**
 * CapabilityCheckResult: the verdict of one capability check — allowed /
 * denied + reason (the task contract), plus the truncated flag (design
 * §6: allowed | truncated | refused) and the effective limit.
 *
 * Lives in CapabilityCheck.php (not autoloadable by name) because the
 * wave allocates exactly three capability files; everybody reaches it
 * through the checker's return type. PHP forbids a static and an instance
 * method with the same name, so the design's factory names
 * allowed()/truncated()/refused() cannot coexist with the allowed():bool
 * getter — factories are allow()/truncate()/refuse(), the getters keep
 * the design names.
 */
final class CapabilityCheckResult
{
    private function __construct(
        private readonly bool $allowed,
        private readonly bool $truncated,
        private readonly ?string $reason,
        private readonly int $effective,
    ) {}

    public static function allow(int $effective): self
    {
        return new self(true, false, null, $effective);
    }

    public static function truncate(string $reason, int $effective): self
    {
        return new self(false, true, $reason, $effective);
    }

    public static function refuse(string $reason, int $effective): self
    {
        return new self(false, false, $reason, $effective);
    }

    public function allowed(): bool
    {
        return $this->allowed;
    }

    public function denied(): bool
    {
        return ! $this->allowed;
    }

    /** True for a text/caption over-cap intended to be trimmed, not refused. */
    public function truncated(): bool
    {
        return $this->truncated;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    /** The effective limit the verdict was checked against (table unit). */
    public function effective(): int
    {
        return $this->effective;
    }
}
