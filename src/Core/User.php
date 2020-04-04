<?php

namespace Aethletic\Telegram\Core;

class User
{
    /**
     * Объект бота
     * @var Bot
     */
    private $bot;

    public $new = false;
    public $spam = false;
    public $ban = false;
    public $admin = false;
    public $newVersion = false;

    public function __construct($bot)
    {
        $this->bot = $bot;
        $update = $this->bot->update;

        if (array_key_exists('message', $update))
            $key = 'message';

        if (array_key_exists('callback_query', $update))
            $key = 'callback_query';

        if (array_key_exists('edited_message', $update))
            $key = 'edited_message';

        $this->message_id = $update[$key]['message_id'];
        $this->chat_id = $update[$key]['chat']['id'];
        $this->chat_name = trim($update[$key]['chat']['first_name'] . ' ' . $update['message']['chat']['last_name']) ?? null;
        $this->chat_username = $update[$key]['chat']['username'] ?? null;;
        $this->id = $update[$key]['from']['id'];
        $this->full_name = trim($update[$key]['from']['first_name'] . ' ' . $update['message']['from']['last_name']);
        $this->first_name = $update[$key]['from']['first_name'] ?? null;
        $this->last_name = $update[$key]['from']['last_name'] ?? null;
        $this->username = $update[$key]['from']['username'] ?? null;
        $this->lang = $update[$key]['from']['language_code'] ?? null;
        $this->message = array_key_exists('text', $update[$key]) ? trim($update[$key]['text']) : trim($update[$key]['caption']);

        if (in_array($this->id, $this->bot->config['admin.list']) || in_array($this->username, $this->bot->config['admin.list']))
            $this->admin = true;

        // если передана бд
        if ($this->bot->config['db.driver']) {
            if (!$this->exist($this->id)) {
                $insert = [
                    'user_id' => $this->id,
                    'full_name' => $this->full_name,
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'username' => $this->username,
                    'lang' => $this->lang,
                    'first_message' => time(),
                    'last_message' => time(),
                    'ban' => 0,
                    'ban_comment' => null,
                    'ban_start' => null,
                    'ban_end' => null,
                    'state_name' => null,
                    'state_data' => null,
                    'bot_version' => '0.0.0',
                ];
                $this->insert($insert);
                $this->new = true;
            } else {
                $this->data = $this->getDataById($this->id);

                $this->ban = $this->data['ban'] == 1 ? true : false;

                if (array_key_exists('bot.version', $this->bot->config)) {
                    if ($this->bot->config['bot.version'] !== $this->data['bot_version']) {
                        $this->update($this->id, ['bot_version' => $this->bot->config['bot.version']]);
                        $this->newVersion = true;
                    }

                }

                $diffMessageTime = time() - $this->data['last_message'];
                if ($diffMessageTime <= $this->bot->config['spam.timeout'])
                    $this->spam = $this->bot->config['spam.timeout'] - $diffMessageTime;
                else
                    $this->update($this->id, ['last_message' => time()]);
            }
        }
    }

    public function insert()
    {
        $this->bot->db->table('users')->insert($insert);
    }

    public function update($user_id, $update = [])
    {
        return $this->bot->db->table('users')->where('user_id', '=', $user_id)->update($update);
    }

    public function exist($user_id)
    {
        return $this->bot->db->table('users')->where('user_id', '=', $user_id)->count() > 0 ? true : false;
    }

    public function getDataById($user_id)
    {
        return $this->bot->db->table('users')->find($user_id, ['*'], 'user_id');
    }
}
