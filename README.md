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
$composer require aethletic/botify
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
