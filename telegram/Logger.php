<?php

namespace Aethletic\Telegram;

class Logger
{
    private $logs_dir = '';

    public function __construct($logs_dir)
    {
        $this->logs_dir = $logs_dir;
    }

    public function log($user, $update)
    {
        $date = date("d.m.Y H:i:s");

        $fullname = $user['fullname'] == '' ? "Неизвестно" : $user['fullname'];
        $username = $user['username'] == '' ? "Неизвестно" : $user['username'];

        $chat_name = $user['chat_name'] == '' ? "Неизвестно" : $user['chat_name'];
        $chat_username = $user['chat_username'] == '' ? "Неизвестно" : $user['chat_username'];

        $new_line = "[$date] [от: $fullname,  @$username, {$user['id']}, {$user['lang']}] [из чата: $chat_name, @$chat_username, {$user['chat_id']}] Сообщение: \"{$user['message']}\", Updates: " . json_encode($update, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

        file_put_contents($this->logs_dir . '/log_' . date("d-m-Y") . '.log', $new_line, FILE_APPEND);
    }
}
