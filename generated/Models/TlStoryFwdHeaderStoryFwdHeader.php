<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for storyFwdHeader of StoryFwdHeader (crc32 b826e150). */
final class TlStoryFwdHeaderStoryFwdHeader extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_story_fwd_header_story_fwd_header';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'modified' => 'bool',
        'from_name' => 'string',
        'story_id' => 'int',
    ];
}
