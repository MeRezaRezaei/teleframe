<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for inputMessagesFilterPhotos of MessagesFilter (crc32 9609a51c). */
final class TlMessagesFilterInputMessagesFilterPhotos extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_filter_input_messages_filter_photos';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
