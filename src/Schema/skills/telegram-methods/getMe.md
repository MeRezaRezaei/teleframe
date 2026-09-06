<!-- @generated -->

# getMe

[Docs](https://core.telegram.org/bots/api#getme)

A simple method for testing your bot's authentication token. Requires no parameters. Returns basic information about the bot in form of a User object.

## Parameters

| name | type | required | description |
| --- | --- | --- | --- |

## Returns

User

## Usage

```php
$request = Methods::bots()->getMe()
    ->toRequest();

$client = app(\MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient::class);   // or: new TeleframeClient(defaultApiId: …, defaultApiHash: …)
$result = $client->dispatch($request);
```
