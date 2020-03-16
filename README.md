# Rick 🧙‍♂️

Простая PHP библиотека для работы с Telegram Bot Api.

Ready-for-use шаблон для быстрого старта [aethletic/rick-template](https://github.com/aethletic/rick-template "aethletic/rick-template").

## Установка
```
$composer require aethletic/rick
```

## Инициализация
```php
use Aethletic\Telegram\Bot;

$rick = new Bot('1234:token', [
    'rick.logs' => true,
    'rick.logs_dir' => '/var/logs',
]);
```

## Простой пример бота
```php
use Aethletic\Telegram\Bot;

$rick = new Bot('1234:token');

$rick->hear('Привет', function() use ($rick) {
    $rick->say("И тебе привет!");
});

$rick->run();
```


## getUpdates()
Получить исходный массив с данными.
```php
$updates = $rick->getUpdates();

// Array
// (
//     [update_id] => 1234567890
//     [message] => Array
//         (
//             [message_id] => 1337
//             [from] => Array
//                 (
//                     [id] => 43643
//                     [is_bot] =>
//                     [first_name] => Pavel
//                     [username] => durov
//                     [language_code] => ru
//                 )
//
//             [chat] => Array
//                 (
//                     [id] => 43643
//                     [first_name] => Pavel
//                     [username] => durov
//                     [type] => private
//                 )
//
//             [date] => 1234567890
//             [text] => Hello World
//         )
//
// )
```

## getUser()
Получить обработанный массив $updates
```php
$user = $rick->getUser();
```

## say($message, $keyboard)
Ответить в чат, откуда пришло сообщение.

[Обязательно] `$message` текст сообщения;

[Опционально] `$keyboard` клавиатура собранная методом `keyboard()` или `inline()`;

Просто сообщение:
```php
$rick->say('Привет мир!');
```
Выбрать случайные слова из конструкции {{слово1|слово2}}
```php
$rick->say('{{Привет|Хеллоу|Хай|Хало|Алоха|Шалом}} мир!');
```
Сообщение с клавиатурой:
```php
$rick->say('Привет мир!', $rick->keyboard([
	['Кнопка1', 'Кнопка2']
]));
```
Сообщение с inline-клавиатурой
```php
$rick->say('Привет мир!', $rick->inline([
        [
            ['text' => 'Поиск в Google', 'url' => 'https://google.com'],
        ])
]);
```

## hear(string $message OR array $message, $callback)
Своебразный роутинг сообщений.

[Обязательно] `$message` сообщение от пользователя;
[Обязательно] `$callback` анониманая функция или метод класса;

Простой пример с методом **say()**:
```php
$rick->hear('Привет', function() use ($rick, $user) {
    $rick->say("Привет {$user['firstname']}!");
});
```
Сообщение по умолчанию (например, если пользователь написал какую-то дичь)
```php
$rick->hear('{default}', function() use ($rick) {
    $rick->say('К сожалению, я вас не понял...');
});
```

Массив с возможными сообщениями:
```php
$rick->hear(['Привет','Hello','Hi'], function() use ($rick, $user) {
    $rick->say("И тебе привет, {$user['firstname']}!");
});
```
Пример с использованием классов (назовем их Events):
```php
$rick->hear(['/start','Магазин','Главная'], 'Shop::index');
$rick->hear('Товары', 'Shop::products');
$rick->hear('Баланс', 'Shop::balance');
```
```php
class Shop
{
    public static function index($rick, $user, $updates)
    {
        $rick->say("Информация о магазине...");
    }

    public static function products($rick, $user, $updates)
    {
        $rick->say("Показываем товары...");
    }

    public static function balance($rick, $user, $updates)
    {
        $rick->say("Показываем баланс пользователя {$user['firstname']}...");
    }
}
```

В идеале, Events нужно использовать как контроллеры в MVC, только Events выполняют роль как контроллера, так и модели, то есть запросы в БД можно делать из Event класса.

# keyboard(string $keyboard OR array $keybaord OR false, $resize = true, $one_time = false)
Метод создания клавиатуры.

[Обязательно] `$keyboard` название заранее подгнотовленной клавиатуры ИЛИ массив с клавиатурой;

[Опционально] `$resize` по умолчанию `true` - маленькие кнопки, `false` - огромные кнопки;

[Опционально] `$one_time` скрывать клавиатуру после нажатия, по умолчанию `false`;


Простой пример:
```php
$keyboard = $rick->keyboard([
        ['1','2','3'],
        ['4','5','6'],
        ['7','8','9'],
            ['0']
]);

$rick->say("Отправляю клавиатуру", $keyboard);
```

Чтобы убрать клавиатуру у пользователя, нужно передать значение `false` в методе `$rick->keyboard()`

Пример:
```php
$rick->say("Клавиатура спрятана", $rick->keyboard(false));
```

Заранее подготовленные клавиатуры, можно использовать как с обычной клавиатурой, так и с inline:
```php
$keyboards = [
    'Цифры' => [
        ['1','2','3'],
        ['4','5','6'],
        ['7','8','9'],
            ['0']
    ],
    'Инлайн' => [
        [
            ['text' => 'Колбэк1', 'callback_data' => 'some_callback_data1'],
        ],
        [
            ['text' => 'Колбэк2', 'callback_data' => 'some_callback_data2'],
            ['text' => 'Поиск в Google', 'url' => 'https://google.com'],
        ]
    ],
];

$rick->register('keyboards', $keyboards);

$rick->say("Отправляю клавитару", $rick->keyboard('Цифры'));
$rick->say("Отправляю inline-клавитару", $rick->inline('Инлайн'));

// или без register(), напрямую из массива $keyboards
$rick->say("Отправляю клавитару", $rick->keyboard($keyboards['Цифры']);

// или можно так
$keyboards = [
	'Цифры' => $rick->keyboard([
		['1','2','3'],
		['4','5','6'],
		['7','8','9'],
		    ['0']
    	]),
];

$rick->say("Еще один пример", $keyboards['Цифры']);
```

## inline(string $keyboard OR array $keyboard)
Метод создания inline-клавиатуры.

[Обязательно] `$keyboard` название заранее подгнотовленной клавиатуры ИЛИ массив с клавиатурой;

Пример:
```php
$keyboard = [
	[
		['text' => 'Колбэк1', 'callback_data' => 'some_callback_data1'],
	],
	[
		['text' => 'Колбэк2', 'callback_data' => 'some_callback_data2'],
		['text' => 'Поиск в Google', 'url' => 'https://google.com'],
	]
];

$rick->say("Отправляю inline-клавиатуру", $rick->inline($keyboard));
```

## callback(string $callback_data OR array $callback_data, $callback)
Метод обработки колбэк данных из inline-клавиатуры. Работает аналогично как метод `hear()`.

[Обязательно] `$callback_data` данные из callback_data inline-клавиатуры;

[Обязательно] `$callback` анониманая функция или метод класса;


В `callback()` можно использовать метод `notify($message)`, который отправит всплывающее уведомление в чате с пользователем.

Простой пример обработки колбэка:
```php
$rick->callback('some_callback_data1', function() use ($rick) {
    $rick->say('Колбэк успешно обработан!');
});
```

Пример с уведомлением:
```php
$rick->callback('some_callback_data1', function() use ($rick) {
    $rick->say('Колбэк успешно обработан!');
    $rick->notify('Ой, тут еще и уведомление!');
});
```

Как и метод `hear()`, поддерживает ответ по умолчанию, если например забыли добавить обработчик для колбэка и т. п.:
```php
$rick->callback('{default}', function() use ($rick) {
    $rick->say('Неизсветный колбэк...');
});
```
## execute(string $method, array $params, bool $is_file)
Универсальный метод выполнения практически любых методов из документации телеграма.

[Обязательно] `$method` название метода из документации телеграма, с учетом регистра, например, `sendMessage`;
[Обязательно] `$params` массив с параметрами из документации телеграма;

[Опционально] `$is_file` если передается файла, установить `true`, чтобы поменять заголовки;

Пример:
```php
$rick->execute('sendMessage', [
	'chat_id' => $user['chat_id'],
	'text' => 'Пример отправки сообщения',
	'reply_markup' => $rick->keyboard('Цифры'),
]);
```
```php
use Aethletic\Telegram\File;

$rick->execute('sendDocument', [
	'chat_id' => $user['chat_id'],
	'caption' => 'Пример отправки файла',
	'document' => File::upload('/storage/book.pdf'), // или просто ссылка на файл (только gif, pdf, zip)
]);
```

## sendMessage(...), sendDocument(...), sendPhoto(...)
Обертки над методом `execute()`.

```php
$rick->sendMessage($user['chat_id'], 'Текст сообщения', $rick->keyboard('Цифры'));

// конструкцию `{{}}` из метода `say()` можно так же вызвать отдельным методом `randomMessage($message)`
$rick->sendMessage($user['chat_id'], $rick->randomMessage('Сегодня на улице {{солнечная|дождливая|пасмурная}} погода.') , $rick->keyboard('Цифры'));
```

```php
use Aethletic\Telegram\File;

$rick->sendDocument($user['chat_id'], File::upload('/storage/book.pdf'), 'Пример отправки файла', $rick->keyboard('Цифры'));
```

```php
use Aethletic\Telegram\File;

$rick->sendPhoto($user['chat_id'], File::upload('/storage/image.png'), 'Пример отправки картинки', $rick->keyboard('Цифры'));
```
