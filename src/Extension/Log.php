<?php

namespace Botify4\Extension;

use Botify4\Util;

class Log
{
    private $dir;

    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    public function add($data = false, $type = 'info')
    {
        if (!$data) {
            return;
        }

        $date = date("d.m.Y, H:i:s");
        $data = is_array($data) ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : trim($data);
        $log = "[$date] [$type] $data";

        $filename = 'bot_' . date("d-m-Y") . '.log';
        file_put_contents("{$this->dir}/{$filename}", $log . PHP_EOL, FILE_APPEND);
    }

    public static function addMessagesToDatabase($bot)
    {
        if ($bot->config['database.driver'] == false) {
          return false;
        }

        $date = $bot->util->midnight();

        // messages stats
        $isNewDate = $bot->db->table('stats_messages')->where('date', $date)->count() == 0;
        if ($isNewDate) {
            $bot->db->query("INSERT INTO stats_messages (date, count) VALUES ({$date}, 1)");
        } else {
            $bot->db->query("UPDATE stats_messages SET count = count + 1 WHERE date = {$date}");
        }

        // new users stats

        $isNewDate = $bot->db->table('stats_new_users')->where('date', $date)->count() == 0;
        if ($bot->user->isNewUser) {
            if ($isNewDate) {
                $bot->db->query("INSERT INTO stats_new_users (date, count) VALUES ({$date}, 1)");
            } else {
                $bot->db->query("UPDATE stats_new_users SET count = count + 1 WHERE date = {$date}");
            }
        } else {
            if ($isNewDate) {
                $bot->db->query("INSERT INTO stats_new_users (date, count) VALUES ({$date}, 0)");
            }
        }


        if ($bot->isMessage || $bot->isCommand) {
            $update = $bot->update;

            if ($bot->isSticker) {
                $update['message']['text'] = '🖼 Стикер';
            }
            if ($bot->isPhoto) {
                $update['message']['text'] = '🖼 Фотография';
            }
            if ($bot->isVideo) {
                $update['message']['text'] = '🎬 Видео';
            }
            if ($bot->isVideoNote) {
                $update['message']['text'] = '🎬 Видеосообщение';
            }
            if ($bot->isDocument) {
                $update['message']['text'] = '📎 Файл';
            }
            if ($bot->isAnimation) {
                $update['message']['text'] = '🖼 Gif';
            }
            if ($bot->isAudio) {
                $update['message']['text'] = '🎶 Аудио';
            }
            if ($bot->isVoice) {
                $update['message']['text'] = '🎤 Голосовое сообщение';
            }
            if ($bot->isContact) {
                $update['message']['text'] = '💌 Контакт';
            }
            if ($bot->isLocation) {
                $update['message']['text'] = '📍 Геолокация';
            }
            if ($bot->isVenue) {
                $update['message']['text'] = '📍 Место встречи';
            }
            if ($bot->isPoll) {
                $update['message']['text'] = '📊 Голосование';
            }

            $insert = [
            'date' => time(),
            'user_id' => $bot->user_id,
            'user' => $bot->full_name,
            'value' => json_encode($update, JSON_UNESCAPED_UNICODE)
        ];

            $bot->db->table('messages')
                    ->insert($insert);
        }
    }
}
