
# Botify 🧙‍♂️
#### Simple & developer-friendly Telegram Bot Api Framework for PHP.

Skeleton template for this framework can be found here [botify-template](https://github.com/aethletic/botify-template).

Modules for this framework:
- (rus) [botify-module.start](https://github.com/aethletic/botify-module.start) - notification about new users
- (rus) [botify-module.admin](https://github.com/aethletic/botify-module.admin) - manage users 

> **NOTE:** Include module file immediately after the bot initialization.
```php
include __DIR__ . '/modules/botify.start/module.php';
include __DIR__ . '/modules/botify.admin/module.php';
```
Do not forget to pass the module settings when initializing the bot.

## ⭐ Features 
 - Easy Localization
 - Database (MySQL, SQLite)
 - Cache
 - Modules (Extensions)
 - Easy Users Manage
 - All In One Object

# Documentation
## 📜 Table of Contents
 - [Installation](#-installation)
 - [Example: Hello World Bot](#-example-helloworld-bot)
 - [Create Bot](#-create-bot)
 - [Available Variables](#-available-variables)
 - [Methods](#-methods)
 - [Keyboard](#-keyboard)
 - [Events](#-events)
 - [Default Telegram Methods](#-default-telegram-methods)
 - [File Upload](#-file)
 - [States](#-states)
 - [Database](#-database)
 - [Cache](#-cache)
 - [Localization](#-localization)
 - [Logs](#-logs)
 
 ## 📦 Installation 
 
```
$ composer require aethletic/botify
```

## 👀 Example: HelloWorld Bot
```php
use Botify\Core\Bot;

require '/vendor/autoload.php';

$bot = new Bot('1234567890:ABC_TOKEN');

$bot->hear('Hello', function () use ($bot) {
    $bot->say('Hello World! 🌎');
});

$bot->run();
```

## 🐣 Create Bot
When creating a bot, you can pass the second parameter with the configuration of the bot.

It is not necessary to specify all parameters. 
You can specify only those that you will use or you can omit the parameters at all.
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
    // > db - using database (faster than cache)
    // > cache - using cache
    'state.driver'      => 'db',

    // (OPTIONAL) store logs file
    'log.dir'           => __DIR__ . '/../storage/logs',

    // (OPTIONAL) if you have modules, set config here for they
    'module.name'      => [
        'param1' => 'value1',
        'param2' => 'value2',
    ]
];

$bot = new Bot('1234567890:ABC_TOKEN', $config);
```

# 💡 Available Variables
```php
$bot->update;
$bot->message_id;
$bot->callback_id;
$bot->inline_id;
$bot->chat_id;
$bot->chat_name;
$bot->chat_username;
$bot->full_name;
$bot->first_name;
$bot->last_name;
$bot->is_bot;
$bot->username;
$bot->lang;
$bot->message;
$bot->inline_query;
$bot->inline_offset;
$bot->callback_data;

$bot->state_name;
$bot->state_data;

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

# 🕹 Methods
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

## random
A random element from an array.
```php 
$bot->random($array);
```
```php
$names = ['Ivan', 'Maria', 'John', 'Anna', 'Robert'];
$bot->say('Random name: ' . $bot->random($names));
```

## randomReplica
You can store all replica options in one associative array, and then use them in different places.
```php 
$bot->randomReplica($key);
```
```php
$replics = [
    'start' => [
        'Random Replica #1',
        'Random Replica #2',
        'Random Replica #3',
    ],
    'denied' => [
        'Access denied.',
        'You dont have access for this.',
        'Sorry, not today.',
    ],
];

// add replics to bot
$bot->register('replics', $replics);

$bot->say($bot->randomReplica('denied')); // Sorry, not today.
```

## randomEmoji
You can store all emoji options in one associative array, and then use them in different places.
```php 
$bot->randomEmoji($key);
```
```php
$emojis = [
    'happy' => ['😋', '🥳', '😊'],
    'sad' => [ '😭', '😢', '☹']
];

// add emojis to bot
$bot->register('emojis', $emojis);

$bot->say($bot->randomEmoji('happy')); // 🥳
```

## shuffle
You can diversify messages of the same type by sending different variants of words or phrases.
To do this, put a word or phrase in the view structure `{{word1|word2|etc}}`.

> **NOTE:** Methods `say()` and `reply()` support this method by default. 
> You don’t have to use shuffle for them.

```php 
$bot->shuffle($text);
```
```php 
$bot->shuffle('It was {{cool|nice|ok}}!'); // It was nice!
$bot->shuffle('Hello! My name is {{Ivan|Maria}}. I am {{21|25|45}} yo.'); // Hello! My name is Ivan. I am 21 yo.
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

## time
Get the execution time of the script in seconds.
```php 
// $n - how many characters will be after the dot.
$bot->time($n = 2); // $n default: 2
```
```php
$time = $bot->time();
$bot->say("Execution time: {$time} sec."); // Execution time: 1.32 sec.
```
```php
$time = $bot->time(5);
$bot->say("Execution time: {$time} sec."); // Execution time: 1.32172 sec.
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

## getUpdate
Get array of update from `php://input`.
```php
$update = $bot->getUpdate();
```

## isUpdate
Checks if there is an array with the update.
```php
if ($bot->isUpdate()) {
    // update exists
}
```

## request
A universal way to call any Telegram method.
All Telegram methods can be found [here](https://core.telegram.org/bots/api#available-methods).
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

# ⌨ Keyboard
The keyboard can be used as a static method `Keyboard::method`, for this you need to specify to use the name `use Botify\Core\Keyboard`.
Or as an object `$bot->keyboard`.
## Keyboard::show
```php
Keyboard::show($keyboard, $resize = true, $one_time = false);
```

```php 
use Botify\Core\Keyboard;

$keyboard = [
    ['👍', '👎']
];

$bot->say('Standart Keyboard', Keyboard::show($keyboard));
$bot->say('Standart Keyboard', $bot->keyboard->show($keyboard));
```

## Keyboard::inline
```php
Keyboard::inline($inline);
```

```php
use Botify\Core\Keyboard;

$keyboard = [
    [
        ['text' => '👍', 'callback_data' => 'thumb_up'],
        ['text' => '👎', 'callback_data' => 'thumb_down'],
    ],
    [
        ['text' => 'Botify 🔥', 'url' => 'https://botify.ru/'],
    ]
]

$bot->say('Inline Keyboard', Keyboard::inline([
    ['👍', '👎']
]));

$bot->say('Inline Keyboard', $bot->keyboard->inline([
    ['👍', '👎']
]));
```

## Keyboard::hide
```php
use Botify\Core\Keyboard;
$bot->say('The keyboard is hidden.', Keyboard::hide())
$bot->say('The keyboard is hidden.', $bot->keyboard->hide())
```

## Keyboard::add
You can add an associative array with keyboards and then use them many times anywhere.
```php
use Botify\Core\Keyboard;

$keyboards = [
    // you can store standart keyboards
    'numbers' => [
        ['7', '8', '9'],
        ['4', '5', '6'],
        ['1', '2', '3'],
             ['0']
    ],

    // and can store inline keyboards
    'thumbs' => [
        [
            ['text' => '👍', 'callback_data' => 'thumb_up'],
            ['text' => '👎', 'callback_data' => 'thumb_down'],
        ],
        [
            ['text' => 'Botify 🔥', 'url' => 'https://botify.ru/'],
        ]
    ],
];

Keyboard::add($keyboard);

$bot->say('Press thumb up!', Keyboard::inline('thumbs'));
$bot->say('Calculate?', $bot->keyboard->show('numbers'));
```

## Keyboard::contact
```php
Keyboard::contact($text = 'Share contact telephone number.', $resize = true, $one_time = false);
```
```php 
use Botify\Core\Keyboard;
$bot->say('Please, share ur telephone number.', Keyboard::contact('Share!'));
```

## Keyboard::location
```php
Keyboard::location($text = 'Share location.', $resize = true, $one_time = false);
```
```php 
use Botify\Core\Keyboard;
$bot->say('Please, share ur location.', Keyboard::location('Share!'));
```

# 🎈 Events

## hear
Catch a text message from a user.
```php
$bot->hear('ping?', function () use ($bot) {
    $bot->say('pong!');
});
```
```php
$bot->hear('{default}', function () use ($bot) {
    $bot->say('Default answer...');
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
```php
$bot->command('{default}', function () use ($bot) {
    $bot->say('Default answer...');
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
```php
$bot->callback('{default}', function () use ($bot) {
    $bot->say('Default answer...');
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
## isSpam

> **NOTE:** These events are available ONLY if a database is connected.

Alternative for `$bot->user->isSpam`.
```php
$bot->isSpam(function ($spam) use ($bot) {
    // code... 
});
```
## isNewVersion

> **NOTE:** These events are available ONLY if a database is connected.

Alternative for `$bot->user->isNewVersion`.
```php
$bot->isNewVersion(function ($version) use ($bot) {
    // code... 
});
```
## isBanned

> **NOTE:** These events are available ONLY if a database is connected.

Alternative for `$bot->user->isBanned`.
```php
$bot->isBanned(function ($comment, $expire_end) use ($bot) {
    // code... 
});
```
## isAdmin

> **NOTE:** These events are available ONLY if a database is connected.

Alternative for `$bot->user->isAdmin`.
```php
$bot->isAdmin(function () use ($bot) {
    // code... 
});
```
## isNewUser

> **NOTE:** These events are available ONLY if a database is connected.

Alternative for `$bot->user->isNewUser`.

If the user has been added to the database. And he had not been there before.
```php
$bot->isNewUser(function () use ($bot) {
    // code... 
});
```

# 🛩 Default Telegram Methods
All Telegram methods can be found [here](https://core.telegram.org/bots/api#available-methods).
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
# 📝 File
If you want to send a local file (photo, document, audio, etc.) use `File::upload`.
```php 
use Botify\Core\File;
$bot->sendPhoto($chat_id, File::upload('/storage/photos/gf_nudes.jpg'));
$bot->sendDocument($chat_id, File::upload('/storage/file/secretly.pdf'));
```

# 📌 States
**States** - with the help of states you can store data about the current user action.
For example, it is useful if the user passes the survey and you expect him to answer a specific question.
And many other use cases.

> **NOTE:** States are available ONLY if a database is connected OR cache enabled.

You can choose `state.driver` - `db` or `cache`. 
Pass it in config when bot initialization.

## Init State driver "db"
State driver "db" faster than "cache".
```php 
$config = [
    'db.driver' => 'sqlite',
    'db.path' => '/path/to/db.sqlite',
    'state.driver' => 'db',
];

$bot = new Bot('1234567890:ABC_TOKEN', $config);
```

## Init  State driver "cache"
```php 
$config = [
    'cache.driver' => 'memcached',
    'cache.host' => 'localhost',
    'cache.port' => '11211',
    'state.driver' => 'cache',
];

$bot = new Bot('1234567890:ABC_TOKEN', $config);
```

## setState
If you need to save an array, you need to convert it to a json object (`json_encode`). 
And upon receipt of `json_decode`.

`$state_data` - optional parameter.

```php
$bot->setState($state_name, $state_data = null);
```

## getState
Returns an array with the `name` and `data` of the state.
```php
$state_data = $bot->getState();
```
`$state_data` is:
```bash
Array (
    [name] => 'name_of_state',
    [data] => 'data_of_state'
)
```

## clearState
```php
$bot->clearState();
```

## clearStateById
```php
$bot->clearStateById($user_id); // Telegram user_id
```

## state
Case, you have set the name of the state and are waiting for a message from the user to do something.
To do this, you can use the `state` method before the methods `hear`, `command` and `callback`.
```php
$bot->state('choose_car')->hear(['ferrari'], function () use ($bot) {
    // do something
});
```

## state_name (variable)
You can get state name.
```php 
$state_name = $bot->state_name;
```

## state_data (variable)
You can get state data.
```php 
$state_data = $bot->state_data;
```

# 🗃 Database
For work with database [used this library](https://github.com/mrjgreen/database).

Before using the database, create a `users` table.
```sql
/* users.sql */
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `first_message` int(11) DEFAULT NULL,
  `last_message` int(11) DEFAULT NULL,
  `role` text COLLATE utf8mb4_unicode_ci,
  `full_name` text COLLATE utf8mb4_unicode_ci,
  `first_name` text COLLATE utf8mb4_unicode_ci,
  `last_name` text COLLATE utf8mb4_unicode_ci,
  `username` text COLLATE utf8mb4_unicode_ci,
  `nickname` text COLLATE utf8mb4_unicode_ci,
  `lang` text COLLATE utf8mb4_unicode_ci,
  `ban` tinyint(4) DEFAULT NULL,
  `ban_comment` text COLLATE utf8mb4_unicode_ci,
  `ban_start` int(11) DEFAULT NULL,
  `ban_end` int(11) DEFAULT NULL,
  `state_name` text COLLATE utf8mb4_unicode_ci,
  `state_data` text COLLATE utf8mb4_unicode_ci,
  `bot_version` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

```

## Connect SQLite
```php
$config = [
    'db.driver' => 'sqlite',
    'db.path' => '/path/to/db.sqlite',
];

$bot = new Bot('1234567890:ABC_TOKEN', $config);
```

## Connect MySQL
```php
$config = [
    'db.host' => 'localhost',
    'db.database' => 'database_name',
    'db.username' => 'admin',
    'db.password' => 'p@$$w0rD',
    'db.charset' => 'utf8_mb4',
    'db.lazy' => true
]

$bot = new Bot('1234567890:ABC_TOKEN', $config);
```

## Usage
```php
$bot->db;
$bot->db->table('users')->get();
$bot->db->table('users')->count();
$bot->db->table('users')->where('user_id', '=', $user_id)->get();
```
More methods and examples for work with database see [here](https://github.com/mrjgreen/database).


# 🔖 Cache
[Memcached](https://www.php.net/manual/en/book.memcached.php) is used for caching.

> **NOTE:** Before work, make sure that you have the installed memcached exstension.

## Usage
```php
$config = [
    'cache.driver' => 'memcached',
    'cache.host' => 'localhost',
    'cache.port' => '11211',
];

$bot = new Bot('1234567890:ABC_TOKEN', $config);

$bot->cache->get($key);
$bot->cache->set($key, $data, $expire);
$bot->cache->delete($key);
```
More methods and examples for Memcached see [here](https://www.php.net/manual/en/book.memcached.php).

# 🌎 Localization

## Usage
```php
$bot->loc->setLang($bot->lang); // $bot->lang is user language from update.
$bot->loc->setDefaultLang('en'); // default language if user language is empty or there is no template for it.

// add lang templates
$bot->loc->add(require __DIR__ . '/localization/ru.php'); 
$bot->loc->add(require __DIR__ . '/localization/en.php');
```

The structure of the language template.
The template can be in a json object or a php array.
```php 
// file: /localization/en.php
<?php
return [
    'en' => [
        'START' => 'You launched the bot!',
        'HELLO' => 'Hi!',
    ],
];

$bot->loc->add(require __DIR__ . '/localization/en.php');

// file: index.php
$bot->say($bot->loc->get('HELLO'));
```

```php 
// file: /localization/en.json
{ 
    "en": { 
        "START": "You launched the bot!", 
        "HELLO": "Hi!" 
    } 
}
```
To pass a variable:
```php
// file: /localization/en.php
<?php
return [
    'en' => [
        'HELLO' => 'Hello {name}!',
    ],
];

$bot->loc->add(require __DIR__ . '/localization/en.php');

// file: index.php
$bot->say($bot->loc->get('HELLO', [
    'name' => $bot->first_name,
]));
```

# ✏ Logs
You can keep logging.
If you pass the `log.dir` parameter during bot initialization, the `log` method will be available.

> **NOTE**: Automatically logged every update.

```php
$bot->log->add($array, $caption = null);
```
```php
$bot->log->add($bot->update);
$bot->log->add($bot->update['message']['from'], 'message_from');
```



