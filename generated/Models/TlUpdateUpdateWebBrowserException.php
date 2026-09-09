<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBool;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebDomainException;

/** Constructor model for updateWebBrowserException of Update (crc32 140502d1). */
final class TlUpdateUpdateWebBrowserException extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_web_browser_exception';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'delete' => 'bool',
    ];

    public function openExternalBrowser(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'open_external_browser');
    }
    public function exception(): BelongsTo
    {
        return $this->belongsTo(TlWebDomainException::class, 'exception');
    }
}
