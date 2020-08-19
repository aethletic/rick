<?php

namespace Botify4\Extension;

use Botify4\Exception;
use Botify4\Util;

class User extends AbstractExtension
{
  private $id;
  private $row;
  private $db;

  public $data = [];
  public $isNewVersion = false;
  public $isNewUser = false;
  public $isSpam = false;
  public $isAdmin = false;
  public $isBanned = false;

  public function __construct($id = false, $is_user_id = true)
  {
    if (!$id) {
      throw new Exception\Bot('Required parameter $id not passed.');
    }

    parent::__construct();

    $this->id = $id;
    $this->row = $is_user_id ? 'user_id' : 'id';
    $this->db = $this->bot->db;

    // check admin rights
    if (in_array($this->bot->user_id, $this->bot->config['admin.list']) || in_array($this->bot->username, $this->bot->config['admin.list'])) {
      $this->isAdmin = true;
    }

    if (!$this->db) {
      return $this;
    }

    $this->addUserIfNotExists();
    $this->data = $this->getById($id);

    // check new version
    $key = 'bot.version';
    if (array_key_exists($key, $this->bot->config)) {
      if ($this->bot->config[$key] !== $this->data['bot_version']) {
        $this->update([
          'bot_version' => $this->bot->config[$key],
        ]);
        $this->isNewVersion = true;
      }
    }

    // check spam time and update last_message
    $diffMessageTime = time() - $this->data['last_message'];
    if ($diffMessageTime <= @$this->bot->config['bot.spam_timeout']) {
      $this->isSpam = $this->bot->config['bot.spam_timeout'] - $diffMessageTime;
    } else {
      $this->update(['last_message' => time()]);
    }

    // check user ban
    $this->isBanned = $this->data['is_banned'] == 1;

    $this->role = $this->data['role'];
    $this->nickname = $this->data['nickname'];
    $this->icon = $this->data['user_icon'];

    $this->state_name = $this->data['state_name'];
    $this->state_data = $this->bot->utilisJson($this->data['state_data']) ? json_decode($this->data['state_data'], true) : $this->data['state_data'];
  }

  public function update($update)
  {
    return $this->db->table('users')->where($this->row, $this->id)->update($update);
  }

  public function updateById($user_id, $update)
  {
    return $this->db->table('users')->where($this->row, $user_id)->update($update);
  }

  public function get()
  {
    $res = $this->db->table('users')->where($this->row, $this->id)->get();
    return sizeof($res) > 0 ? $res[0] : false;
  }

  public function getById($user_id)
  {
    $res = $this->db->table('users')->where($this->row, $user_id)->get();
    return sizeof($res) > 0 ? $res[0] : false;
  }

  public function insert($insert)
  {
    return $this->db->table('users')->insert($insert);
  }

  public function delete()
  {
    return $this->db->table('users')->where($this->row, $this->id)->delete();
  }

  public function deleteById($user_id)
  {
    return $this->db->table('users')->where($this->row, $user_id)->delete();
  }

  public function count()
  {
    return $this->db->table('users')->count();
  }

  public function getStateData($data_json_decode = false)
  {
    $res = $this->db->table('users')->select('state_name', 'state_data')->where($this->row, $this->id)->get();

    if (sizeof($res) == 0) {
      return false;
    }

    $user = $res[0];

    return [
      'state_name' => $user['state_name'],
      'state_data' => $data_json_decode ? json_decode($user['state_data']) : $user['state_data'],
    ];
  }

  public function getStateDataById($user_id, $data_json_decode = false)
  {
    $res = $this->db->table('users')->select('state_name', 'state_data')->where($this->row, $user_id)->get();

    if (sizeof($res) == 0) {
      return false;
    }

    $user = $res[0];

    return [
      'name' => $user['state_name'],
      'data' => $data_json_decode ? json_decode($user['state_data']) : $user['state_data'],
    ];
  }

  public function clearState()
  {
    $update = [
      'state_name' => null,
      'state_data' => null,
    ];
    return $this->update($update);
  }

  public function clearStateById($user_id)
  {
    $update = [
      'state_name' => null,
      'state_data' => null,
    ];
    return $this->updateById($user_id, $update);
  }

  public function setState($state_name = null, $state_data = null)
  {
    if (is_array($state_data) || is_object($state_data)) {
      $state_data = json_encode($state_data, JSON_UNESCAPED_UNICODE);
    }

    return $this->update(compact('state_name', 'state_data'));
  }

  public function setStateById($user_id, $state_name = null, $state_data = null)
  {
    if (is_array($state_data) || is_object($state_data)) {
      $state_data = json_encode($state_data, JSON_UNESCAPED_UNICODE);
    }

    return $this->updateById($user_id, compact('state_name', 'state_data'));
  }

  public function existById($user_id)
  {
      return $this->db->table('users')->where($this->row, $user_id)->count() > 0 ? true : false;
  }

  private function addUserIfNotExists()
  {
    if ($this->existById($this->id)) {
      return false;
    }

    if (trim($this->bot->full_name) !== '') {
      $full_name = trim($this->bot->full_name);
    } else {
      $full_name = 'EMPTY_FULL_NAME';
    }

    if (trim($this->bot->lang) !== '') {
      $lang = $this->bot->lang;
    } else {
      $key = 'bot.default_lang';
      if (array_key_exists($key, $this->bot->config)) {
        $lang = $this->bot->config[$key];
      } else {
        $lang = 'en';
      }
    }

    $insert = [
      'user_id' => $this->bot->user_id,
      'full_name' => $full_name,
      'first_name' => trim($this->bot->first_name),
      'last_name' => trim($this->bot->last_name),
      'username' => $this->bot->username,
      'lang' => $lang,
      'first_message' => time(),
      'last_message' => time(),
      'is_banned' => 0,
      'ban_comment' => null,
      'ban_from' => null,
      'ban_to' => null,
      'state_name' => null,
      'state_data' => null,
      'nickname' => null,
      'photo' => null,
      'role' => 'user',
      'user_icon' => null,
      'is_active' => 1,
      'note' => null,
      'from_source' => null,
      'bot_version' => array_key_exists('bot.version', $this->bot->config) ? $this->bot->config['bot.version'] : null,
    ];

    $key = 'db.insert';
    if (array_key_exists($key, $this->bot->config)) {
      if (is_array($this->bot->config[$key])) {
        $insert = array_merge($insert, $this->bot->config[$key]);
      }
    }

    $this->isNewUser = true;

    return $this->insert($insert);
  }

  public function ban($from, $to, $comment = null)
  {
    $update = [
      'is_banned' => 1,
      'ban_from' => $from,
      'ban_to' => $to,
      'ban_comment' => $comment
    ];

    $this->update($update);
  }

  public function banById($user_id, $from, $to, $comment = null)
  {
    $update = [
      'is_banned' => 1,
      'ban_from' => $from,
      'ban_to' => $to,
      'ban_comment' => $comment
      'state_name' => null,
      'state_data' => null,
    ];

    $this->updateById($user_id, $update);
  }

  public function unban()
  {
    $update = [
      'is_banned' => 0,
      'ban_from' => null,
      'ban_to' => null,
      'ban_comment' => null
    ];

    $this->update($update);
  }

  public function unbanById($user_id)
  {
    $update = [
      'is_banned' => 0,
      'ban_from' => null,
      'ban_to' => null,
      'ban_comment' => null
    ];

    $this->updateById($user_id, $update);
  }
}
