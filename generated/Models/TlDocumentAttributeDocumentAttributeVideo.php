<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for documentAttributeVideo of DocumentAttribute (crc32 43c57c48). */
final class TlDocumentAttributeDocumentAttributeVideo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_document_attribute_document_attribute_video';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'round_message' => 'bool',
        'supports_streaming' => 'bool',
        'nosound' => 'bool',
        'duration' => 'float',
        'w' => 'int',
        'h' => 'int',
        'preload_prefix_size' => 'int',
        'video_start_ts' => 'float',
        'video_codec' => 'string',
    ];
}
