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

        if (array_key_exists('edited_message', $update))
            $key = 'edited_message';

        if ($key == 'edited_message' || $key == 'message') {
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
        }

        if (array_key_exists('callback_query', $update)) {
            $this->message_id = $update['callback_query']['message']['message_id'];
            $this->callback_id = $update['callback_query']['id'];
            $this->chat_id = $update['callback_query']['message']['chat']['id'];
            $this->chat_name = trim($update['callback_query']['message']['chat']['first_name'] . ' ' . $update['callback_query']['message']['chat']['last_name']) ?? null;
            $this->chat_username = $update['callback_query']['message']['chat']['username'] ?? null;;
            $this->id = $update['callback_query']['from']['id'];
            $this->full_name = trim($update['callback_query']['from']['first_name'] . ' ' . $update['callback_query']['from']['last_name']);
            $this->first_name = $update['callback_query']['from']['first_name'] ?? null;
            $this->last_name = $update['callback_query']['from']['last_name'] ?? null;
            $this->username = $update['callback_query']['from']['username'] ?? null;
            $this->lang = $update['callback_query']['from']['language_code'] ?? null;
            $this->message = array_key_exists('text', $update['callback_query']['message']) ? trim($update[$key]['text']) : trim($update['callback_query']['message']);
            $this->callback_data = $update['callback_query']['data'];
        }


        if (in_array($this->id, $this->bot->config['admin.list']) || in_array($this->username, $this->bot->config['admin.list']))
            $this->admin = true;

        // если передана бд
        if ($this->bot->config['db.driver'] && is_array($bot->update) && trim($this->id) !== '') {
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
                    'nickname' => null,
                    'role' => 'user',
                    'bot_version' => array_key_exists('bot.version', $this->bot->config) ? $this->bot->config['bot.version'] : '',
                ];

                if (is_array($this->bot->config['db.insert']))
                    $insert = array_merge($insert, $this->bot->config['db.insert']);

                $this->insert($insert);
                $this->data = $this->getById($this->id);
                $this->new = true;
            } else {
                $this->data = $this->getById($this->id);

                $this->ban = $this->data['ban'] == 1 ? true : false;
                $this->state_name  = $this->data['state_name'];
                $this->state_data = $this->data['state_data'];

                if (array_key_exists('bot.version', $this->bot->config)) {
                    if ($this->bot->config['bot.version'] !== $this->data['bot_version']) {
                        $this->updateById($this->id, ['bot_version' => $this->bot->config['bot.version']]);
                        $this->newVersion = true;
                    }

                }

                $diffMessageTime = time() - $this->data['last_message'];
                if ($diffMessageTime <= $this->bot->config['spam.timeout'])
                    $this->spam = $this->bot->config['spam.timeout'] - $diffMessageTime;
                else
                    $this->updateById($this->id, ['last_message' => time()]);
            }
        }
    }

    public function insert($insert)
    {
        $this->bot->db->table('users')->insert($insert);
    }

    public function update($update = [])
    {
        return $this->bot->db->table('users')->where('user_id', '=', $this->id)->update($update);
    }

    public function updateById($user_id, $update = [])
    {
        return $this->bot->db->table('users')->where('user_id', '=', $user_id)->update($update);
    }

    public function exist($user_id)
    {
        return $this->bot->db->table('users')->where('user_id', '=', $user_id)->count() > 0 ? true : false;
    }

    public function getById($user_id, $select = ['*'])
    {
        return $this->bot->db->table('users')->find($user_id, $select, 'user_id');
    }

    public function banById($user_id, $comment, $ban_start, $ban_end)
    {
        $this->updateById($user_id, [
            'ban' => 1,
            'ban_comment' => $comment,
            'ban_start' => $ban_start,
            'ban_end' => $ban_end
        ]);
    }

    public function ban($comment, $ban_start, $ban_end)
    {
        $this->updateById($this->user->id, [
            'ban' => 1,
            'ban_comment' => $comment,
            'ban_start' => $ban_start,
            'ban_end' => $ban_end
        ]);
    }
    public function unBan()
    {
        return $this->update([
            'ban' => 0,
            'ban_comment' => null,
            'ban_start' => null,
            'ban_end' => null,
        ]);
    }

    public function unBanById($user_id)
    {
        return $this->updateById($user_id, [
            'ban' => 0,
            'ban_comment' => null,
            'ban_start' => null,
            'ban_end' => null,
        ]);
    }

    public function setState($name = null, $data = null)
    {
        $update = [];

        if ($name)
            $update['state_name'] = $name;

        if ($data)
            $update['state_data'] = $data;

        return $this->updateById($this->id, $update);
    }

    public function clearState()
    {
        $update = [];
        $update['state_name'] = null;
        $update['state_data'] = null;

        return $this->updateById($this->id, $update);
    }

    public function clearStateById($user_id)
    {
        $update = [];
        $update['state_name'] = null;
        $update['state_data'] = null;

        return $this->updateById($user_id, $update);
    }
}
