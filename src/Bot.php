<?php

namespace Botify;

use Aethletic\App\Container;
use Botify\Exception;
use Botify\Http\Request;
use Botify\Util;
use Botify\Methods;
use Botify\Extension\Keyboard;
use Botify\Extension\Database;
use Botify\Extension\User;
use Botify\Extension\Cache;
use Botify\Extension\File;
use Botify\Extension\Localization;
use Botify\Extension\Log;

class Bot extends Container
{
  private const __VERSION__ = '4.0.0';

  public $token;
  public $api_url = 'https://api.telegram.org/bot';

  public $request;
  public $keyboard;
  public $db;
  public $user;
  public $cache;
  public $loc;
  public $util;
  public $log;

  public $update;
  private $actions  = [];
  private $messages = [];
  private $commands = [];
  private $middlewares = [];
  private $middleware_callback = false;
  private $needState;
  private $stopWords;
  private $start_time;
  private $extender = [];
  private $is_skipped = false;
  private $extenders = [];
  private $replics = [];
  private $emojis = [];
  private $before_run = false;

  private $default_answers = ['{default}', '{any}'];

  public $config = [
    'bot.token'               => '1234567890:ABC_DE',
    'bot.url'                 => 'https://example.com/bot.php',
    'bot.web'                 => 'https://example.com/web/',
    'bot.version'             => '1.0.0',
    'bot.debug'               => true,
    'bot.timezone'            => 'Europe/Samara',
    'bot.spam_timeout'        => 1,
    'bot.default_lang'        => 'en',
    'bot.max_system_load'     => 0.80,
    'bot.max_execution_time'  => 60,

    'admin.list'              => ['botify'],
    'admin.password'          => 'hackme',

    'telegram.parse_mode'     => 'html',

    'database.check_tables'   => false,
    'database.driver'         => false,
    'database.path'           => '/path/to/database.sqlite',
    'database.host'           => 'localhost',
    'database.database'       => 'botify',
    'database.username'       => 'botify',
    'database.password'       => 'hackme',
    'database.charset'        => 'utf8mb4',
    'database.collation'      => 'utf8mb4_unicode_ci',
    'database.lazy'           => true,

    'cache.driver'            => false,
    'cache.host'              => 'localhost',
    'cache.port'              => '11211',

    'log.db_store_messages'   => false,
    'log.autolog'             => false,
    'log.dir'                 => '/path/to/log/dir/',
  ];

  public $isInline = false;
  public $isSticker = false;
  public $isVoice = false;
  public $isAnimation = false;
  public $isDocument = false;
  public $isAudio = false;
  public $isPhoto = false;
  public $isPoll = false;
  public $isVideoNote = false;
  public $isVideo = false;
  public $isContact = false;
  public $isLocation = false;
  public $isVenue = false;
  public $isDice = false;
  public $isNewChatMembers = false;
  public $isLeftChatMember = false;
  public $isNewChatTitle = false;
  public $isNewChatPhoto = false;
  public $isDeleteChatPhoto = false;
  public $isChannelChatCreated = false;
  public $isMigrateToChatId = false;
  public $isMigrateFromChatId = false;
  public $isPinnedMessage = false;
  public $isInvoice = false;
  public $isSucessfulPayment = false;
  public $isConnectedWebsite = false;
  public $isPassportData = false;
  public $isReplyMarkup = false;
  public $isCommand = false;
  public $isForward = false;
  public $isSuperGroup = false;
  public $isGroup = false;
  public $isChannel = false;
  public $isPrivate = false;
  public $isCaption = false;
  public $isEditedMessage = false;
  public $isCallback = false;
  public $isMessage = false;

  function __construct($token = false, $config = [], $fakeUpdateJson = false)
  {
    $this->start_time = microtime(true);

    parent::__construct();

    if (!$token) {
      throw new Exception\Bot('The bot token was not passed.');
    }

    $this->token = $token;
    $this->config = array_merge($this->config, $config);

    set_time_limit($this->config['bot.max_execution_time']);
    $is_debug = $this->config['bot.debug'];
    ini_set('display_errors', $is_debug);
    error_reporting($is_debug ? E_ALL : 0);
    date_default_timezone_set($this->config['bot.timezone']);

    // debug
    $this->update = $fakeUpdateJson ? json_decode($fakeUpdateJson, true) : $this->getUpdate();
    $this->setIsVars();
    $this->setUpdateVars();

    // Extensions
    $this->request = new Request($this->api_url . $this->token);
    $this->keyboard = new Keyboard();
    $this->util = new Util();
    $this->db = (new Database)->connect();
    if (@$this->config['database.check_tables'] == true && $this->db) {
      (new Database)->initTables($this->db);
    }

    $cache_driver = @$this->config['cache.driver'];
    $cache_host = @$this->config['cache.host'];
    $cache_port = @$this->config['cache.port'];
    if ($cache_driver == 'memcache' || $cache_driver == 'memcached') {
      $this->cache = Cache::memcached($cache_host, $cache_port);
    } elseif ($cache_driver == 'redis') {
      $this->cache = Cache::redis($cache_host, $cache_port);
    }

    // User extension include only if Update exists and Database connected
    if ($this->isUpdate() && $this->db) {
      $this->user = new User($this->user_id);
    }

    if ($this->isUpdate() && $this->db && @$this->config['log.db_store_messages'] == true) {
      Log::addMessagesToDatabase($this);
    }

    if (@$this->config['log.dir']) {
      $this->log = new Log($this->config['log.dir']);
      if (@$this->config['log.autolog'] && $this->isUpdate()) {
        $this->log->add($this->update, 'autolog');
      }
    }

    // If Database connected, get user lang from table else get from Telegram update
    $this->loc = new Localization($this->db ? @$this->user->data['lang'] : @$this->lang, @$this->config['bot.default_lang']);

    // Mapping extension methods
    $this->set('api', false, function ($method, $parameters = [], $is_file = false) {
      return $this->request->call($method, $parameters, $is_file);
    });

    $this->set('shuffle', false, function ($message = '') {
      return $this->util->shuffle($message);
    });

    $this->set('upload', false, function ($file = false) {
      return File::upload($file);
    });

    $this->set('l', false, function ($key, $params = []) {
      return $this->loc->get($key, $params);
    });

    // Include methods
    $this->addExtender(new Methods($this->self()));
  }

  public function __call($name, $params){
    $res = parent::__call($name, $params);
    if ($res == 'CONTAINER_NEXT_CALL') {
      foreach($this->extenders as $extender){
         if (method_exists($extender, $name)){
            return call_user_func_array(array($extender, $name), $params);
         }
      }
    } else {
      return $res;
    }
  }

  public function addExtender($obj){
    $this->extenders[] = $obj;
  }

  public function getUpdate($array = true)
  {
      $update = file_get_contents('php://input');
      return !$update ? false : json_decode($update, $array);
  }

  public function isUpdate()
  {
      return is_array($this->update);
  }

  private function setUpdateVars()
  {
      if ($this->isUpdate()) {
          $update = $this->update;
      } else {
          return;
      }

      $key = '';
      if (array_key_exists('message', $update))
          $key = 'message';

      if (array_key_exists('edited_message', $update))
          $key = 'edited_message';

      if ($key == 'edited_message' || $key == 'message') {
          $this->message_id = $update[$key]['message_id'];
          $this->chat_id = $update[$key]['chat']['id'];
          $this->chat_name = trim($update[$key]['chat']['first_name'] . ' ' . @$update['message']['chat']['last_name']) ?? null;
          $this->chat_username = $update[$key]['chat']['username'] ?? null;
          $this->user_id = $update[$key]['from']['id'];
          $this->full_name = trim($update[$key]['from']['first_name'] . ' ' . @$update['message']['from']['last_name']);
          $this->first_name = $update[$key]['from']['first_name'] ?? null;
          $this->last_name = @$update[$key]['from']['last_name'] ?? null;
          $this->is_bot = $update[$key]['from']['is_bot'];
          $this->username = $update[$key]['from']['username'] ?? null;
          $this->lang = $update[$key]['from']['language_code'] ?? null;
          $this->message = array_key_exists('text', $update[$key]) ? trim($update[$key]['text']) : trim($update[$key]['caption']);
          return;
      }

      if (array_key_exists('callback_query', $update)) {
          $this->message_id = $update['callback_query']['message']['message_id'];
          $this->callback_id = $update['callback_query']['id'];
          $this->chat_id = $update['callback_query']['message']['chat']['id'];
          $this->chat_name = trim($update['callback_query']['message']['chat']['first_name'] . ' ' . @$update['callback_query']['message']['chat']['last_name']) ?? null;
          $this->chat_username = $update['callback_query']['message']['chat']['username'] ?? null;
          $this->user_id = $update['callback_query']['from']['id'];
          $this->full_name = trim($update['callback_query']['from']['first_name'] . ' ' . @$update['callback_query']['from']['last_name']);
          $this->first_name = $update['callback_query']['from']['first_name'] ?? null;
          $this->last_name = @$update['callback_query']['from']['last_name'] ?? null;
          $this->username = $update['callback_query']['from']['username'] ?? null;
          $this->is_bot = $update['callback_query']['from']['is_bot'];
          $this->lang = $update['callback_query']['from']['language_code'] ?? null;
          $this->message = array_key_exists('text', $update['callback_query']['message']) ? trim($update[$key]['text']) : trim($update['callback_query']['message']);
          $this->callback_data = $update['callback_query']['data'];
          return;
      }

      if (array_key_exists('inline_query', $update)) {
          $this->inline_id = $update['inline_query']['id'];
          $this->user_id = $update['inline_query']['from']['id'];
          $this->chat_id = $update['inline_query']['from']['id'];
          $this->is_bot = $update['inline_query']['from']['is_bot'];
          $this->first_name = $update['inline_query']['from']['first_name'] ?? null;
          $this->chat_name = trim($update['inline_query']['from']['first_name'] . ' ' . @$update['inline_query']['from']['last_name']);
          $this->last_name = @$update['inline_query']['from']['last_name'] ?? null;
          $this->username = $update['inline_query']['from']['username'] ?? null;
          $this->chat_username = $update['inline_query']['from']['username'] ?? null;
          $this->lang = $update['inline_query']['from']['language_code'];
          $this->full_name = trim($update['inline_query']['from']['first_name'] . ' ' . @$update['inline_query']['from']['last_name']);
          $this->inline_query = $update['inline_query']['query'];
          $this->inline_offset = $update['inline_query']['offset'];
          return;
      }
  }

  private function setIsVars()
  {
      if (!$this->update)
          return;

      if (array_key_exists('callback_query', $this->update)) {
          $this->isCallback = $this->update['callback_query'];
          return;
      }

      if (array_key_exists('inline_query', $this->update)) {
          $this->isInline = $this->update['inline_query'];
          return;
      }

      if (array_key_exists('message', $this->update)) {
          $this->isMessage = $this->update['message'];
          $key = 'message';
      }

      if (array_key_exists('edited_message', $this->update)) {
          $this->isEditedMessage = $this->update['edited_message'];
          $key = 'edited_message';
      }

      if (!$this->isMessage and !$this->isEditedMessage)
          return;

      $this->isBot = $this->update[$key]['from']['is_bot'];

      if (array_key_exists('sticker', $this->update[$key]))
          $this->isSticker = $this->update[$key]['sticker'];

      if (array_key_exists('voice', $this->update[$key]))
          $this->isVoice = $this->update[$key]['voice'];

      if (array_key_exists('animation', $this->update[$key]))
          $this->isAnimation = $this->update[$key]['animation'];

      if (array_key_exists('document', $this->update[$key]))
          $this->isDocument = $this->update[$key]['document'];

      if (array_key_exists('audio', $this->update[$key]))
          $this->isAudio = $this->update[$key]['audio'];

      if (array_key_exists('photo', $this->update[$key]))
          $this->isPhoto = $this->update[$key]['photo'];

      if (array_key_exists('video', $this->update[$key]))
          $this->isVideo = $this->update[$key]['video'];

      if (array_key_exists('poll', $this->update[$key]))
          $this->isPoll = $this->update[$key]['poll'];

      if (array_key_exists('video_note', $this->update[$key]))
          $this->isVideoNote = $this->update[$key]['video_note'];

      if (array_key_exists('contact', $this->update[$key]))
          $this->isContact = $this->update[$key]['contact'];

      if (array_key_exists('location', $this->update[$key]))
          $this->isLocation = $this->update[$key]['location'];

      if (array_key_exists('venue', $this->update[$key]))
          $this->isVenue = $this->update[$key]['venue'];

      if (array_key_exists('dice', $this->update[$key])) {
          $this->isDice = $this->update[$key]['dice'];
          $this->dice = $this->update[$key]['dice'];
      }

      if (array_key_exists('new_chat_members', $this->update[$key]))
          $this->isNewChatMembers = $this->update[$key]['new_chat_members'];

      if (array_key_exists('left_chat_member', $this->update[$key]))
          $this->isLeftChatMember = $this->update[$key]['left_chat_member'];

      if (array_key_exists('new_chat_title', $this->update[$key]))
          $this->isNewChatTitle = $this->update[$key]['new_chat_title'];

      if (array_key_exists('new_chat_photo', $this->update[$key]))
          $this->isNewChatPhoto = $this->update[$key]['new_chat_photo'];

      if (array_key_exists('delete_chat_photo', $this->update[$key]))
          $this->isDeleteChatPhoto = $this->update[$key]['delete_chat_photo'];

      if (array_key_exists('channel_chat_created', $this->update[$key]))
          $this->isChannelChatCreated = $this->update[$key]['channel_chat_created'];

      if (array_key_exists('migrate_to_chat_id', $this->update[$key]))
          $this->isMigrateToChatId = $this->update[$key]['migrate_to_chat_id'];

      if (array_key_exists('migrate_from_chat_id', $this->update[$key]))
          $this->isMigrateFromChatId = $this->update[$key]['migrate_from_chat_id'];

      if (array_key_exists('pinned_message', $this->update[$key]))
          $this->isPinnedMessage = $this->update[$key]['pinned_message'];

      if (array_key_exists('invoice', $this->update[$key]))
          $this->isInvoice = $this->update[$key]['invoice'];

      if (array_key_exists('successful_payment', $this->update[$key]))
          $this->isSucessfulPayment = $this->update[$key]['successful_payment'];

      if (array_key_exists('connected_website', $this->update[$key]))
          $this->isConnectedWebsite = $this->update[$key]['connected_website'];

      if (array_key_exists('passport_data', $this->update[$key]))
          $this->isPassportData = $this->update[$key]['passport_data'];

      if (array_key_exists('reply_markup', $this->update[$key]))
          $this->isReplyMarkup = $this->update[$key]['reply_markup'];

      if (array_key_exists('entities', $this->update[$key]))
          $this->isCommand = $this->update[$key]['entities'][0]['type'] == 'bot_command' ? true : false;

      if (array_key_exists('forward_date', $this->update[$key]) || array_key_exists('forward_from', $this->update[$key]))
          $this->isForward = true;

      if ($this->update[$key]['chat']['type'] == 'supergroup')
          $this->isSuperGroup = true;

      if ($this->update[$key]['chat']['type'] == 'group')
          $this->isGroup = true;

      if ($this->update[$key]['chat']['type'] == 'channel')
          $this->isChannel = true;

      if ($this->update[$key]['chat']['type'] == 'private')
          $this->isPrivate = true;

      if (array_key_exists('caption', $this->update[$key]))
          $this->isCaption = $this->update[$key]['caption'];
  }

  public function state($state_name, $stop_words = [])
  {
      $this->needState = $state_name;
      $this->stopWords = !is_array($stop_words) ? [$stop_words] : $stop_words;
      return $this;
  }

  public function checkState()
  {
      if (in_array($this->message, $this->stopWords))
        return false;

      if ($this->needState !== null && $this->needState !== $this->user->state_name) {
          $this->needState = null;
          return false;
      }

      $this->needState = null;
      return true;
  }

  public function on($needle_keys = [], $callback)
  {
    if (!$this->isUpdate()) {
      return;
    }

    if (!is_array($needle_keys)) {
      $needle_keys = [$needle_keys];
    }

    $result = $this->checkKeys($this->update, $needle_keys, []);

    if (sizeof($result) == 0) {
      return false;
    }

    return call_user_func_array($callback, [$result]);
  }

  private function checkKeys($array, $needle_keys, $result) {
    foreach ($array as $key => $value) {
      if (in_array($key, $needle_keys, true)) {
        $this->print($needle_keys);
        $result[$key] = $value;
      }

      if (is_array($value)) {
         $result = $this->checkKeys($value, $needle_keys, $result);
      }
    }
    return $result;
  }

  public function hear($messages = null, $callback = null)
  {
      $middleware_callback = $this->getCurrentMiddleware();

      if (!$messages || !$callback)
          return;

      if (!@$this->checkState())
          return;

      if (!is_array($messages))
          $messages = [$messages];

      foreach ($messages as $message) {
          $id = in_array($message, $this->default_answers) ? $message : rand().rand();
          $this->messages[$id]['callback'] = $callback;
          $this->messages[$id]['data'] = $message;
          $this->messages[$id]['middleware'] = $middleware_callback;
      }
  }

  public function command($commands = null, $callback = null)
  {
      $middleware_callback = $this->getCurrentMiddleware();

      if (!$commands || !$callback)
          return;

      if (!@$this->checkState())
          return;

      if (!is_array($commands))
          $commands = [$commands];

      foreach ($commands as $command) {
          $id = in_array($command, $this->default_answers) ? $command : rand().rand();
          $this->commands[$id]['callback'] = $callback;
          $this->commands[$id]['data'] = $command;
          $this->commands[$id]['middleware'] = $middleware_callback;
      }
  }

  public function callback($actions = null, $callback = null)
  {
      if ($this->isSkipped()) return;

      $middleware_callback = $this->getCurrentMiddleware();

      if (!$actions || !$callback)
          return;

      if (!@$this->checkState())
          return;

      if (!is_array($actions))
          $actions = [$actions];

      foreach ($actions as $action) {
          $id = in_array($action, $this->default_answers) ? $action : rand().rand();
          $this->actions[$id]['callback'] = $callback;
          $this->actions[$id]['data'] = $action;
          $this->actions[$id]['middleware'] = $middleware_callback;
      }
  }

  public function onCommand($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isCommand)
        call_user_func_array($callback, [$this->message]);
  }

  public function onSticker($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isSticker)
        call_user_func_array($callback, [$this->isSticker]);
  }

  public function onVoice($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isVoice)
        call_user_func_array($callback, [$this->isVoice]);
  }

  public function onDocument($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isDocument)
        call_user_func_array($callback, [$this->isDocument]);
  }

  public function onAnimation($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isAnimation)
        call_user_func_array($callback, [$this->isAnimation]);
  }

  public function onPhoto($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isPhoto)
        call_user_func_array($callback, [$this->isPhoto]);
  }

  public function onAudio($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isAudio)
        call_user_func_array($callback, [$this->isAudio]);
  }

  public function onVideoNote($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isVideoNote)
        call_user_func_array($callback, [$this->isVideoNote]);
  }

  public function onContact($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isContact)
        call_user_func_array($callback, [$this->isContact]);
  }

  public function onLocation($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isLocation)
        call_user_func_array($callback, [$this->isLocation]);
  }

  public function onPoll($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isPoll)
        call_user_func_array($callback, [$this->isPoll]);
  }

  public function onDice($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isDice)
        call_user_func_array($callback, [$this->isDice['emoji'], $this->isDice['value']]);
  }

  public function onInline($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isInline)
        call_user_func($callback);
  }

  public function onCallback($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isCallback)
        call_user_func($callback);
  }

  public function onMessage($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isMessage)
        call_user_func($callback);
  }

  public function onEditedMessage($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isEditedMessage)
        call_user_func($callback);
  }

  public function onVideo($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isVideo)
        call_user_func_array($callback, [$this->isVideo]);
  }

  public function fromPrivate($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isPrivate)
        call_user_func($callback);
  }

  public function fromChannel($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isChannel)
        call_user_func($callback);
  }

  public function fromGroup($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isChannel)
        call_user_func($callback);
  }

  public function fromSuperGroup($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->isChannel)
        call_user_func($callback);
  }

  public function onSpam($callback)
  {
    if (!@$this->checkState())
        return;

    if (@$this->user->isSpam)
        call_user_func_array($callback, [@$this->user->isSpam]);
  }

  public function onNewVersion($callback)
  {
    if (!@$this->checkState())
        return;

    if (@$this->user->isNewVersion)
        call_user_func_array($callback, [$this->config['bot.version']]);
  }

  public function onBanned($callback)
  {
    if (!@$this->checkState())
        return;

    if (@$this->user->isBanned)
        call_user_func_array($callback, [@$this->user->data['ban_comment'], @$this->user->data['ban_to']]);
  }

  public function onAdmin($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->user->isAdmin)
        call_user_func($callback);
  }

  public function onNewUser($callback)
  {
    if (!@$this->checkState())
        return;

    if ($this->user->isNewUser)
        call_user_func($callback);
  }

  public function run()
  {

    header('Content-Type: application/json');
    if (!$this->isUpdate()) {
      header('Content-Type: application/json');
      $version = self::__VERSION__;
      $botify = "no updates
  _           _   _  __
 | |__   ___ | |_(_)/ _|_   _
 | '_ \ / _ \| __| | |_| | | |
 | |_) | (_) | |_| |  _| |_| |_
 |_.__/ \___/ \__|_|_|  \__, (_)
                        |___/
                        v{$version}";
      die($botify);
    }

    if ($this->before_run) {
      call_user_func($this->before_run);
    }

    echo 'ok';

    // command
    if ($this->isCommand) {
        $has_answer = false;
        foreach ($this->commands as $key => $command) {
            if ($this->isSkipped()) return;

            if ($this->util->isRegEx($command['data'])) {
                preg_match($command['data'], $this->message, $res);
                if (sizeof($res) > 0) {
                  if ($command['middleware']) {
                    $result = call_user_func($command['middleware']);
                    if ($result === false) {
                      continue;
                    }
                  }
                  call_user_func_array($command['callback'], is_string($command['callback']) ? [$this] : []);
                  $has_answer = true;
                  continue;
                }
            }
            if ($command['data'] !== $this->message) {
              continue;
            }

            if ($command['middleware']) {
              $result = call_user_func($command['middleware']);
              if ($result === false) {
                continue;
              }
            }
            call_user_func_array($command['callback'], is_string($command['callback']) ? [$this] : []);
            $has_answer = true;
            continue;
        }

        if ($has_answer) { return; }

        // default answer
        if (array_key_exists('{default}', $this->commands)) {
            $callback = $this->commands['{default}']['callback'];

            if ($this->commands['{default}']['middleware']) {
              $result = call_user_func($this->commands['{default}']['middleware']);
              if ($result === false) {
                return false;
              }
            }

            return call_user_func_array($callback, is_string($callback) ? [$this] : []);
        }

        return false;
    }

    // hear
    if ($this->isMessage || $this->isEditedMessage) {

      $has_answer = false;
      foreach ($this->messages as $key => $message) {
          if ($this->isSkipped()) return;

          if ($this->util->isRegEx($message['data'])) {
              preg_match($message['data'], $this->message, $res);
              if (sizeof($res) > 0) {
                if ($message['middleware']) {
                  $result = call_user_func($message['middleware']);
                  if ($result === false) {
                    continue;
                  }
                }
                call_user_func_array($message['callback'], is_string($message['callback']) ? [$this] : []);
                $has_answer = true;
                continue;
              }
          }

          if ($message['data'] !== $this->message) {
            continue;
          }

          if ($message['middleware']) {
            $result = call_user_func($message['middleware']);
            if ($result === false) {
              continue;
            }
          }

          $rs = call_user_func_array($message['callback'], is_string($message['callback']) ? [$this] : []);
          var_dump($rs);
          $has_answer = true;
          continue;
      }

      if ($has_answer) { return; }

      if (array_key_exists('{default}', $this->messages)) {
          $callback = $this->messages['{default}']['callback'];

          if ($this->messages['{default}']['middleware']) {
            $result = call_user_func($this->messages['{default}']['middleware']);
            if ($result === false) {
              return false;
            }
          }

          return call_user_func_array($callback, is_string($callback) ? [$this] : []);
      }

      return false;
    }

    // callback
    if ($this->isCallback) {
      $has_answer = false;
        foreach ($this->actions as $key => $action) {
            if ($this->isSkipped()) return;
            if ($this->util->isRegEx($action['data'])) {
                preg_match($action['data'], $this->callback_data, $res);
                if (sizeof($res) > 0) {
                  if ($action['middleware']) {
                    $result = call_user_func($action['middleware']);
                    if ($result === false) {
                      continue;
                    }
                  }
                  call_user_func_array($action['callback'], is_string($action['callback']) ? [$this] : []);
                  $has_answer = true;
                  continue;
                }
            }
            if ($action['data'] !== $this->callback_data) {
              continue;
            }

            if ($action['middleware']) {
              $result = call_user_func($action['middleware']);
              if ($result === false) {
                continue;
              }
            }

            call_user_func_array($action['callback'], is_string($action['callback']) ? [$this] : []);
            $has_answer = true;
            continue;
        }

        if ($has_answer) { return; }

        if (array_key_exists('{default}', $this->actions)) {
            $callback = $this->actions['{default}']['callback'];

            if ($this->actions['{default}']['middleware']) {
              $result = call_user_func($this->actions['{default}']['middleware']);
              if ($result === false) {
                return false;
              }
            }

            return call_user_func_array($callback, is_string($callback) ? [$this] : []);
        }

        return false;
    }
  }

  public function stop($text = false)
  {
    header('Content-Type: application/json');
    die($text);
  }

  public function addMiddleware($name, $callback)
  {
    if ($this->checkMiddleware($name)) {
        throw new Exception\Bot("Middleware \"{$name}\" already exists.");
    }

    $this->middlewares[$name] = $callback;
  }

  public function checkMiddleware($name)
  {
    return array_key_exists($name, $this->middlewares);
  }

  private function getCurrentMiddleware()
  {
    $middleware = $this->middleware_callback;
    $this->middleware_callback = false;
    return $middleware;
  }

  public function middleware($name)
  {
    if (!$this->checkMiddleware($name)) {
        throw new Exception\Bot("Middleware \"{$name}\" not exists.");
    }

    $this->middleware_callback = $this->middlewares[$name];

    return $this;
  }

  public function time($number_ends = 2)
  {
      return round(microtime(true) - $this->start_time, $number_ends);
  }

  public function print($str)
  {
    $this->say(print_r($str, true));
  }

  public function getSystemLoad()
  {
    return sys_getloadavg();
  }

  public function onMaxSystemLoad($callback)
  {
    $load = $this->getSystemLoad();
    if ($load[0] > $this->config['bot.max_system_load']) {
      call_user_func_array($callback, [$load]);
    }
  }

  public function skipAll()
  {
    $this->is_skipped = true;
  }

  public function isSkipped()
  {
    return $this->is_skipped;
  }

  public function parse($delimiter = ' ')
  {
      if ($this->isCallback) {
          return explode($delimiter, $this->callback_data);
      } else if ($this->isInline) {
          return explode($delimiter, $this->inline_query);
      } else if ($this->isMessage || $this->isEditedMessage || $this->isCommand) {
          return explode($delimiter, $this->message);
      }
  }

  public function randomReplica($key)
  {
      shuffle($this->replics[$key]);
      return $this->replics[$key][array_rand($this->replics[$key])];
  }

  public function addReplics($array)
  {
    $this->replics = $array;
  }

  public function randomEmoji($key)
  {
      shuffle($this->emojis[$key]);
      return $this->emojis[$key][array_rand($this->emojis[$key])];
  }

  public function addEmojis($array)
  {
    $this->emojis = $array;
  }

  public function addKeyboards($array)
  {
    $this->keyboard->add($array);
  }

  public function beforeRun($callback)
  {
    $this->before_run = $callback;
  }
}
