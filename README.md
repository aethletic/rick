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

# Methods
## setWebhook
Set webhook for recive updates from Telegram.
```php
$bot->setWebhook($url = false);
```
```php 
$bot->setWebhook('https://bot.example.com/botify/index.php');
```
If you passed the `bot.url` parameter in the configuration, then it is not necessary to pass `$url` parameter.
```php
$bot->setWebhook();
```

## deleteWebhook
```php
$bot->deleteWebhook($token = false);
```
```php
$bot->deleteWebhook('1234567890:ABC_TOKEN');
```
If you passed the `bot.token` parameter in the configuration, then it is not necessary to pass `$token` parameter.
```php
$bot->deleteWebhook();
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

> **Available $action:** typing, upload_photo, record_video, upload_video, record_audio, upload_audio, upload_document, find_location, record_video_note, upload_video_note
```php
$bot->action($action);
$bot->action('typing');
$bot->action('typing')->say('Hello!');
$bot->action('typing')->reply('How are you?');
```

## parse
You can parse a `text message`, `command message` or `callback_data` with one command.
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
```php 
$message = '/serial_12345_3';

[$cmd, $serial_id, $serial_season] = $bot->parse('_'); // now delimiter is '_'.

echo $cmd; // /serial
echo $serial_id; // 12345
echo $serial_season; // 3
```

## dice
Send a dice to chat.
```php 
$bot->dice($emoji = '', $keyboard = false);
```

## notify
You can send a notification to the chat.
It works only if the `callback_data` came in the update
```php 
$bot->notify($text);
$bot->notify($text, $is_alert = true); // false - is notification, true - is alert. Default: false
```

## isActive
Check if the user has blocked the bot or not. 
For example, it is useful to use active users to collect statistics.
```php
$bot->isActive($chat_id);
```
You can also pass the parameter `$action`.
```php
$bot->isActive($chat_id, $action = 'typing'); // default: 'typing'
```

## sendJson
For debugging you can use this method.
Sends json data to chat.
```php 
$bot->sendJson();
```
Sample received message:
```json
{
    "update_id": 31332905,
    "message": {
        "message_id": 29437,
        "from": {
            "id": 436432850,
            "is_bot": false,
            "first_name": "чипсы лейс",
            "username": "aethletic",
            "language_code": "ru"
        },
        "chat": {
            "id": 436432850,
            "first_name": "чипсы лейс",
            "username": "aethletic",
            "type": "private"
        },
        "date": 1590836349,
        "text": "Botify 👍"
    }
}
```

## request
A universal way to call any Telegram method.
```php
$bot->request($method, $parameters, $is_file = false); // $is_file default: false
```
```php
$parameters = [
	'chat_id' => $chat_id,
	'text' => 'Sent via Request method 📩',
	'parse_mode' => 'html'
];

$bot->request('sendMessage', $parameters);
```

```php
use Botify\Core\File;

$parameters = [
	'chat_id' => $chat_id,
	'photo' => File::upload('/storage/photo/name.jpg')
];

$bot->request('sendPhoto', $parameters, $is_file = true);
```

# Events

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
You can also parse the `callback_data`.
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
## Event Type Check
All variables have data corresponding data in themselves. 
If there is no data, then the variable will be `false`.
```php
$bot->isSticker;
$bot->isVoice;
$bot->isAnimation;
$bot->isDocument;
$bot->isAudio;
$bot->isPhoto;
$bot->isPoll;
$bot->isVideoNote;
$bot->isContact;
$bot->isLocation;
$bot->isVenue;
$bot->isDice;
$bot->isNewChatMembers;
$bot->isLeftChatMember;
$bot->isNewChatTitle;
$bot->isNewChatPhoto;
$bot->isDeleteChatPhoto;
$bot->isChannelChatCreated;
$bot->isMigrateToChatId;
$bot->isMigrateFromChatId;
$bot->isPinnedMessage;
$bot->isInvoice;
$bot->isSucessfulPayment;
$bot->isConnectedWebsite;
$bot->isPassportData;
$bot->isReplyMarkup;
$bot->isCommand;
$bot->isInline;
$bot->isForward;
$bot->isSuperGroup;
$bot->isGroup;
$bot->isChannel;
$bot->isPrivate;
$bot->isCaption;
$bot->isEditedMessage;
$bot->isCallback;
$bot->isMessage;
```
Example:
```php 
if ($bot->isDice) {
	$emoji = $bot->isDice['emoji'];
	$value = $bot->isDice['value'];
	$bot->say("Emoji: {$emoji}, value: {$value}.");
}
```

## onMessage
Alternative for `$bot->isMessage`.
```php
$bot->onMessage(function () use ($bot) {
	// code...
});
```

## onCommand
Alternative for `$bot->isCommand`.
```php
$bot->onCommand(function ($command) use ($bot) {
	// code...
});
```
## onCallback
Alternative for `$bot->isCallback`.
```php
$bot->onCallback(function () use ($bot) {
	// code...
});
```
## onEditedMessage
Alternative for `$bot->isEditedMessage`.
```php
$bot->onEditedMessage(function () use ($bot) {
	// code...
});
```
## onSticker
Alternative for `$bot->isSticker`.
```php
$bot->onSticker(function ($sticker) use ($bot) {
	// code...
});
```
## onVoice
Alternative for `$bot->isVoice`.
```php
$bot->onVoice(function ($voice) use ($bot) {
	// code...
});
```
## onDocument
Alternative for `$bot->isDocument`.
```php
$bot->onDocument(function ($document) use ($bot) {
	// code...
});
```
## onAnimation
Alternative for `$bot->isAnimation`.
```php
$bot->onAnimation(function ($animation) use ($bot) {
	// code...
});
```
## onPhoto
Alternative for `$bot->isPhoto`.
```php
$bot->onPhoto(function ($photo) use ($bot) {
	// code...
});
```
## onAudio
Alternative for `$bot->isAudio`.
```php
$bot->onAudio(function ($audio) use ($bot) {
	// code...
});
```
## onVideoNote
Alternative for `$bot->isVideoNote`.
```php
$bot->onVideoNote(function ($video_note) use ($bot) {
	// code...
});
```
## onVideo
Alternative for `$bot->isVideo`.
```php
$bot->onVideo(function ($video) use ($bot) {
	// code...
});
```
## onContact
Alternative for `$bot->isContact`.
```php
$bot->onContact(function ($contact) use ($bot) {
	// code...
});
```
## onLocation
Alternative for `$bot->isLocation`.
```php
$bot->onLocation(function ($location) use ($bot) {
	// code...
});
```
## onPoll
Alternative for `$bot->isPoll`.
```php
$bot->onPoll(function ($poll) use ($bot) {
	// code...
});
```
## onDice
Alternative for `$bot->isDice`.
```php
$bot->onDice(function ($emoji, $value) use ($bot) {
	// code...
});
```
## onInline
Alternative for `$bot->isInline`.
```php
$bot->onInline(function () use ($bot) {
	// code...
});
```
## fromPrivate
Alternative for `$bot->isPrivate`.
```php
$bot->fromPrivate(function () use ($bot) {
	// code...
});
```
## fromChannel
Alternative for `$bot->isChannel`.
```php
$bot->fromChannel(function () use ($bot) {
	// code...
});
```
## fromGroup
Alternative for `$bot->isGroup`.
```php
$bot->fromGroup(function () use ($bot) {
	// code...
});
```
## fromSuperGroup
Alternative for `$bot->isSuperGroup`.
```php
$bot->fromSuperGroup(function () use ($bot) {
	// code...
});
```

# Default Telegram Methods

## sendAction
> **Available $action:** typing, upload_photo, record_video, upload_video, record_audio, upload_audio, upload_document, find_location, record_video_note, upload_video_note
```php
$bot->sendAction($chat_id, $action);
```
## sendMessage
```php
$bot->sendMessage($chat_id, $text, $keyboard = false);
```
## sendReply
```php
$bot->sendReply($chat_id, $message_id, $text, $keyboard = false);
```
## sendDice
```php
$bot->sendDice($chat_id, $emoji = '', $keyboard = false);
```
## sendDocument
```php
$bot->sendDocument($chat_id, $file, $text = '', $keyboard = false)
```
## sendPhoto
```php
$bot->sendPhoto($chat_id, $file, $text = '', $keyboard = false);
```
## sendVoice
```php
$bot->sendVoice($chat_id, $file, $text = '', $keyboard = false);
```
## sendAudio
```php
$bot->sendAudio($chat_id, $file, $text = '', $keyboard = false);
```
## sendVideo
```php
$bot->sendVideo($chat_id, $file, $text = '', $keyboard = false);
```
## sendAnimation
```php
$bot->sendAnimation($chat_id, $file, $text = '', $keyboard = false);
```
## sendVideoNote
```php
$bot->sendVideoNote($chat_id, $file, $text = '', $keyboard = false);
```
## deleteMessage
Works only for bot message. **You cannot delete user message.** 
```php
$bot->deleteMessage($chat_id, $message_id);
```
## editMessageText
```php
$bot->editMessageText($message_id, $text = '', $keyboard = false);
```
## editMessageCaption
```php
$bot->editMessageCaption($message_id, $text = '', $keyboard = false);
```
## editMessageReplyMarkup
```php
$bot->editMessageReplyMarkup($message_id, $keyboard = false);
```
## answerInlineQuery
```php
$bot->answerInlineQuery($results = [], $scopes = []);
```
## getFile
Get data about a file or save it right away.
```php
$bot->getFile($file_id, $local_file_path = false);
$bot->getFile($file_id);
$bot->getFile($file_id, '/storage/files/{basename}'); // immediately save to server
```
## saveFile
Save file on server.
```php
// $file_path_url - this is result by $bot->getFile()['result']['file_path'];
$bot->saveFile($file_path_url, $local_file_path = false);
$bot->saveFile($file_path_url, '/storage/files/{basename}');
```




