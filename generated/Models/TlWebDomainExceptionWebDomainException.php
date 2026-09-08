<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for webDomainException of WebDomainException (crc32 933ca597). */
final class TlWebDomainExceptionWebDomainException extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_web_domain_exception_web_domain_exception';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'domain' => 'string',
        'url' => 'string',
        'title' => 'string',
        'favicon' => 'int',
    ];
}
