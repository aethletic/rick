<?php 

// Random screenshot from LightShot.

require '/vendor/autoload.php';

use Botify\Core\Bot;

$bot = new Bot('1234567890:ABC_TOKEN');

$bot->hear(['{default}'], function () use ($bot) {
    $bot->reply('Whoops! Please, click button.');
});

$bot->command(['/\/start/'], function () use ($bot) {
    $bot->say('Good luck, netstalker! ✊', $bot->keyboard->show([
        ['🔍 Random Screenshot']
    ]));
});

$bot->hear(['🔍 Random Screenshot'], function () use ($bot) {
    $code = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 6)), 0, 6);
    $bot->say("https://prnt.sc/{$code}");
});
