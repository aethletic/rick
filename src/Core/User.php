<?php

namespace Botify\Core;

class User
{
    /**
     * Объект бота
     * @var Bot
     */
    private $bot;

    public $isNewUser = false;
    public $isSpam = false;
    public $isBanned = false;
    public $isAdmin = false;
    public $isNewVersion = false;

    public function __construct($bot)
    {
        $this->bot = $bot;
        $update = $this->bot->update;

        if (in_array($this->bot->user_id, $this->bot->config['admin.list']) || in_array($this->bot->username, $this->bot->config['admin.list']))
            $this->isAdmin = true;

        // если передана бд
        if ($this->bot->config['db.driver'] && is_array($bot->update) && trim($this->bot->user_id) !== '') {
            if (!$this->exist($this->bot->user_id)) {
                $insert = [
                    'user_id' => $this->bot->user_id,
                    'full_name' => $this->bot->full_name,
                    'first_name' => $this->bot->first_name,
                    'last_name' => $this->bot->last_name,
                    'username' => $this->bot->username,
                    'lang' => $this->bot->lang,
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
                    'bot_version' => array_key_exists('bot.version', $this->bot->config) ? $this->bot->config['bot.version'] : null,
                ];

                if (is_array($this->bot->config['db.insert']))
                    $insert = array_merge($insert, $this->bot->config['db.insert']);

                $this->insert($insert);
                $this->data = $this->getById($this->bot->user_id);
                $this->isNewUser = true;
            } else {
                $this->data = $this->getById($this->bot->user_id);

                $this->isBanned = $this->data['ban'] == 1 ? true : false;
                $this->state_name  = $this->data['state_name'];
                $this->state_data = $this->data['state_data'];

                if (array_key_exists('bot.version', $this->bot->config)) {
                    if ($this->bot->config['bot.version'] !== $this->data['bot_version']) {
                        $this->updateById($this->bot->user_id, ['bot_version' => $this->bot->config['bot.version']]);
                        $this->isNewVersion = true;
                    }

                }

                $diffMessageTime = time() - $this->data['last_message'];
                if ($diffMessageTime <= $this->bot->config['spam.timeout'])
                    $this->isSpam = $this->bot->config['spam.timeout'] - $diffMessageTime;
                else
                    $this->updateById($this->bot->user_id, ['last_message' => time()]);
            }
        }
    }

    public function insert($insert)
    {
        $this->bot->db->table('users')->insert($insert);
    }

    public function update($update = [])
    {
        return $this->bot->db->table('users')->where('user_id', '=', $this->bot->user_id)->update($update);
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
        $this->updateById($this->user->bot->user_id, [
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

    // public function setState($name = null, $data = null)
    // {
    //     $update = [];
    //
    //     if ($name) {
    //         $this->state_name = $name;
    //         $update['state_name'] = $name;
    //     }
    //
    //     if ($data) {
    //         $this->state_data = $data;
    //         $update['state_data'] = $data;
    //     }
    //
    //     return $this->updateById($this->bot->user_id, $update);
    // }
    //
    // public function clearState()
    // {
    //     $update = [];
    //     $update['state_name'] = null;
    //     $update['state_data'] = null;
    //
    //     $this->state_name = null;
    //     $this->state_data = null;
    //
    //     return $this->updateById($this->bot->user_id, $update);
    // }
    //
    // public function clearStateById($user_id)
    // {
    //     $update = [];
    //     $update['state_name'] = null;
    //     $update['state_data'] = null;
    //
    //     return $this->updateById($user_id, $update);
    // }
}
