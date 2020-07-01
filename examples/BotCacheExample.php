<?php 

use Botify\Core\Bot;

require '/vendor/autoload.php';

$config = [
  'cache.driver' => 'redis',
  'cache.host' => '127.0.0.1',
  'cache.port' => '6379'
];

$bot = new Bot('1234567890:ABC_TOKEN', $config);

// use regex for insensetive case
$bot->command(['/\/set/iu'], function () use ($bot) {
  // parse message (default separator " " space)
  // you can be pass other separator like $bot->parse('_');
  [$cmd, $data] = $bot->parse();
  $bot->cache->set('some_key', $data);
  $bot->say("Store: {$data}");
});

$bot->command(['/get'], function () use ($bot) {
  $data = $bot->cache->get('some_key');
  $bot->say("Get: {$data}");
});

$bot->command(['/clear'], function () use ($bot) {
  $bot->cache->del('some_key'); // method "del" for redis, "delete" for memcached
  $bot->say("Successfully deleted.");
});

$bot->command(['{default}'], function () use ($bot) {
  $bot->say('This command not found, sorry.');
});

$bot->run();
