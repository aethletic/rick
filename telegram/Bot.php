<?php

namespace Aethletic\Telegram;

require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/File.php';

class Bot
{
    /**
     * Токен бота
     */
    protected $token;

    /**
     * Базовый адрес API
     */
    protected $base_url = 'https://api.telegram.org/bot';

    /**
     * Массив с действиями
     */
    protected $events = [];

    /**
     * Массив с колбэками для инлайн-кнопок
     */
    protected $callbacks = [];

    /**
     * Массив с клавиатурами
     */
    protected $keyboards = [];

    /**
     * Массив с данными о пользователе
     */
    protected $user = [];

    /**
     * Массив с данными о новом сообщении
     */
    protected $updates = [];

    /**
     * Объект Logger
     */
    protected $logger;

    /**
     * Массив с настрйоками бота
     */
    protected $config = [
        'rick.logs' => false,
        'rick.logs_dir' => '',
        'rick.default_parse_mode' => 'markdown',
    ];

    public function __construct($token = null, $config = [])
    {
        foreach ($config as $key => $value) {
            $this->config[$key] = $value;
        }

        $this->logger = new Logger($this->config['rick.logs_dir']);

        $this->token = $token;

        $this->setData();
    }

    public function execute($method, $params = false, $isFile = false)
    {
        $url = $this->base_url . $this->token . '/' . $method;

        if ($isFile) {
            $contentType = 'Content-Type: multipart/form-data';
        } else {
            $contentType = 'Content-Type: application/json';
            $params = json_encode($params);
        }

        return Bot::request([
            'method'    => 'POST',
            'url'       => $url,
            'params'    => $params,
            'header'    => $contentType,
            'return'    => 'array',
        ]);
    }

    public function request($data)
    {
        $ch = curl_init($data['url']);

        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$data['header']]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (mb_strtolower($data['method']) == 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data['params']);
        }

        $response = curl_exec($ch);

        curl_close($ch);

        return mb_strtolower($data['return']) == 'array' ? json_decode($response, true) : $response;
    }

    public function hear($events = [], $callback)
    {
        if (!is_array($events)) {
            $events = [$events];
        }

        foreach ($events as $event) {
            $this->events[$event] = $callback;
        }

        return $this;
    }

    // реакция на колбэк_дату из инлайн кнопок
    public function callback($callback_data = [], $callback)
    {
        if (!is_array($callback_data)) {
            $callback_data = [$callback_data];
        }

        foreach ($callback_data as $c) {
            $this->callbacks[$c] = $callback;
        }

        return $this;
    }

    public function say($message, $keyboard = false)
    {
        $params = [
            'chat_id'       => $this->user['chat_id'],
            'text'          => $this->randomMessage($message),
            'parse_mode'    => $this->config['rick.default_parse_mode'],
        ];

        if ($keyboard) {
            $params['reply_markup'] = $keyboard;
        }

        return $this->execute('sendMessage', $params);
    }

    public function reply($message, $keyboard = false)
    {
        $params = [
            'chat_id'               => $this->user['chat_id'],
            'text'                  => $this->randomMessage($message),
            'parse_mode'            => $this->config['rick.default_parse_mode'],
            'reply_to_message_id'   => $this->updates['message']['message_id'],
        ];

        if ($keyboard) {
            $params['reply_markup'] = $keyboard;
        }

        return $this->execute('sendMessage', $params);
    }

    public function edit($message_id, $message, $keyboard = false)
    {
        $params = [
            'chat_id'      => $this->user['chat_id'],
            'text'         => $this->randomMessage($message),
            'parse_mode'   => $this->config['rick.default_parse_mode'],
            'message_id'   => $message_id,
        ];

        if ($keyboard) {
            $params['reply_markup'] = $keyboard;
        }

        return $this->execute('editMessageText', $params);
    }

    public function sendMessage($chat_id, $message, $keyboard = null)
    {
        $params = [
            'chat_id'       => $chat_id,
            'text'          => $message,
            'parse_mode'    => $this->config['rick.default_parse_mode'],
        ];

        if ($keyboard) {
            $params['reply_markup'] = $keyboard;
        }

        return $this->execute('sendMessage', $params);
    }


    public function randomMessage($message)
    {
        preg_match_all('/{{(.+?)}}/mi', $message, $sentences);

        foreach($sentences[1] as $words) {
            $words_array = explode('|', $words);
            $select = $words_array[array_rand($words_array)];
            $message = str_ireplace('{{' . $words . '}}', $select, $message);
        }

        return $message;
    }

    public function getUpdates()
    {
        return $this->updates;
    }

    public function setData()
    {
        $updates = json_decode(file_get_contents('php://input'), true);

        if (!$this->isCallback()) {
            $this->user['chat_id'] = $updates['message']['chat']['id'];
            $this->user['chat_name'] = trim($updates['message']['chat']['first_name'] . ' ' . $updates['message']['chat']['last_name']);
            $this->user['chat_username'] = $updates['message']['chat']['username'];
            $this->user['id'] = $updates['message']['from']['id'];
            $this->user['fullname'] = trim($updates['message']['from']['first_name'] . ' ' . $updates['message']['from']['last_name']);
            $this->user['firstname'] = $updates['message']['from']['first_name'];
            $this->user['lastname'] = $updates['message']['from']['last_name'];
            $this->user['username'] = $updates['message']['from']['username'];
            $this->user['lang'] = $updates['message']['from']['language_code'];
            $this->user['message'] = $updates['message']['text'];
        } else {
            $this->user['chat_id'] = $updates['callback_query']['message']['chat']['id'];
            $this->user['chat_name'] = trim($updates['callback_query']['message']['chat']['first_name'] . ' ' . $updates['callback_query']['message']['chat']['last_name']);
            $this->user['chat_username'] = $updates['callback_query']['message']['chat']['username'];
            $this->user['id'] = $updates['callback_query']['from']['id'];
            $this->user['fullname'] = trim($updates['callback_query']['from']['first_name'] . ' ' . $updates['callback_query']['message']['from']['last_name']);
            $this->user['firstname'] = $updates['callback_query']['from']['first_name'];
            $this->user['lastname'] = $updates['callback_query']['from']['last_name'];
            $this->user['username'] = $updates['callback_query']['from']['username'];
            $this->user['lang'] = $updates['callback_query']['from']['language_code'];
            $this->user['message'] = $updates['callback_query']['message']['text'];
        }

        $this->updates = $updates;
    }

    public function register($variable, $value)
    {
        $this->$variable = $value;
    }

    public function keyboard($keyboard, $resize = true, $one_time = false)
    {
        if (!is_array($keyboard)) {
            $keyboard = $this->keyboards[$keyboard];
        }

        $markup = [
            'keyboard' => $keyboard,
            'resize_keyboard' => $resize,
            'one_time_keyboard' => $one_time
        ];

        if ($keyboard == false) {
            $markup = [
                'hide_keyboard' => true,
            ];
        }

        return json_encode($markup);
    }

    public function getUser()
    {
        return $this->user;
    }

    // any type
    // 50 MB
    public function sendDocument($chat_id, $file, $message = '', $keyboard = false)
    {
        $params = [
            'chat_id' => $chat_id,
            'document' => $file,
            'caption' => $message,
            'parse_mode' => $this->config['rick.default_parse_mode'],
        ];

        if ($keyboard) {
            $params['reply_markup'] = $keyboard;
        }

        $this->execute('sendDocument', $params, true);
    }

    public function sendPhoto($chat_id, $file, $message = '', $keyboard = false)
    {
        $params = [
            'chat_id' => $chat_id,
            'photo' => $file,
            'caption' => $message,
            'parse_mode' => $this->config['rick.default_parse_mode'],
        ];

        if ($keyboard) {
            $params['reply_markup'] = $keyboard;
        }

        $this->execute('sendPhoto', $params, true);
    }

    // .OGG file encoded with OPUS (other formats may be sent as Audio or Document)
    // 50 MB
    public function sendVoice($chat_id, $file, $message = '', $keyboard = false)
    {
        $params = [
            'chat_id' => $chat_id,
            'voice' => $file,
            'caption' => $message,
            'parse_mode' => $this->config['rick.default_parse_mode'],
        ];

        if ($keyboard) {
            $params['reply_markup'] = $keyboard;
        }

        $this->execute('sendVoice', $params, true);
    }

    // .MP3 or .M4A format
    // 50 MB
    public function sendAudio($chat_id, $file, $message = '', $keyboard = false)
    {
        $params = [
            'chat_id' => $chat_id,
            'audio' => $file,
            'caption' => $message,
            'parse_mode' => $this->config['rick.default_parse_mode'],
        ];

        if ($keyboard) {
            $params['reply_markup'] = $keyboard;
        }

        $this->execute('sendAudio', $params, true);
    }

    // mp4 videos (other formats may be sent as Document)
    // 50 MB
    public function sendVideo($chat_id, $file, $message = '', $keyboard = false)
    {
        $params = [
            'chat_id' => $chat_id,
            'video' => $file,
            'caption' => $message,
            'parse_mode' => $this->config['rick.default_parse_mode'],
        ];

        if ($keyboard) {
            $params['reply_markup'] = $keyboard;
        }

        $this->execute('sendVideo', $params, true);
    }

    // GIF or H.264/MPEG-4 AVC video without sound
    // 50 MB
    public function sendAnimation($chat_id, $file, $message = '', $keyboard = false)
    {
        $params = [
            'chat_id' => $chat_id,
            'animation' => $file,
            'caption' => $message,
            'parse_mode' => $this->config['rick.default_parse_mode'],
        ];

        if ($keyboard) {
            $params['reply_markup'] = $keyboard;
        }

        $this->execute('sendAnimation', $params, true);
    }

    /**
     * Доступные варианты действий:
     * typing, upload_photo, record_video, upload_video, record_audio,
     * upload_audio, upload_document, find_location, record_video_note,
     * upload_video_note
     */
    public function action($action)
    {
        $params = [
            'chat_id' => $this->user['chat_id'],
            'action' => $action,
        ];

        $this->execute('sendChatAction', $params);

        return $this;
    }

    public function sendAction($chat_id, $action)
    {
        $params = [
            'chat_id' => $chat_id,
            'action' => $action,
        ];

        $this->execute('sendChatAction', $params);

        return $this;
    }

    public function inline($inline)
    {
        if (!is_array($inline)) {
            $inline = $this->keyboards[$inline];
        }

        return json_encode(['inline_keyboard' => $inline]);
    }

    public function notify($message)
    {
        if (!$this->isCallback()) {
            return false;
        }

        $this->execute('answerCallbackQuery', [
            'callback_query_id' => $this->updates['callback_query']['id'],
            'text' => $message,
        ]);
    }

    public function isCallback()
    {
        if (!is_array($this->updates)) {
            return;
        }

        return array_key_exists('callback_query', $this->updates);
    }

    public function run()
    {

        if ($this->updates == '') {
            $this->json(['message' => 'Обновлений нет']);
            return;
        }

        echo 'ok';

        if ($this->config['rick.logs']) {
            $this->logger->log($this->user, $this->updates);
        }

        // если поступил только колбэк то сообщения не обрабатываем
        if ($this->isCallback()) {
            $callback_data = $this->updates['callback_query']['data'];

            if (!array_key_exists($callback_data, $this->callbacks)) {
                $callback_data = '{default}';
            }

            if (strpos($this->callbacks[$callback_data], '::') == false) {
                return $this->callbacks[$callback_data]();
            } else {
                return $this->callbacks[$callback_data]($this, $this->user, $this->updates);
            }
        }

        $message = $this->user['message'];

        if (!array_key_exists($message, $this->events)) {
            $message = '{default}';
        }

        if (strpos($this->events[$message], '::') == false) {
            return $this->events[$message]();
        } else {
            return $this->events[$message]($this, $this->user, $this->updates);
        }
    }

    public function json($array)
    {
        echo '<pre>';
        echo json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo '</pre>';
    }

    public function debug($message)
    {
        echo '<pre>';
        echo print_r($message);
        echo '</pre>';
    }
}
