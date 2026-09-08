<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for help.recentMeUrls of help.RecentMeUrls.
 */
final class TlHelpRecentMeUrlsData extends TlHelpRecentMeUrlsAbstractData
{
    public function __construct(
    public array $urls,
    public array $chats,
    public array $users,
    ) {
    }
}
