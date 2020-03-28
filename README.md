# RICK 🧙‍♂️

Простая PHP библиотека для работы с Telegram Bot Api.

Ready-for-use шаблон для быстрого старта [aethletic/rick-template](https://github.com/aethletic/rick-template "aethletic/rick-template").

## Внимание
#### Могут встретиться грамматические/синтаксические ошибки 🙄

## Установка
```
$ composer require aethletic/rick
```

### Инициализация
```php
use Aethletic\Telegram\Bot;

$rick = new Bot('1234:token');
```

Так же, при инициализации можно передать массив с настройками бота.
`rick.logs` - Записывать логи сообщений, по умолчанию `false` (выключено).
`rick.logs_dir` - директория где хранятся логи. Логи будут записывать в файл с названием текущей даты. Таким образом каждый день будет создаваться новый файл.
`rick.default_parse_mode` - Вариант разметки страницы, по умолчанию `markdown`. 

Пример инициализации с настройками: 

```php 
use Aethletic\Telegram\Bot;

$rick = new Bot('1234:token', [
	'rick.logs' => true,
	'rick.logs_dir' => '/var/logs',
	'rick.default_parse_mode' => 'markdown',
]);
```

### Простой пример бота
```php
use Aethletic\Telegram\Bot;

$rick = new Bot('1234:token');

$rick->hear('Привет', function() use ($rick) {
    $rick->say("И тебе привет!");
});

$rick->run();
```


### say($message, $keyboard)

Метод отправки сообщения в чат из которого пришло сообщение. 

По задмуке, должен использоваться с методом **hear()** *(слушает - говорит)*.

`$message` string  (обязательно) - текст сообщения.

`$keyboard` json  (опционально) - json-объект из метода **keyboard($array or $string)** или **inline($array or $string)**.

**Примеры:**

```php
$rick->say("Привет мир!");
```

```php
$keyboard = [
	['1','2','3'],
	['4','5','6'],
	['7','8','9'],
	    ['0']
];

$rick->say("Привет мир!", $rick->keyboard($keyboard));
```

```php
$keyboard = [
	['1','2','3'],
	['4','5','6'],
	['7','8','9'],
	    ['0']
];

$rick->say("Привет мир!", $rick->keyboard($keyboard));
```

```php
// Создаем массив со статичными клавиатурами (те, которые не изменяются и вызываются в разных местах)
$keyboards = [
    'цифры' => [
        ['1','2','3'],
        ['4','5','6'],
        ['7','8','9'],
            ['0']
	],
];

// Заносим массив с клавиатурами в переменную бота
$rick->register('keyboards', $keyboards);

// Обращаемся по названию ключа клавиатуры 
$rick->say("Привет мир!", $rick->keyboard('цифры'));
```

В методе **say()** доступна по умолчанию  функция **randomMessage($message)**, которая позволяет из нескольких слов/предложений выбрать случайное. Для этого нужно использовать конструкцию вида ```{{слово 1|слово 2|слово 3|etc...}}```.

Пример:

```php 
$rick->say("Меня зовут {{Саша|Вася|Петя|Маша}}");
```

### edit($message_id, $message, $keyboard)

Редактирует текст и кнопки **обычного** сообщения, не отправляя нового. 

Редактировать можно только сообщения бота, **НЕ** пользователя.

Аналог методов **say()** и **reply()**.

Не отправляет сообщение, а только редактирует.

`$message_id` integer  (обязательно) - ID сообщения бота, которое нужно отредактировать.

`$message` string  (обязательно) - новый текст сообщения.

`$keyboard` json  (опционально) - json-объект из метода **keyboard($array or $string)** или **inline($array or $string)**.

**Примеры:**

```php 
$rick->edit('1234567890', 'Сообщение отредактировано');
```

```php
// Отправляем сообщение в чат
$data = $rick->say('Старый текст сообщения');

// Получаем его $message_id
$message_id = $data['result']['message_id'];

// Редактируем текст передавая $message_id.
// Так же, можно передать клавиатуру (опционально)
$rick->edit($message_id, 'Новый текст сообщения');
```

### reply($message_id, $message, $keyboard)

Отвечает на любое сообщение переслав его.

Аналог методов **say()** и **edit()**.

`$message_id` integer  (обязательно) - ID сообщения, на которое должен ответить бот.

`$message` string  (обязательно) - текст сообщения.

`$keyboard` json  (опционально) - json-объект из метода **keyboard($array or $string)** или **inline($array or $string)**.

**Примеры:**

```php
$rick->reply('1234567890', 'Ответ на сообщение');
```

```php
// Получаем массив с данными о входящем сообщении
$updates = $rick->getUpdates();

// Получаем $message_id сообщения пользователя
$message_id = $updates['message']['message_id'];

// Отвечаем на сообщения пользователя, передав $message_id
// Так же, можно передать клавиатуру (опционально)
$rick->reply($message_id, 'Ответ на сообщение');
```

### hear($message_text, $callback)

Метод листинга сообщений, что-то наподобия роутинга. 

По задмуке, должен использоваться с методом **say()** *(слушает - говорит)*.

`$message_text` string|array (обязательно) - Строка или массив с текстом сообщений от пользователя.

`$callback` string|func  (обязательно) - колбэк для обработки сообщения. Можно передать анонимную функцию или статический метод класса как строку.

**Примеры:**

```php
$rick->hear('/start', function() use ($rick) {
	// здесь обрабатываем сообщение
});
```

Для доступа к переменным из анонимной функции, их нужно передать в параметрах: `use($rick, $user, $updates)`.

Пример:

```php
$rick->hear('/start', function() use($rick, $user, $updates) {
	// здесь обрабатываем сообщение
});
```

Но, если колбэк не анонимная функция, а метод класса, ничего передавать не нужно, а только указать в самом методе первые 3 параметра `$rick, $user, $updates`.

Пример:

```php
$rick->hear('/start', 'Event::firstStart');

// В файле /bot/events/Event.php
class Event
{
    public static function firstStart($rick, $user, $updates)
    {
	// здесь обрабатываем сообщение
    }
}
```

Если нужно, чтобы бот отвечал одним сообщением на разные варианты сообщения пользователя, то можно передать массив с вариантами сообщений.

Пример:

```php
$rick->hear(['Привет', 'Hello', 'Aloha'], function() use($rick, $user) {
	$rick->say("Привет, {$user['firstname']}!");
});
```

Если сообщение пользователя неразспознано, можно воспользоваться универсальным ответом (заглушкой) `{default}`

Пример:

```php
$rick->hear('{default}', function() use ($rick) {
	$rick->say('К сожалению, сообщение не распознано.');
});
```

### callback($callback_data, $callback)

Метод листинга колбэк даты, аналог метода **hear()**.

Только в этом методе доступен **notify($message)**, метод который отправляет в чат всплывающее оповещение с заданым текстом.

`$callback_data`  string|array (обязательно) - Строка или массив с текстом колбэк даты, которая была передана в inline-клавиатуре.

`$callback` string|func  (обязательно) - колбэк для обработки сообщения. Можно передать анонимную функцию или статический метод класса как строку.

**Примеры:**

Если колбэк нераспознан, можно воспользоваться универсальным ответом (заглушкой) `{default}`

```php
$rick->callback('{default}', function() use ($rick) {
    $rick->notify('Ошибка: колбэк не определён!');
});
```

```php
$rick->callback('some_callback_data', function() use ($rick) {
    $rick->say('Колбэк обработан');
});
```

```php
$rick->callback('some_callback_data', 'Callback::someAnswer');

// В файле /bot/callbacks/Callback.php
class Callback
{
    public static function someAnswer($rick, $user, $updates)
    {
	// здесь обрабатываем колбэк дату
    }
}
```

### action($action)

Отправляет в чат информацию, например, "печатает", "отправляет фото" и подобное.

Доступные действия: **typing, upload_photo, record_video, upload_video, record_audio, upload_audio, upload_document, find_location, record_video_note, upload_video_note**

`$action` string (обязательно) - Код действия.

**Примеры:**

```php
$rick->action('typing');
```

Можно использовать следующую конструкцию со всеми методами.

**Важно:** Метод **action()**, должен быть всегда первый, иначе бот не ответит.

```php
$rick->action('typing')->say('Привет мир');
```

```php
$rick->action('typing')->edit($message_id, 'Привет мир');
```

```php
$rick->action('typing')->reply($message_id, 'Привет мир');
```

### notify($message)

Выводит всплывающее уведомление в чат в пользователем.

Используется **только** внутри метода **callback()**.

`$message` string (обязательно) - Текст оповещения.

**Примеры:**

```php
$rick->notify('Привет мир');
```

```php
$rick->callback('example_callback_data', function() use ($rick) {
	$rick->notify('Привет мир');
});
```

Так же, можно проверить, есть ли колбэк данные в массиве $updates или нет с помощью метода **isCallback()**.

**Важно:** работает только если телеграм прислал масив `$updates`.
```php
if ($rick->isCallback()) {
	$rick->notify('Привет мир');
}
```

### isCallback()

Проверяет, есть ли в массиве `$updates` ключ колбэк даты.

**Примеры:**
```php
if ($rick->isCallback()) {
	// колбэк дата есть
} else {
	// колбэк даты нет
}
```

### getUpdates()

Получить массив с данными о сообщении который прислал телеграм.

**Примеры:**

```php
$updates = $rick->getUpdates();

print_r($updates);

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

### getUser()

Получить данные о пользователе и чате из которого пришло сообщение.

Это обработанный массив `$updates`, созданный для удобства.

Ключи: 

`chat_id` - ID чата откуда пришло сообщение (пользователь/беседа/канал).

`chat_name` - Название пользователя/беседы/канала.

`chat_username` - Юзернейм пользователя/беседы/канала.

`id` - ID пользователя который прислал сообщение.

`fullname` - Имя Фамилия пользователя который прислал сообщение.

`firstname` - Имя пользователя который прислал сообщение.

`lastname` - Фамилия пользователя который прислал сообщение.

`username` - Юзернейм пользователя который прислал сообщение.

`lang` - Язык пользователя который прислал сообщение.

`message` - Текст сообщения или колбэк дата.

**Примеры:**

```php
$user = $rick->getUser();
```

```php
// Инициализировать массив в переменные
[$chat_id,$chat_name,$chat_username,$id,$fullname,$firstname,$lastname,$username,$lang,$message] = $rick->getUser();

$rick->say("Привет $firstnname, твой юзернейм: $username");
```

### keyboard($keyboard)

Конструктор обычной клавиатуры.

Принимает на входе массив с клавиатурой, либо строку с названием клавиатуры которая была зарегистрирована ранее.

Чтобы спрятать клавиатуру, нужно передать параметр `false`.

После создания клавиатуры возвращает json-объект.

`$keyboard` array|string|bool (обязательно) - Клавиатура.

`$resize` bool (опционально) - Большие или маленькие кнопки, по умолчанию `true` (маленькие).

`$one_time` bool (опционально) - Скрыть клавиатуру после нажатия на нее, по умолчанию `false` (не скрывать).

**Примеры:**

Если клавиатура может использоваться много раз и в разных местах, можно ее зарегистрировать:

```php
$keyboards = [
    'цифры' => [
        ['1','2','3'],
        ['4','5','6'],
        ['7','8','9'],
            ['0']
    ],
    'пример' => [
        ['Картинка','Файл'],
        ['Клавиатура', 'Inline-клавиатура'],
        ['Спрятать клавиатуру'],
        ['Анимация'],
    ],
	// так же тут можно указывать и инлайн клавиатуры. О них ниже.
];

$rick->register('keyboards', $keyboards);

$rick->action('typing')->say("Пример обычной клавиатуры", $rick->keybaord('цифры'));
```

```php
$keyboard = [
	['1','2','3'],
	['4','5','6'],
	['7','8','9'],
	    ['0']
];

$rick->say("Пример обычной клавиатуры", $rick->keyboard($keyboard));
```

```php
$rick->say("Пример обычной клавиатуры", $rick->keyboard([
	['кнопка 1'], ['кнопка 2']
]));
```

Спрятать клавиатуру:

```php
$rick->say("Клавиатура скрыта", $rick->keyboard(false));
```

### inline($keyboard)

Конструктор инлайн-клавиатуры.

`$keyboard` array|string|bool (обязательно) - Клавиатура.

**Примеры:**

```php
$keyboards = [
    'инлайн' => [
        [
            ['text' => 'Google', 'url' => 'https://google.com'],
            ['text' => 'Yandex', 'url' => 'https://yandex.ru'],
        ],
        [
			// этот колбэк дату можно будет обработать методом calback('example_callback_data', $callback)
            ['text' => 'Известный колбэк', 'callback_data' => 'example_callback_data'], 
        ]
    ],
];

$rick->register('keyboards', $keyboards);

$rick->action('typing')->say("Пример инлайн-клавиатуры", $rick->keybaord('инлайн'));
```

```php
$keyboard = [
        [
            ['text' => 'Google', 'url' => 'https://google.com'],
            ['text' => 'Yandex', 'url' => 'https://yandex.ru'],
        ]
];

$rick->action('typing')->say("Пример инлайн-клавиатуры", $rick->keybaord($keyboard));
```

### execute($method, $params, $is_file)

Универсальный метод вызова всех методов телеграма.

`$method` string (обязательно) - Название метода из документации телеграма.

`$params` array (опционально) - Массив с параметрами  из документации телеграма.

`$is_file` bool (опционально) - Если в параметрах передается локальный файл, нужно указать `true`, по умолчанию `false`.

**Примеры:**

```php
$params = [
	'chat_id' => $chat_id,
	'text' => 'Привет!',
];

$rick->execute('sendMessage', $params);
);
```

```php
use Aethletic\Telegram\File;

$params = [
	'chat_id' => $chat_id,
	'document' => File::upload('/storage/archive.zip'),
	'caption' => 'Отправка файла',
];

$rick->execute('sendDocument', $params);
);
```

### File::upload($file)

Чтобы отправить локальный файл, нужно вспользоваться методом `File::upload('путь до файла')`. 

Исключение - ссылка на файл/фото/аудио/прочее.

**Примеры:**

```php
use Aethletic\Telegram\File;

$rick->sendPhoto($chat_id, File::upload('/storage/dick_pic.jpg'), 'Классная картинка!');
```

```php
$rick->sendPhoto($chat_id, 'https://example.com/photo.jpg', 'Картинка из интернета');
```

### sendPhoto, sendVideo, sendAudio, sendVoice, sendAnimation, sendDocument

Логика у этих методов одинаковая:

`$chat_id` string|integer (обязательно) - ID чата куда отправить.

`$file` string (обязательно) - Ссылка на файл или локальный файл `File::upload`.

`$message` string (опционально) - Текст сообщения.

`$keyboard` json  (опционально) - json-объект из метода **keyboard($array or $string)** или **inline($array or $string)**.

**Примеры:**
```php
$rick->sendPhoto($chat_id, 'https://example.com/photo.jpg');
$rick->sendPhoto($chat_id, File::upload('/storage/dick_pic.jpg'));

$rick->sendVideo($chat_id, 'https://example.com/video.mp4');
$rick->sendVideo($chat_id, File::upload('/storage/video.mp4'));

$rick->sendAudio($chat_id, 'https://example.com/audio.mp3');
$rick->sendAudio($chat_id, File::upload('/storage/audio.mp3));

// и т. п.
```

### sendMessage($chat_id, $message, $keyboard)

Отправить сообщение пользваотелю/чат/канал.

`$chat_id` string|integer (обязательно) - ID чата куда отправить.

`$message` string (обязательно) - Текст сообщения.

`$keyboard` json  (опционально) - json-объект из метода **keyboard($array or $string)** или **inline($array or $string)**.

**Примеры:**

```php
$rick->sendMessage($chat_id, 'Привет мир!', $rick->keyboard('цифры'));
```

### sendAction$chat_id, $action)

Отправить действие пользователю/чат.

Аналог метода **action()**.

`$chat_id` string|integer (обязательно) - ID чата куда отправить.

`$action` string (обязательно) - Код действия.

Доступные действия: **typing, upload_photo, record_video, upload_video, record_audio, upload_audio, upload_document, find_location, record_video_note, upload_video_note**

**Примеры:**

```php
$rick->sendAction($chat_id, 'typing');
```

```php
$rick->sendAction($chat_id, 'typing')->sendMessage($chat_id, 'Привет мир!');
```

### randomMessage($message)

Выбрать из констуркции ```{{слово 1|слово 2|слово 3|etc...}}``` случайное слово или предложение. Таких конструкций в сообщение может быть неограниченное кол-во.

`$message` string (обязательно) - Строка с текстом.

**Примеры:**

```php
$message = $rick->randomMessage('Сегодня {{солнечная|пасмурная|облачная|дождливая}} погода.');

$rick->sendMessage($chat_id, $message);
```
