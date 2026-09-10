<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for messageFwdHeader of MessageFwdHeader (crc32 4e4df4bb). */
final class TlMessageFwdHeaderMessageFwdHeader extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_message_fwd_header_message_fwd_header';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'imported' => 'bool',
        'saved_out' => 'bool',
        'from_name' => 'string',
        'date' => 'int',
        'channel_post' => 'int',
        'post_author' => 'string',
        'saved_from_msg_id' => 'int',
        'saved_from_name' => 'string',
        'saved_date' => 'int',
        'psa_type' => 'string',
    ];
}
