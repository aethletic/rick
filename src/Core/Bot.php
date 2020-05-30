<?php

namespace Botify\Core;

use Botify\Core\Keyboard;
use Botify\Core\Cache;
use Botify\Core\File;
use Botify\Core\Database;
use Botify\Core\Logger;
use Botify\Core\User;
use Botify\Core\Localization;

require_once __DIR__ . '/Keyboard.php';
require_once __DIR__ . '/Cache.php';
require_once __DIR__ . '/File.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Localization.php';

class Bot
{
    private $base_url = 'https://api.telegram.org/bot';
    private $token;
    public  $start_time;
    private $actions  = [];
    private $messages = [];
    private $commands = [];
    private $replics = [];
    private $emojis = [];
    public  $update;
    private $client;
    public  $cache;
    public  $loc;
    public  $db;
    public  $user;
    public  $config = [
        'bot.version'       => '0.0.0',
        'bot.url'           => '',
        'bot.username'      => '',
        'bot.timezone'      => 'Europe/Samara',
        'bot.token'         => '',
        'bot.default_lang'  => 'ru',
        'admin.list'        => ['aethletic'],
        'admin.password'    => ['qwerty'],
        'log.dir'           => false,
        'tg.parse_mode'     => 'markdown',
        'cache.driver'      => false,
        'cache.host'        => 'localhost',
        'cache.port'        => '11211',
        'spam.timeout'      => 1,
        'db.driver'         => false, // sqlite, mysql
        'db.path'           => '/path/to/db.sqlite',
        'db.host'           => 'localhost',
        'db.database'       => 'database_name',
        'db.username'       => 'admin',
        'db.password'       => 'password',
        'db.charset'        => 'utf8',
        'db.lazy'           => true,
        'db.insert'         => [],
        'state.driver'      => 'cache' // cache, db
    ];

    public function __construct($token, $config = false)
    {
        $this->start_time = microtime(true);

        $this->token = $token;
        $this->client = curl_init();
        $this->update = $this->getUpdate();
        $this->setIsVars();
        $this->setUpdateVars();
        $this->setConfig($config);

        if ($this->config['cache.driver']) {
            if (stripos($this->config['cache.driver'], 'memcache') !== false) {
                $this->cache = Cache::getMemcachedInstance($this->config['cache.host'], $this->config['cache.port']);
            }
        }

        if ($this->config['db.driver']) {
            $this->db = Database::connect($this->config);
        }

        if ($this->config['log.dir']) {
            $this->log = new Logger($this->config['log.dir']);
            if ($this->update) {
                $this->log->add($this->update, 'new.update');
            }
        }

        $this->loc = new Localization();
        $this->loc->setLang('ru');
        $this->loc->setDefaultLang($this->config['bot.default_lang']);

        $this->user = new User($this);

        $this->keyboard = new Keyboard();

        $state_data = $this->getState();
        $this->state_name = $state_data['name'];
        $this->state_data = $state_data['data'];
    }

    private function setConfig($config = false)
    {
        // если передан массив с настройками
        if ($config) {
            foreach ($config as $key => $value) {
                $this->config[$key] = $value;
            }
        }
    }

    public function getUpdate()
    {
        $update = file_get_contents('php://input');
        return !$update ? false : json_decode($update, true);
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

        if (array_key_exists('message', $update))
            $key = 'message';

        if (array_key_exists('edited_message', $update))
            $key = 'edited_message';

        if ($key == 'edited_message' || $key == 'message') {
            $this->message_id = $update[$key]['message_id'];
            $this->chat_id = $update[$key]['chat']['id'];
            $this->chat_name = trim($update[$key]['chat']['first_name'] . ' ' . $update['message']['chat']['last_name']) ?? null;
            $this->chat_username = $update[$key]['chat']['username'] ?? null;;
            $this->user_id = $update[$key]['from']['id'];
            $this->full_name = trim($update[$key]['from']['first_name'] . ' ' . $update['message']['from']['last_name']);
            $this->first_name = $update[$key]['from']['first_name'] ?? null;
            $this->last_name = $update[$key]['from']['last_name'] ?? null;
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
            $this->chat_name = trim($update['callback_query']['message']['chat']['first_name'] . ' ' . $update['callback_query']['message']['chat']['last_name']) ?? null;
            $this->chat_username = $update['callback_query']['message']['chat']['username'] ?? null;
            $this->user_id = $update['callback_query']['from']['id'];
            $this->full_name = trim($update['callback_query']['from']['first_name'] . ' ' . $update['callback_query']['from']['last_name']);
            $this->first_name = $update['callback_query']['from']['first_name'] ?? null;
            $this->last_name = $update['callback_query']['from']['last_name'] ?? null;
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
            $this->chat_name = trim($update['inline_query']['from']['first_name'] . ' ' . $update['inline_query']['from']['last_name']);
            $this->last_name = $update['inline_query']['from']['last_name'] ?? null;
            $this->username = $update['inline_query']['from']['username'] ?? null;
            $this->chat_username = $update['inline_query']['from']['username'] ?? null;
            $this->lang = $update['inline_query']['from']['language_code'];
            $this->full_name = trim($update['inline_query']['from']['first_name'] . ' ' . $update['inline_query']['from']['last_name']);
            $this->inline_query = $update['inline_query']['query'];
            $this->inline_offset = $update['inline_query']['offset'];
            return;
        }
    }

    public function setIsVars()
    {
        $this->isSticker = false;
        $this->isVoice = false;
        $this->isAnimation = false;
        $this->isDocument = false;
        $this->isAudio = false;
        $this->isPhoto = false;
        $this->isPoll = false;
        $this->isVideoNote = false;
        $this->isContact = false;
        $this->isLocation = false;
        $this->isVenue = false;
        $this->isDice = false;
        $this->isNewChatMembers = false;
        $this->isLeftChatMember = false;
        $this->isNewChatTitle = false;
        $this->isNewChatPhoto = false;
        $this->isDeleteChatPhoto = false;
        $this->isChannelChatCreated = false;
        $this->isMigrateToChatId = false;
        $this->isMigrateFromChatId = false;
        $this->isPinnedMessage = false;
        $this->isInvoice = false;
        $this->isSucessfulPayment = false;
        $this->isConnectedWebsite = false;
        $this->isPassportData = false;
        $this->isReplyMarkup = false;
        $this->isCommand = false;
        $this->isForward = false;
        $this->isSuperGroup = false;
        $this->isGroup = false;
        $this->isChannel = false;
        $this->isPrivate = false;
        $this->isCaption = false;
        $this->isEditedMessage = false;
        $this->isCallback = false;
        $this->isMessage = false;

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

    public function onCommand($callback)
    {
        if ($this->isCommand)
            call_user_func_array($callback, [$this->message]);
    }

    public function onSticker($callback)
    {
        if ($this->isSticker)
            call_user_func_array($callback, [$this->isSticker]);
    }

    public function onVoice($callback)
    {
        if ($this->isVoice)
            call_user_func_array($callback, [$this->isVoice]);
    }

    public function onDocument($callback)
    {
        if ($this->isDocument)
            call_user_func_array($callback, [$this->isDocument]);
    }

    public function onAnimation($callback)
    {
        if ($this->isAnimation)
            call_user_func_array($callback, [$this->isAnimation]);
    }

    public function onPhoto($callback)
    {
        if ($this->isPhoto)
            call_user_func_array($callback, [$this->isPhoto]);
    }

    public function onAudio($callback)
    {
        if ($this->isAudio)
            call_user_func_array($callback, [$this->isAudio]);
    }

    public function onVideoNote($callback)
    {
        if ($this->isVideoNote)
            call_user_func_array($callback, [$this->isVideoNote]);
    }

    public function onContact($callback)
    {
        if ($this->isContact)
            call_user_func_array($callback, [$this->isContact]);
    }

    public function onLocation($callback)
    {
        if ($this->isLocation)
            call_user_func_array($callback, [$this->isLocation]);
    }

    public function onPoll($callback)
    {
        if ($this->isPoll)
            call_user_func_array($callback, [$this->isPoll]);
    }

    public function onDice($callback)
    {
        if ($this->isDice)
            call_user_func_array($callback, [$this->isDice['emoji'], $this->isDice['value']]);
    }

    public function onInline($callback)
    {
        if ($this->isInline)
            call_user_func($callback);
    }

    public function onCallback($callback)
    {
        if ($this->isCallback)
            call_user_func($callback);
    }

    public function onMessage($callback)
    {
        if ($this->isMessage)
            call_user_func($callback);
    }

    public function onEditedMessage($callback)
    {
        if ($this->isEditedMessage)
            call_user_func($callback);
    }

    public function onVideo($callback)
    {
        if ($this->isVideo)
            call_user_func_array($callback, [$this->isVideo]);
    }

    public function fromPrivate($callback)
    {
        if ($this->isPrivate)
            call_user_func($callback);
    }

    public function fromChannel($callback)
    {
        if ($this->isChannel)
            call_user_func($callback);
    }

    public function fromGroup($callback)
    {
        if ($this->isChannel)
            call_user_func($callback);
    }

    public function fromSuperGroup($callback)
    {
        if ($this->isChannel)
            call_user_func($callback);
    }

    public function setWebhook($url = false)
    {
        if (!$url && array_key_exists('bot.url', $this->config))
            $url = $this->config['bot.url'];


        return $url ? json_decode(file_get_contents($this->base_url . $this->token . '/setWebhook?url=' . $url), true) : false;
    }

    public function deleteWebhook($token = false)
    {
        if (!$token && array_key_exists('bot.token', $this->config))
            $token = $this->config['bot.token'];

        return json_decode(file_get_contents($this->base_url . $token . '/deleteWebhook'), true);
    }

    public function request($method, $parameters, $is_file = false)
    {
        $url = $this->base_url . $this->token . '/' . $method;

        if ($is_file) {
            $headers = 'Content-Type: multipart/form-data';
        } else {
            $headers = 'Content-Type: application/json';
            $parameters = json_encode($parameters);
        }

        curl_setopt($this->client, CURLOPT_URL, $url);
        curl_setopt($this->client, CURLINFO_HEADER_OUT, true);
        curl_setopt($this->client, CURLOPT_HTTPHEADER, [$headers]);
        curl_setopt($this->client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->client, CURLOPT_POST, true);
        curl_setopt($this->client, CURLOPT_POSTFIELDS, $parameters);

        $response = curl_exec($this->client);

        return json_decode($response, true);
    }

    public function answerInlineQuery($results = [], $scopes = [])
    {
        $parameters = [
            'inline_query_id' => $this->inline_id,
            'results' => json_encode($results),
        ];

        $parameters = array_merge($parameters, $scopes);

        return $this->request('answerInlineQuery', $parameters);
    }

    public function sendAction($chat_id, $action)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'action' => $action,
        ];

        $this->request('sendChatAction', $parameters);

        return $this;
    }

    public function isActive($chat_id, $action = 'typing')
    {
        $parameters = [
            'chat_id' => $chat_id,
            'action' => $action,
        ];

        return $this->request('sendChatAction', $parameters)['ok'];
    }

    public function editMessageText($message_id, $text = '', $keyboard = false)
    {
        $parameters = [
            'chat_id' => $this->chat_id,
            'text' => $text,
            'parse_mode' => $this->config['tg.parse_mode'],
            'message_id' => $message_id,
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('editMessageText', $parameters);
    }

    public function editMessageCaption($message_id, $text = '', $keyboard = false)
    {
        $parameters = [
            'chat_id' => $this->chat_id,
            'caption' => $text,
            'parse_mode' => $this->config['tg.parse_mode'],
            'message_id'   => $message_id,
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('editMessageText', $parameters);
    }

    public function editMessageReplyMarkup($message_id, $keyboard = false)
    {
        $parameters = [
            'chat_id' => $this->chat_id,
            'caption' => $text,
            'message_id'   => $message_id,
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('editMessageReplyMarkup', $parameters);
    }

    public function sendMessage($chat_id, $text = '', $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => $this->config['tg.parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendMessage', $parameters);
    }

    public function sendDice($chat_id, $emoji = '', $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'emoji' => $emoji,
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendMessage', $parameters);
    }

    public function sendReply($chat_id, $message_id, $text = '', $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'text' => $text,
            'reply_to_message_id' => $message_id,
            'parse_mode' => $this->config['tg.parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendMessage', $parameters);
    }

    public function sendDocument($chat_id, $file, $text = '', $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'caption' => $text,
            'document' => $file,
            'parse_mode' => $this->config['tg.parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendDocument', $parameters, $is_file = true);
    }

    public function sendPhoto($chat_id, $file, $text = '', $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'caption' => $text,
            'photo' => $file,
            'parse_mode' => $this->config['tg.parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendPhoto', $parameters, $is_file = true);
    }

    public function sendVoice($chat_id, $file, $text = '', $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'caption' => $text,
            'voice' => $file,
            'parse_mode' => $this->config['tg.parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendVoice', $parameters, $is_file = true);
    }

    public function sendAudio($chat_id, $file, $text = '', $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'caption' => $text,
            'audio' => $file,
            'parse_mode' => $this->config['tg.parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendAudio', $parameters, $is_file = true);
    }

    public function sendVideo($chat_id, $file, $text = '', $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'caption' => $text,
            'video' => $file,
            'parse_mode' => $this->config['tg.parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendVideo', $parameters, $is_file = true);
    }

    public function sendAnimation($chat_id, $file, $text = '', $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'caption' => $text,
            'animation' => $file,
            'parse_mode' => $this->config['tg.parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendAnimation', $parameters, $is_file = true);
    }

    public function sendVideoNote($chat_id, $file, $text = '', $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'caption' => $text,
            'video_note' => $file,
            'parse_mode' => $this->config['tg.parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendVideoNote', $parameters, $is_file = true);
    }

    public function sendJson()
    {
        $parameters = [
            'chat_id' => $this->chat_id,
            'text' => '`'.json_encode($this->update, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).'`',
            'parse_mode' => $this->config['tg.parse_mode'],
        ];

        return $this->request('sendMessage', $parameters, $is_file = false);
    }

    public function say($text, $keyboard = false)
    {
        $parameters = [
            'chat_id' => $this->chat_id,
            'text' => $this->shuffle($text),
            'parse_mode' => $this->config['tg.parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendMessage', $parameters);
    }

    public function reply($text, $keyboard = false)
    {
        $parameters = [
            'chat_id' => $this->chat_id,
            'text' => $this->shuffle($text),
            'reply_to_message_id' => $this->message_id,
            'parse_mode' => $this->config['tg.parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendMessage', $parameters);
    }

    /**
     * typing, upload_photo, record_video, upload_video, record_audio,
     * upload_audio, upload_document, find_location, record_video_note,
     * upload_video_note
     */
    public function action($action)
    {
        $parameters = [
            'chat_id' => $this->chat_id,
            'action' => $action,
        ];

        $this->request('sendChatAction', $parameters);

        return $this;
    }

    public function notify($text, $alert = false)
    {
        if (!$this->isCallback)
            return;

        $parameters = [
            'callback_query_id' => $this->update['callback_query']['id'],
            'text' => $text,
        ];

        if ($alert)
            $parameters['show_alert'] = true;

        $this->request('answerCallbackQuery', $parameters);
    }

    public function dice($emoji = '', $keyboard = false)
    {
        $parameters = [
            'chat_id' => $this->chat_id,
            'emoji' => $emoji,
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendMessage', $parameters);
    }

    public function hear($messages = null, $callback = null)
    {
        if (!$this->checkState())
            return;

        if (!$messages || !$callback)
            return;

        if (!is_array($messages))
            $messages = [$messages];

        foreach ($messages as $message) {
            $this->messages[$message]['callback'] = $callback;
            $this->messages[$message]['data'] = $message;
            $this->messages[$message]['mode'] = null;
        }
    }

    public function command($commands = null, $callback = null)
    {
        if (!$this->checkState())
            return;

        if (!$commands || !$callback)
            return;

        if (!is_array($commands))
            $commands = [$commands];

        foreach ($commands as $command) {
            $this->commands[$command]['callback'] = $callback;
            $this->commands[$command]['data'] = $command;
            $this->commands[$command]['mode'] = null;
        }
    }

    public function callback($actions = null, $callback = null)
    {
        if (!$this->checkState())
            return;

        if (!$actions || !$callback)
            return;

        if (!is_array($actions))
            $actions = [$actions];

        foreach ($actions as $action) {
            $this->actions[$action]['callback'] = $callback;
            $this->actions[$action]['data'] = $action;
            $this->actions[$action]['mode'] = null;
        }
    }

    public function run($update = null)
    {
        // debug
        if ($update) {
            $this->update = json_decode($update, true);
            $this->setIsVars();
            $this->setUpdateVars();
        }

        echo ('ok');

        if ($this->isCommand) {
            foreach ($this->commands as $key => $command) {
                if ($this->isRegEx($command['data'])) {
                    preg_match($command['data'], $this->message, $res);
                    if (sizeof($res) > 0) {
                        return call_user_func_array($command['callback'], is_string($command['callback']) ? $this : []);
                    }
                }
                if ($command['data'] !== $this->message)
                    continue;
                return call_user_func_array($command['callback'], is_string($command['callback']) ? $this : []);
            }

            if (array_key_exists('{default}', $this->commands)) {
                $callback = $this->commands['{default}']['callback'];
                return call_user_func_array($callback, is_string($callback) ? $this : []);
            }

            return false;
        }

        if ($this->isMessage || $this->isEditedMessage) {
            foreach ($this->messages as $key => $message) {
                if ($this->isRegEx($message['data'])) {
                    preg_match($message['data'], $this->message, $res);
                    if (sizeof($res) > 0) {
                        return call_user_func_array($message['callback'], is_string($message['callback']) ? $this : []);
                    }
                }
                if ($message['data'] !== $this->message)
                    continue;
                return call_user_func_array($message['callback'], is_string($message['callback']) ? $this : []);
            }

            if (array_key_exists('{default}', $this->messages)) {
                $callback = $this->messages['{default}']['callback'];
                return call_user_func_array($callback, is_string($callback) ? $this : []);
            }

            return false;
        }

        if ($this->isCallback) {
            foreach ($this->actions as $key => $action) {
                if ($this->isRegEx($action['data'])) {
                    preg_match($action['data'], $this->callback_data, $res);
                    if (sizeof($res) > 0) {
                        return call_user_func_array($action['callback'], is_string($action['callback']) ? $this : []);
                    }
                }
                if ($action['data'] !== $this->callback_data)
                    continue;
                return call_user_func_array($action['callback'], is_string($action['callback']) ? $this : []);
            }

            if (array_key_exists('{default}', $this->actions)) {
                $callback = $this->actions['{default}']['callback'];
                return call_user_func_array($callback, is_string($callback) ? $this : []);
            }

            return false;
        }
    }

    public function getFile($file_id, $local_file_path = false)
    {
        $parameters = [
            'file_id' => $file_id,
        ];

        if ($local_file_path) {
            $result = $this->request('getFile', $parameters);
            if (!$result['ok'])
                return false;
            return $this->saveFile($result['result']['file_path'], $local_file_path);
        }

        return $this->request('getFile', $parameters);
    }

    public function saveFile($file_path, $local_file_path = false)
    {
        if (!$local_file_path)
            return false;

        $extension = stripos(basename($file_path), '.') !== false ? end(explode('.', basename($file_path))) : '';
        $local_file_path = str_ireplace(['{ext}', '{extension}', '{file_ext}'], $extension, $local_file_path);
        $local_file_path = str_ireplace(['{base}', '{basename}', '{base_name}', '{name}'], basename($file_path), $local_file_path);
        $local_file_path = str_ireplace(['{time}'], time(), $local_file_path);
        $local_file_path = str_ireplace(['{md5}'], md5(time().mt_rand()), $local_file_path);
        $local_file_path = str_ireplace(['{rand}','{random}','{rand_name}','{random_name}'], md5(time().mt_rand()) . ".$extension", $local_file_path);
        $local_file_path = str_ireplace(['{base}', '{basename}', '{base_name}', '{name}'], basename($file_path), $local_file_path);

        file_put_contents($local_file_path, file_get_contents("https://api.telegram.org/file/bot{$this->token}/{$file_path}"));

        return basename($local_file_path);
    }

    public function deleteMessage($chat_id, $message_id)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
        ];

        return $this->request('deleteMessage', $parameters, $is_file = false);
    }

    public function state($state_name)
    {
        $this->needState = $state_name;
        return $this;
    }

    public function checkState()
    {
        if ($this->needState !== null && $this->needState !== $this->state_name) {
            $this->needState = null;
            return false;
        }

        $this->needState = null;
        return true;
    }

    public function setState($state_name, $state_data = null)
    {
        $this->state_name = $state_name;
        $this->state_data = $state_data;

        $state_driver = mb_strtolower($this->config['state.driver']);

        if ($state_driver == 'cache' && $this->cache) { // cache
            $id = $this->user_id . '_state';
            return $this->cache->set($id, [
                'name' => $state_name,
                'data' => $state_data,
            ]);
        } else if ($state_driver == 'db' && $this->db) { // db
            return $this->user->updateById($this->user_id, [
                'state_name' => $state_name,
                'state_data' => $state_data,
            ]);
        }
    }

    public function getState()
    {
        $state_driver = mb_strtolower($this->config['state.driver']);

        if ($state_driver == 'cache' && $this->cache) { // cache
            $id = $this->user_id . '_state';
            return $this->cache->get($id);
        } else if ($state_driver == 'db' && $this->db) { // db
            return [
                'name' => $this->user->state_name,
                'data' => $this->user->state_data,
            ];
        }
    }

    public function clearState()
    {
        $this->state_name = null;
        $this->state_data = null;

        $state_driver = mb_strtolower($this->config['state.driver']);

        if ($state_driver == 'cache' && $this->cache) { // cache
            $id = $this->user_id . '_state';
            return $this->cache->delete($id);
        } else if ($state_driver == 'db' && $this->db) { // db
            $update = [];
            $update['state_name'] = null;
            $update['state_data'] = null;
            return $this->user->updateById($this->user_id, $update);
        }
    }

    public function clearStateById($user_id)
    {
        $state_driver = mb_strtolower($this->config['state.driver']);

        if ($state_driver == 'cache' && $this->cache) { // cache
            $id = $user_id . '_state';
            return $this->cache->delete($id);
        } else if ($state_driver == 'db' && $this->db) { // db
            $update = [];
            $update['state_name'] = null;
            $update['state_data'] = null;
            return $this->user->updateById($user_id, $update);
        }
    }

    public function isSpam($callback)
    {
        if ($this->user->isSpam)
            call_user_func_array($callback, [$this->user->isSpam]);
    }

    public function isNewVersion($callback)
    {
        if ($this->user->isNewVersion)
            call_user_func_array($callback, [$this->config['bot.version']]);

    }

    public function isBanned($callback)
    {
        if ($this->user->isBanned)
            call_user_func_array($callback, [$this->user->data['ban_comment'], $this->user->data['ban_end']]);
    }

    public function isAdmin($callback)
    {
        if ($this->user->isAdmin)
            call_user_func($callback);
    }

    public function isNewUser($callback)
    {
        if ($this->user->isNewUser)
            call_user_func($callback);
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

    public function loc($key, $params = null)
    {
        return $this->loc->get($key, $params);
    }

    public function shuffle($message)
    {
        preg_match_all('/{{(.+?)}}/mi', $message, $sentences);

        if (sizeof($sentences[1]) == 0)
            return $message;

        foreach($sentences[1] as $words) {
            $words_array = explode('|', $words);
            $select = $words_array[array_rand($words_array)];
            $message = str_ireplace('{{' . $words . '}}', $select, $message);
        }

        return $message;
    }

    public function isRegEx($string) {
        return @preg_match($string, '') !== FALSE;
    }

    public function time($n = 2)
    {
        return round(microtime(true) - $this->start_time, $n);
    }

    public function random($arr)
    {
        shuffle($arr);
        return $arr[array_rand($arr)];
    }

    public function randomReplica($key)
    {
        shuffle($this->replics[$key]);
        return $this->replics[$key][array_rand($this->replics[$key])];
    }

    public function randomEmoji($key)
    {
        shuffle($this->emojis[$key]);
        return $this->emojis[$key][array_rand($this->emojis[$key])];
    }

    public function register($var, $data)
    {
        $this->$var = $data;
    }
}
