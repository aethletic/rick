<?php

namespace Aethletic\Telegram;

require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/File.php';

class Bot
{
    // system settings
    protected $token;
    protected $base_url = 'https://api.telegram.org/bot';

    // telegram settings
    protected $events = [];
    protected $keyboards = [];
    protected $callbacks = [];
    protected $user = [];
    protected $updates = [];
    protected $logger;

    // bot settings
    protected $config = [
        'rick.logs' => false,
        'rick.logs_dir' => '',
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

    public function execute($method, $params, $is_file = false)
    {
        $url = $this->base_url . $this->token . '/' . $method;

        if ($is_file) {
            $content_type = 'Content-Type: multipart/form-data';
        } else {
            $content_type = 'Content-Type: application/json';
            $params = json_encode($params);
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$content_type]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = json_decode(curl_exec($ch), true);

        curl_close($ch);

        return $response;
    }

    public function hear($events = [], $callback)
    {
        if (!is_array($events)) {
            $events = [$events];
        }

        foreach ($events as $event) {
            $this->events[$event] = $callback;
        }
    }

    // реакция на колбэк_дату из инлайн кнопок
    public function callback($events = [], $callback)
    {
        if (!is_array($events)) {
            $events = [$events];
        }

        foreach ($events as $event) {
            $this->callbacks[$event] = $callback;
        }
    }

    public function say($message, $keyboard = false)
    {
        $params = [
            'chat_id'       => $this->user['chat_id'],
            'text'          => $this->randomMessage($message),
            'parse_mode'    => 'markdown',
        ];

        if ($keyboard) {
            $params['reply_markup'] = $keyboard;
        }

        return $this->execute('sendMessage', $params);
    }

    public function sendMessage($chat_id, $message, $keyboard = null)
    {
        $params = [
            'chat_id'       => $chat_id,
            'text'          => $message,
            'parse_mode'    => 'markdown',
        ];

        if ($keyboard) {
            $params['reply_markup'] = $keyboard;
        }

        return $this->execute('sendMessage', $params);
    }

    public function run()
    {
        echo 'ok';

        if ($this->config['rick.logs']) {
            // $this->logger->log($this->user);
            $this->logger->logUpdate($this->updates);
        }

        // если поступил только колбэк то сообщения не обрабатываем
        if (array_key_exists('callback_query', $this->updates)) {
            $callback_data = $this->updates['callback_query']['data'];

            if (!array_key_exists($callback_data, $this->callbacks)) {
                $callback_data = '{default}';
            }

            if (strpos($this->callbacks[$callback_data], '::') == false) {
                return $this->callbacks[$callback_data]();
            } else {
                return $this->callbacks[$message]($this, $this->user, $this->updates);
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

        if (!is_array($updates) || count($updates) == 0) {
            exit('Обновлений нет');
        }

        if (!array_key_exists('callback_query', $updates)) {
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

    public function sendDocument($chat_id, $file, $message = '', $keyboard = false)
    {
        $params = [
            'chat_id' => $chat_id,
            'document' => $file,
            'caption' => $message,
            'parse_mode' => 'markdown',
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
            'parse_mode' => 'markdown',
        ];

        if ($keyboard) {
            $params['reply_markup'] = $keyboard;
        }

        $this->execute('sendPhoto', $params, true);
    }

    public function inline($inline)
    {
        if (!is_array($inline)) {
            $keyboard = $this->keyboards[$inline];
        }
        return json_encode(['inline_keyboard' => $keyboard]);
    }

    public function notify($message)
    {
        if (!array_key_exists('callback_query', $this->updates)) {
            return false;
        }

        $this->execute('answerCallbackQuery', [
            'callback_query_id' => $this->updates['callback_query']['id'],
            'text' => $message,
        ]);
    }
}
