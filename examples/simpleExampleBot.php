<?php

use Aethletic\Telegram\Bot;

require './vendor/autoload.php';

$rick = new Bot('1234567890:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA');
$user = $rick->getUser();
$update = $rick->getUpdates();

// можно инициализировать переменные, для удобства (не обязательно)
[$chat_id,$chat_name,$chat_username,$id,$fullname,$firstname,$lastname,$username,$lang,$message] = array_values($user);

$rick->register('keyboards', [

    'main' => [
        ['🙃', '🥶', '😡'],
        ['🤖'],
    ],

    'unknown' => [
        ['Неизвестный текст 🤫'],
        ['👈 Назад'],
    ],

    'example.inline' => [
        [
            ['text' => 'Google', 'url' => 'https://google.com'],
        ]
    ]

]);

$rick->hear(['/start', '👈 Назад'], function () use ($rick, $user, $message) {
    if ($message == '👈 Назад')
        return $rick->say('Хорошо, вернулись в главное меню.', $rick->keyboard('main'));

    $rick->action('typing')->say("*Привет!*\n\nНажми на клавиатуру:", $rick->keyboard('main'));
});

$rick->hear(['🙃', '🥶', '😡'], function () use ($rick, $user) {
    $rick->say('Отправь мне первую кнопку.', $rick->keyboard('unknown'));
});

$rick->action('typing')->hear('{default}', function () use ($rick, $user) {
    $rick->say('Oh shit, я не понял тебя 💩');
});

$rick->hear('🤖', function () use ($rick, $user) {
    $rick->say('Погуглим?', $rick->inline('example.inline'));
});

$rick->run();
