<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Console;

use MeRezaRezaei\Teleframe\Tests\Support\FakeUserScope;

/**
 * A scope that answers the four account-scoped collection calls with payloads
 * that PLACE, and records the EXACT argument array each one was dialled with.
 *
 * Recording the arguments is the point. A test that only asserted "the row
 * landed" would pass or fail on the wrong signal — the row proves the whole
 * pipeline works, while the arguments prove the CALL is well-formed, and the
 * pre-fix bug (every method dialled with `[]`) was invisible to any assertion
 * but an argument one.
 *
 * `$emptyArgs` reconstructs the PRE-FIX caller: `call()` then ignores the
 * arguments it is handed and reports `[]`, exactly as
 * `$scope->call($method)` did. That lets the suite assert the defect's
 * consequence — no keying data in the payload, therefore no row — against the
 * same expectations, which is what makes the positive assertions load-bearing
 * rather than a description of the current code.
 *
 * Provenance: the payload shapes are the layer-227 constructors
 * payments.savedStarGifts, payments.starsStatus, account.businessChatLinks and
 * messages.savedDialogs (schema/sources/TL_telegram_v227.tl:2026, :1938, :1899,
 * :1810) and the keying columns are the shipped migrations'
 * (2026_09_14_200040_create_tf_stars_business_tables.php,
 * 2026_09_14_200050_create_tf_misc_tables.php).
 */
final class CollectionArgsScope extends FakeUserScope
{
    /**
     * method name => the argument array the caller passed, verbatim.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $sent = [];

    /**
     * @param  bool  $emptyArgs  report `[]` for every call, as the pre-fix caller did
     */
    public function __construct(private readonly bool $emptyArgs = false)
    {
        parent::__construct();
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function call(string $method, array $params = []): array
    {
        if ($this->emptyArgs) {
            $params = [];
        }

        if (in_array($method, [
            'payments.getSavedStarGifts',
            'payments.getStarsStatus',
            'account.getBusinessChatLinks',
            'messages.getSavedDialogs',
        ], true)) {
            $this->sent[$method] = $params;
        }

        return match ($method) {
            'payments.getSavedStarGifts' => [
                '_' => 'payments.savedStarGifts',
                'count' => 1,
                'gifts' => [
                    [
                        '_' => 'savedStarGift',
                        'date' => 1712345678,
                        'gift' => ['_' => 'starGift', 'id' => 55501, 'stars' => 25],
                    ],
                ],
                'chats' => [],
                'users' => [],
            ],
            'payments.getStarsStatus' => [
                '_' => 'payments.starsStatus',
                'balance' => ['_' => 'starsAmount', 'amount' => 900, 'nanos' => 0],
                'history' => [
                    [
                        '_' => 'starsTransaction',
                        'id' => 'txn-beta',
                        'date' => 1712345678,
                        'amount' => ['_' => 'starsAmount', 'amount' => 25, 'nanos' => 0],
                        'peer' => [
                            '_' => 'starsTransactionPeer',
                            'peer' => ['_' => 'peerUser', 'user_id' => 9002],
                        ],
                    ],
                ],
                'subscriptions' => [
                    [
                        '_' => 'starsSubscription',
                        'id' => 'sub-alpha',
                        'peer' => ['_' => 'peerUser', 'user_id' => 9002],
                        'until_date' => 1712432078,
                        'pricing' => ['_' => 'starsSubscriptionPricing', 'period' => 2592000, 'amount' => 250],
                    ],
                ],
                'chats' => [],
                'users' => [],
            ],
            'account.getBusinessChatLinks' => [
                '_' => 'account.businessChatLinks',
                'links' => [
                    [
                        '_' => 'businessChatLink',
                        'link' => 'https://t.me/+AbCdEf',
                        'message' => 'Book a call',
                        'views' => 7,
                    ],
                ],
                'chats' => [],
                'users' => [],
            ],
            'messages.getSavedDialogs' => [
                '_' => 'messages.savedDialogs',
                'dialogs' => [
                    [
                        '_' => 'savedDialog',
                        'peer' => ['_' => 'peerUser', 'user_id' => 9002],
                        'top_message' => 900,
                    ],
                ],
                'messages' => [],
                'chats' => [],
                'users' => [],
            ],
            'messages.getHistory' => ['_' => 'messages.messagesSlice', 'count' => 0, 'messages' => []],
            default => parent::call($method, $params),
        };
    }
}
