# Botify
Simple & developer-friendly Telegram Bot Api Framework for PHP.

## Features
 - Easy Localization
 - Cache Data
 - Database (MySQL, SQLite)
 - Modules (Extensions)
 - Easy Users Manage
 - All In One Object
 
 ## Installation
```
$ composer require aethletic/botify
```

## Example: HelloWorld Bot
```php
use Botify\Core\Bot;

require '/vendor/autoload.php';

$bot = new Bot('1234567890:ABC_TOKEN');

$bot->hear('Hello', function () use ($bot) {
    $bot->say('Hello World! 🌎');
});

$bot->run();
```

## Create Bot
When creating a bot, you can pass the second parameter with the configuration of the bot.
```php
$config = [
    // (OPTIONAL) after changing the bot version, the first message
    // that the user will send can be processed and sent a
    // message that the bot version has been updated and
    // send, for example, a new keyboard.
    'bot.version'       => '0.0.1',

    // (OPTIONAL) your url for webhook
    'bot.url'           => 'https://bot.example.com/botify/index.php',

    // (OPTIONAL) for fome modules
    'bot.username'      => 'BotifyBot',

    // (OPTIONAL) Telegram Bot Token
    'bot.token'         => '1234567890:ABC_TOKEN',

    // (OPTIONAL) default language if the user language is empty or for
    // no template. (OPTIONAL, default 'ru')
    'bot.default_lang'  => 'ru',

    // (OPTIONAL) List of usernames (without @) OR user_id (12345677899) of users who are administrators
    'admin.list'        => ['aethletic'],
    'admin.password'    => ['pa$$word'],

    // (OPTINAL) deafult parse mode
    'tg.parse_mode'     => 'markdown',

    // (OPTIONAL) memcache driver:
    // > false - cache disable
    // > memcached - for enable memcache cache
    'cache.driver'      => 'memcached',
    'cache.host'        => 'localhost',
    'cache.port'        => '11211',

    // (OPTIONAL) Time in seconds after which the user can write
    // the following message.
    // It works ONLY if the database is enabled!
    'spam.timeout'      => 1, //sec

    // (OPTIONAL) dv.driver:
    // > false - for disable db
    // > mysql - for enable mysql db
    // > sqlite - for enable sqlite db
    // If db SQLite, need to specify db.path to the database file
    'db.driver'         => 'mysql',
    // 'db.path'           => '/path/to/db.sqlite',
    'db.host'           => 'localhost',
    'db.database'       => 'svetozar79_dev',
    'db.username'       => 'svetozar79_dev',
    'db.password'       => "!ctynz,hz",
    'db.charset'        => 'utf8mb4',
    'db.lazy'           => true,

    // (OPTIONAL) If you changed the data in the database,
    // for example, added a coin field, then you can
    // pass the default value for this field.
    // Example:
    // ['coins' => 1000]
    'db.insert'         => [],

    // (OPTIONAL) works ONLY if cache or db enabled!
    // state driver:
    // > db - using database (fast than cache)
    // > cache - using cache
    'state.driver'      => 'db',

    // (OPTIONAL) store logs file
    'log.dir'           => __DIR__ . '/../storage/logs',

    // config for modules
    'modula.name'      => [
        'param1' => 'value1',
        'param2' => 'value2',
    ]
];

$bot = new Bot('1234567890:ABC_TOKEN', $config);
```

## say()
Send a simple chat message where the update came from.
```php 
$bot->say($text);
$bot->say($text, $keyboard);
```

## reply()
Send message and forward user message.
```php
$bot->reply($text);
$bot->reply($keyboard);
```

## action()
Send chat status, for example, "Typing".

> **Available:** typing, upload_photo, record_video, upload_video, record_audio, upload_audio, upload_document, find_location, record_video_note, upload_video_note
```php
$bot->action('typing');
$bot->action('typing')->say('Hello!');
$bot->action('typing')->reply('How are you?');
```

## hear
Catch a text message from a user.
```php
$bot->hear('ping?', function () use ($bot) {
    $bot->say('pong!');
});
```
To catch multiple messages, use an array of messages.
```php
$bot->hear(['Hello', 'Привет', 'Salut'], function () use ($bot) {
    $bot->say('Hi!');
});
```
You can also use regular expressions, and not even one.
```php
$bot->hear(['/hello/i', '/прив/ui', 'Salut'], function () use ($bot) {
    $bot->say('Hi!');
});
```

## command
Catch a command message from a user.
```php
$bot->command('/start', function () use ($bot) {
    $bot->say('Well met!');
});
```
To catch multiple messages, use an array of messages.
```php
$bot->command(['/start', '/back'], function () use ($bot) {
    $bot->say('Well met!');
});
```
You can also use regular expressions, and not even one.
```php
$bot->command(['/\/start/i', '/\/старт/ui', '/back'], function () use ($bot) {
    $bot->say('Well met!');
});
```
You can also parse the message.
```php
$bot->command('/start', function () use ($bot) {
	[$cmd, $arg] = $bot->parse();
    $bot->say("Well met! Your start arg is: {$arg}.");
});
```

## callback
Catch a callback event from a user.
```php
$bot->callback('some_callback_data', function () use ($bot) {
    $bot->say('Yep!');
});
```
To catch multiple messages, use an array of messages.
```php
$bot->callback(['first_cb_data', 'second_cb_data'], function () use ($bot) {
    $bot->say('Yep!');
});
```
You can also use regular expressions, and not even one.
```php
$bot->callback(['/film_id/i', '/serial_id/i'], function () use ($bot) {
    $bot->say('Show video?');
});
```
You can also parse the callback_data.
```php
$bot->callback(['/film_id/i', '/serial_id/i'], function () use ($bot) {
	[$cmd, $id] = $bot->parse('_');
    $bot->say("Showing video: {$id}.");
});
```
You can send a notification to the chat.
```php
$bot->callback('send_me_notify', function () use ($bot) {
    $bot->notify('This is Notification.');
});
```
```php
$bot->callback('send_me_alert', function () use ($bot) {
    $bot->notify('This is Alert. Please, press OK.', true);
});
```

## parse
You can parse a text message, command message or callback_data with one command.
```php 
$message = '/start botify';

[$cmd, $arg] = $bot->parse(); // default delimiter is space ' '.

echo $cmd; // /start
echo $arg; // botify
```
```php 
$message = '/filmId_12345';

[$cmd, $id] = $bot->parse('_'); // now delimiter is '_'.

echo $cmd; // /filmId
echo $id; // 12345
```
