<?php

namespace Aethletic\Telegram\Core;

use Aethletic\Telegram\Core\User;
use Aethletic\Telegram\Core\Logger;
use Aethletic\Telegram\Core\File;
use Aethletic\Telegram\Core\Keyboard;
use Aethletic\Telegram\Core\DB;

require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/File.php';
require_once __DIR__ . '/Keyboard.php';
require_once __DIR__ . '/DB.php';

class Bot
{
    /**
     * Base API url
     * @var string
     */
    protected $base_url = 'https://api.telegram.org/bot';

    /**
     * Bot token
     * @var string
     */
    protected $token;

    /**
     * Массив с событиями и реакциями
     * @var array
     */
    protected $events = [];

    /**
     * Массив с данными из Телеграма
     * @var array
     */
    public $update;

    /**
     * Массив с настройками бота
     * @var array
     */
    public $config = [
        'log.enable' => false,
        'log.dir' => '.',
        'parse_mode' => 'markdown',
        'admin.list' => [],
        'admin.password' => 'admin',
        'db.driver' => false,
        'spam.timeout' => 1,
        'case_sensitive' => false,
    ];

    /**
     * Массив с клавиатурами
     * @var array
     */
    protected $keyboards = [];

    /**
     * Массив с колбэками
     * @var array
     */
    protected $callbacks = [];

    /**
     * Массив с командами
     * @var array
     */
    protected $commands = [];

    /**
     * Массив с emojis
     * @var array
     */
    protected $emojis = [];

    /**
     * Массив с репликами
     * @var array
     */
    protected $replics = [];

    /**
     * Логгер
     * @var Logger
     */
    public $log;

    /**
     * Объект юзера
     * @var User
     */
    public $user;

    /**
     * Объект базы данных
     * @var DB
     */
    public $db;

    /**
     * Временный стейт для установки события
     * @var string
     */
    protected $state = null;

    public function __construct($token = '', $config = [])
    {
        $this->token = $token;

        foreach ($config as $key => $value) {
            $this->config[$key] = $value;
        }

        if ($this->config['db.driver'])
            $this->db = DB::connect($this->config);


        $this->setUpdate();

        if ($this->update)
            $this->user = new User($this);

        if ($this->config['log.enable']) {
            $this->log = new Logger($this->config['log.dir']);
            if ($this->update)
                $this->log->set($this->update, 'new.update');
        }
    }

    protected function setUpdate()
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

        $post_data = file_get_contents('php://input');

        $this->update = !$post_data ? false : json_decode($post_data, true);

        // $this->update = json_decode('{
        //         "update_id": 123455,
        //         "message": {
        //             "message_id": 1337,
        //             "from": {
        //                 "id": 11111,
        //                 "is_bot": false,
        //                 "first_name": "Павел Дуров",
        //                 "username": "durov",
        //                 "language_code": "ru"
        //             },
        //             "chat": {
        //                 "id": 11111,
        //                 "title": "Чат какой-то",
        //                 "username": "durov_chat",
        //                 "type": "supergroup"
        //             },
        //             "date": 15223112712,
        //             "text": "сообщение"
        //         }
        //     }', true);

        if (!$this->update)
            return;

        if (array_key_exists('callback_query', $this->update)) {
            $this->isCallback = $this->update['callback_query'];
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

        if (array_key_exists('dice', $this->update[$key]))
            $this->isDice = $this->update[$key]['dice'];

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

    public function state($state_name)
    {
        $this->state = $state_name;
        return $this;
    }

    protected function checkState()
    {
        if ($this->state !== null && $this->state !== $this->user->state_name) {
            $this->state = null;
            return false;
        }

        $this->state = null;
        return true;
    }

    public function hear($message, $callback)
    {
        if (!$this->checkState())
            return;

        if (!is_array($message))
            $message = [$message];

        foreach ($message as $event) {
            if (!$this->config['case_sensitive'])
                $event = mb_strtolower($event);

            $this->events[$event]['callback'] = $callback;
            $this->events[$event]['mode'] = '';
        }
    }

    public function command($command, $callback)
    {
        if (!$this->checkState())
            return;

        if (!is_array($command))
            $command = [$command];

        foreach ($command as $event) {
            if (!$this->config['case_sensitive'])
                $event = mb_strtolower($event);

            $this->commands[$event]['callback'] = $callback;
            $this->commands[$event]['mode'] = '';
        }
    }

    public function callback($callback_data = [], $callback)
    {
        if (!$this->checkState())
            return;

        if (!is_array($callback_data))
            $callback_data = [$callback_data];

        foreach ($callback_data as $data) {
            if (!$this->config['case_sensitive'])
                $data = mb_strtolower($data);

            $this->callbacks[$data]['callback'] = $callback;
            $this->callbacks[$data]['mode'] = '';
        }
    }

    public function isSpam($callback)
    {
        if ($this->user->spam)
            $callback($time = $this->user->spam);
    }

    public function isUpdated($callback)
    {
        if ($this->user->newVersion)
            $callback($version = $this->config['bot.version']);
    }

    public function say($text, $keyboard = false)
    {
        $parameters = [
            'chat_id' => $this->user->chat_id,
            'text' => $this->choose($text),
            'parse_mode' => $this->config['parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendMessage', $parameters);
    }

    public function reply($text, $keyboard = false)
    {
        $parameters = [
            'chat_id' => $this->user->chat_id,
            'text' => $this->choose($text),
            'reply_to_message_id' => $this->user->message_id,
            'parse_mode' => $this->config['parse_mode'],
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
            'chat_id' => $this->user->chat_id,
            'action' => $action,
        ];

        $this->request('sendChatAction', $parameters);

        return $this;
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

    public function isActive($chat_id)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'action' => 'typing',
        ];

        return $this->request('sendChatAction', $parameters)['ok'];
    }

    public function editMessageText($message_id, $text, $keyboard = false)
    {
        $parameters = [
            'chat_id' => $this->user->chat_id,
            'text' => $text,
            'parse_mode' => $this->config['parse_mode'],
            'message_id'   => $message_id,
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('editMessageText', $parameters);
    }

    public function editMessageCaption($message_id, $text, $keyboard = false)
    {
        $parameters = [
            'chat_id' => $this->user->chat_id,
            'caption' => $text,
            'parse_mode' => $this->config['parse_mode'],
            'message_id'   => $message_id,
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('editMessageText', $parameters);
    }

    public function editMessageReplyMarkup($message_id, $keyboard = false)
    {
        $parameters = [
            'chat_id' => $this->user->chat_id,
            'caption' => $text,
            'message_id'   => $message_id,
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('editMessageReplyMarkup', $parameters);
    }

    public function sendMessage($chat_id, $text, $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => $this->config['parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendMessage', $parameters);
    }

    public function sendReply($chat_id, $message_id, $text, $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'text' => $text,
            'reply_to_message_id' => $message_id,
            'parse_mode' => $this->config['parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendMessage', $parameters);
    }

    public function sendDocument($chat_id, $file, $text, $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'caption' => $text,
            'document' => $file,
            'parse_mode' => $this->config['parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendDocument', $parameters, $is_file = true);
    }

    public function sendPhoto($chat_id, $file, $text, $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'caption' => $text,
            'photo' => $file,
            'parse_mode' => $this->config['parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendPhoto', $parameters, $is_file = true);
    }

    public function sendVoice($chat_id, $file, $text, $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'caption' => $text,
            'voice' => $file,
            'parse_mode' => $this->config['parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendVoice', $parameters, $is_file = true);
    }

    public function sendAudio($chat_id, $file, $text, $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'caption' => $text,
            'audio' => $file,
            'parse_mode' => $this->config['parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendAudio', $parameters, $is_file = true);
    }

    public function sendVideo($chat_id, $file, $text, $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'caption' => $text,
            'video' => $file,
            'parse_mode' => $this->config['parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendVideo', $parameters, $is_file = true);
    }

    public function sendAnimation($chat_id, $file, $text, $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'caption' => $text,
            'animation' => $file,
            'parse_mode' => $this->config['parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendAnimation', $parameters, $is_file = true);
    }

    public function sendVideoNote($chat_id, $file, $text, $keyboard = false)
    {
        $parameters = [
            'chat_id' => $chat_id,
            'caption' => $text,
            'video_note' => $file,
            'parse_mode' => $this->config['parse_mode'],
        ];

        if ($keyboard)
            $parameters['reply_markup'] = $keyboard;

        return $this->request('sendVideoNote', $parameters, $is_file = true);
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

        $local_file_path = str_ireplace(['{base}', '{basename}', '{base_name}', '{name}'], basename($file_path), $local_file_path);

        return file_put_contents($local_file_path, file_get_contents("https://api.telegram.org/file/bot{$this->token}/{$file_path}"));
    }

    public function run()
    {
        if ($this->update == '')
            die("rick: no update, die\n");

        // command
        if ($this->isCommand) {
            $command_name = '{default}';
            foreach ($this->commands as $command => $value) {
                if (stripos($this->user->message, $command) !== false) {
                    $command_name = $command;
                    break;
                }
            }
            $callback = $this->commands[$command_name]['callback'];
            if (is_string($callback) === false)
                return $callback();
            else
                return $callback($this);
        }

        // callback
        if ($this->isCallback) {
            $callback_name = '{default}';
            foreach ($this->callbacks as $cb => $value) {
                if (stripos($this->update['callback_query']['data'], $cb) !== false) {
                    $callback_name = $cb;
                    break;
                }
            }
            $callback = $this->callbacks[$callback_name]['callback'];
            if (is_string($callback) === false)
                return $callback();
            else
                return $callback($this);
        }

        // messages
        $arr_name = 'events';

        $action = !$this->config['case_sensitive'] ? mb_strtolower($this->user->message) : $this->user->message;

        if ($this->isCallback) {
            $arr_name = 'callbacks';
            $action = !$this->config['case_sensitive'] ? mb_strtolower($this->update['callback_query']['data']) : $this->update['callback_query']['data'];
        }

        if (sizeof($this->$arr_name) == 0)
            die("rick: no have events/callbacks, die\n");

        echo 'ok';

        if (!array_key_exists($action, $this->$arr_name))
            $action = '{default}';

        $callback = $this->$arr_name[$action]['callback'];

        if (is_string($callback) === false)
            return $callback();
        else
            return $callback($this);
    }

    public function request($method, $parameters = null, $is_file = false)
    {
        $url = $this->base_url . $this->token . '/' . $method;

        if ($is_file) {
            $headers = 'Content-Type: multipart/form-data';
        } else {
            $headers = 'Content-Type: application/json';
            $parameters = json_encode($parameters);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$headers]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function setWebhook($url = false)
    {
        return $url ? json_decode(file_get_contents($this->base_url . $this->token . '/setWebhook?url=' . $url), true) : false;
    }

    public function choose($message)
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

    public function parseCommand($divider = '_')
    {
        $arr = explode($divider, $this->user->message);
        $arr = array_map('trim', $arr);
        $arr = array_filter($arr);
        return array_values($arr);
    }

    public function parseCallback($divider = '_')
    {
        $arr = explode($divider, $this->update['callback_query']['data']);
        $arr = array_map('trim', $arr);
        $arr = array_filter($arr);
        return array_values($arr);
    }

    public function randomEmoji($emoji)
    {
        return $this->random($this->emojis[$emoji]);
    }

    public function randomReplica($replica)
    {
        return $this->random($this->replics[$replica]);
    }

    public function random($arr)
    {
        shuffle($arr);
        return $arr[array_rand($arr)];
    }

    public function register($var, $data)
    {
        $this->$var = $data;
    }
}
