<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for exportedMessageLink of ExportedMessageLink (crc32 5dab1af4). */
final class TlExportedMessageLinkExportedMessageLink extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_exported_message_link_exported_message_link';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'link' => 'string',
        'html' => 'string',
    ];
}
