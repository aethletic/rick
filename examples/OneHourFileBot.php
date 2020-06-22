<?php

/**
 * Simple example use Memcache cache.
 * The bot allows you to share files.
 * The link to the file will be available for exactly one hour,
 * after the expiration of time it deletes and the link to
 * the file will no longer be available.
 *
 * Demo Bot - @OneHourFileBot
 * @see https://t.me/OneHourFileBot
 */  

use Botify\Core\Bot;

require __DIR__ . '/vendor/autoload.php';

// set minimal config
$config = [
  'bot.token'         => '1234567890:ABC_TOKEN',
  'cache.driver'      => 'memcached',
  'cache.host'        => 'localhost',
  'cache.port'        => '11211',
];

// create our bot
$bot = new Bot($config['bot.token'], $config);

// bot username without @ (can be set in config "bot.username")
$bot->botUserName = "OneHourFileBot";

// store time in seconds (3600 sec = 1 hour)
$bot->storeTime = 3600;

// code lenght "t.me/botusername?start=ABCDE"
$bot->codeLenght = 5;

// set default message just for reuse
$bot->defaultMessage = "*Send a file to share it.*\n" .
                       "In response, I will send a link to the file.\n" .
                       "The link will be available *one hour*.\n" .
                       "After the expiration of *the link will never be available again*.\n";

// listin /start command
$bot->command(['/\/start/iu'], function () use ($bot) {
  // parse message "/start ABCDE"
  [$cmd, $code] = $bot->parse();

  if (!$code) {
    return $bot->say($bot->defaultMessage);
  }

  $cacheKey = setCacheKey($code);
  $file_id = $bot->cache->get($cacheKey);

  if (!is_string($file_id)) {
      return $bot->say("*Whoops!*\nThe link has expired.");
  }

  $bot->sendDocument($bot->chat_id, $file_id, "@{$bot->botUserName}");
});

// listin if user send document file
$bot->onDocument(function ($document) use ($bot) {
  // generate random code
  $code = setCode($bot->codeLenght);

  // set cache key for store
  $cacheKey = setCacheKey($code);

  // store file_id
  $bot->cache->set($cacheKey, $document['file_id'], $bot->storeTime);

  // make share link
  $shareLink = "https://t.me/{$bot->botUserName}?start={$code}";

  $msg = "📅 Expire date:\n`" . date("d.m.Y H:i:s", strtotime("+1 hour")) . " (UTC+4:00)`\n\n" .
         "📎 Copy:\n" .
         "`{$shareLink}`\n\n" .
         "🌎 Direct:\n" .
         "{$shareLink}";

  die($bot->reply($msg));
});

// default answer for any messages
$bot->hear(['{default}'], function () use ($bot) {
  $bot->say($bot->defaultMessage);
});

// default answer for any commands
$bot->command(['{default}'], function () use ($bot) {
  $bot->reply('This command do not exists.');
});

// just generate random code
function setCode($lenght = 6)
{
  $chars = array_merge(range('a', 'z'), range('A', 'Z'), range(0, 1));

  shuffle($chars);
  shuffle($chars);
  shuffle($chars);

  $code = '';
  for ($i=0; $i < $lenght; $i++) {
    $code .= $chars[array_rand($chars)];
  }

  return $code;
}

// set cache key (for often reuse)
function setCacheKey($code)
{
  return md5("_FILE_SHARE_ID_{$code}");
}

