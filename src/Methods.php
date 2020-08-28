<?php

namespace Botify;

use Botify\Util;

class Methods
{
  private $bot;
  public $result;

  public function __construct($bot) {
    $this->bot = $bot;
  }

  public function setWebhook($url = false)
  {
      if (!$url && array_key_exists('bot.url', $this->bot->config))
          $url = $this->bot->config['bot.url'];

      return $url ? json_decode(file_get_contents($this->bot->api_url . $this->bot->token . '/setWebhook?url=' . $url), true) : false;
  }

  public function deleteWebhook($token = false)
  {
      if (!$token && array_key_exists('bot.token', $this->bot->config))
          $token = $this->bot->config['bot.token'];

      return json_decode(file_get_contents($this->bot->api_url . $token . '/deleteWebhook'), true);
  }

  public function getWebhookInfo($token = false)
  {
      if (!$token && array_key_exists('bot.token', $this->bot->config))
          $token = $this->bot->config['bot.token'];

      return json_decode(file_get_contents($this->bot->api_url . $token . '/getWebhookInfo'), true);
  }

  public function answerInlineQuery($results = [], $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'inline_query_id' => $this->bot->inline_id,
          'results' => json_encode($results),
      ];

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('answerInlineQuery', $parameters);
  }

  public function sendAction($chat_id, $action, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $chat_id,
          'action' => $action,
      ];

      $parameters = array_merge($parameters, $scopes);

      $this->bot->api('sendChatAction', $parameters);

      return $this->bot;
  }

  public function isActive($chat_id, $action = 'typing')
  {
      $parameters = [
          'chat_id' => $chat_id,
          'action' => $action,
      ];

      return $this->bot->api('sendChatAction', $parameters)['ok'];
  }

  public function editMessageText($message_id, $text = '', $keyboard = false, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $this->bot->chat_id,
          'text' => $text,
          'parse_mode' => $this->bot->config['telegram.parse_mode'],
          'message_id' => $message_id,
      ];

      if ($keyboard)
          $parameters['reply_markup'] = $keyboard;

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('editMessageText', $parameters);
  }

  public function editMessageCaption($message_id, $text = '', $keyboard = false, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $this->bot->chat_id,
          'caption' => $text,
          'parse_mode' => $this->bot->config['telegram.parse_mode'],
          'message_id'   => $message_id,
      ];

      if ($keyboard)
          $parameters['reply_markup'] = $keyboard;

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('editMessageText', $parameters);
  }

  public function editMessageReplyMarkup($message_id, $keyboard = false, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $this->bot->chat_id,
          'caption' => $text,
          'message_id'   => $message_id,
      ];

      if ($keyboard)
          $parameters['reply_markup'] = $keyboard;

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('editMessageReplyMarkup', $parameters);
  }

  public function deleteMessage($chat_id, $message_id, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $chat_id,
          'message_id' => $message_id,
      ];

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('deleteMessage', $parameters, $is_file = false);
  }

  public function sendMessage($chat_id, $text = '', $keyboard = false, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $chat_id,
          'text' => $text,
          'parse_mode' => $this->bot->config['telegram.parse_mode'],
      ];

      if ($keyboard)
          $parameters['reply_markup'] = $keyboard;

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('sendMessage', $parameters);
  }

  public function sendLocation($chat_id, $latitude, $longitude, $keyboard = false, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $chat_id,
          'latitude' => $latitude,
          'longitude' => $longitude,
          'parse_mode' => $this->bot->config['telegram.parse_mode'],
      ];

      if ($keyboard)
          $parameters['reply_markup'] = $keyboard;

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('sendMessage', $parameters);
  }

  public function sendDice($chat_id, $emoji = '', $keyboard = false, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $chat_id,
          'emoji' => $emoji,
      ];

      if ($keyboard)
          $parameters['reply_markup'] = $keyboard;

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('sendMessage', $parameters);
  }

  public function sendReply($chat_id, $message_id, $text = '', $keyboard = false, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $chat_id,
          'text' => $text,
          'reply_to_message_id' => $message_id,
          'parse_mode' => $this->bot->config['telegram.parse_mode'],
      ];

      if ($keyboard)
          $parameters['reply_markup'] = $keyboard;

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('sendMessage', $parameters);
  }

  public function sendDocument($chat_id, $file, $text = '', $keyboard = false, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $chat_id,
          'caption' => $text,
          'document' => $file,
          'parse_mode' => $this->bot->config['telegram.parse_mode'],
      ];

      if ($keyboard)
          $parameters['reply_markup'] = $keyboard;

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('sendDocument', $parameters, $is_file = true);
  }

  public function sendPhoto($chat_id, $file, $text = '', $keyboard = false, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $chat_id,
          'caption' => $text,
          'photo' => $file,
          'parse_mode' => $this->bot->config['telegram.parse_mode'],
      ];

      if ($keyboard)
          $parameters['reply_markup'] = $keyboard;

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('sendPhoto', $parameters, $is_file = true);
  }

  public function sendVoice($chat_id, $file, $text = '', $keyboard = false, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $chat_id,
          'caption' => $text,
          'voice' => $file,
          'parse_mode' => $this->bot->config['telegram.parse_mode'],
      ];

      if ($keyboard)
          $parameters['reply_markup'] = $keyboard;

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('sendVoice', $parameters, $is_file = true);
  }

  public function sendAudio($chat_id, $file, $text = '', $keyboard = false, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $chat_id,
          'caption' => $text,
          'audio' => $file,
          'parse_mode' => $this->bot->config['telegram.parse_mode'],
      ];

      if ($keyboard)
          $parameters['reply_markup'] = $keyboard;

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('sendAudio', $parameters, $is_file = true);
  }

  public function sendVideo($chat_id, $file, $text = '', $keyboard = false, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $chat_id,
          'caption' => $text,
          'video' => $file,
          'parse_mode' => $this->bot->config['telegram.parse_mode'],
      ];

      if ($keyboard)
          $parameters['reply_markup'] = $keyboard;

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('sendVideo', $parameters, $is_file = true);
  }

  public function sendAnimation($chat_id, $file, $text = '', $keyboard = false, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $chat_id,
          'caption' => $text,
          'animation' => $file,
          'parse_mode' => $this->bot->config['telegram.parse_mode'],
      ];

      if ($keyboard)
          $parameters['reply_markup'] = $keyboard;

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('sendAnimation', $parameters, $is_file = true);
  }

  public function sendVideoNote($chat_id, $file, $text = '', $keyboard = false, $scopes = [])
  {
      if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $chat_id,
          'caption' => $text,
          'video_note' => $file,
          'parse_mode' => $this->bot->config['telegram.parse_mode'],
      ];

      if ($keyboard)
          $parameters['reply_markup'] = $keyboard;

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('sendVideoNote', $parameters, $is_file = true);
  }

  public function sendJson()
  {
      if (!$this->bot->isUpdate()) return;

      if (!$this->bot->isUpdate()) {
        return false;
      }

      $parameters = [
          'chat_id' => $this->bot->chat_id,
          'text' => '<code>'.json_encode($this->bot->update, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).'</code>',
          'parse_mode' => 'html',
      ];

      return $this->bot->api('sendMessage', $parameters, $is_file = false);
  }

  public function say($text, $keyboard = false, $scopes = [])
  {
      if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $this->bot->chat_id,
          'text' => $this->bot->util->shuffle($text),
          'parse_mode' => $this->bot->config['telegram.parse_mode'],
      ];

      if ($keyboard)
          $parameters['reply_markup'] = $keyboard;

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('sendMessage', $parameters);
  }

  public function reply($text, $keyboard = false, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $this->bot->chat_id,
          'text' => $this->bot->util->shuffle($text),
          'reply_to_message_id' => $this->bot->message_id,
          'parse_mode' => $this->bot->config['telegram.parse_mode'],
      ];

      if ($keyboard)
          $parameters['reply_markup'] = $keyboard;

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('sendMessage', $parameters);
  }

  /**
   * typing, upload_photo, record_video, upload_video, record_audio,
   * upload_audio, upload_document, find_location, record_video_note,
   * upload_video_note
   */
  public function action($action = 'typing', $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $this->bot->chat_id,
          'action' => $action,
      ];

      $parameters = array_merge($parameters, $scopes);

      $this->bot->api('sendChatAction', $parameters);

      return $this->bot;
  }

  public function notify($text, $alert = false, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      if (!$this->bot->isCallback)
          return;

      $parameters = [
          'callback_query_id' => $this->bot->update['callback_query']['id'],
          'text' => $text,
      ];

      if ($alert)
          $parameters['show_alert'] = true;

      $parameters = array_merge($parameters, $scopes);

      $this->bot->api('answerCallbackQuery', $parameters);
  }

  public function dice($emoji = '', $keyboard = false, $scopes = [])
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'chat_id' => $this->bot->chat_id,
          'emoji' => $emoji,
      ];

      if ($keyboard)
          $parameters['reply_markup'] = $keyboard;

      $parameters = array_merge($parameters, $scopes);

      return $this->bot->api('sendMessage', $parameters);
  }

  public function getFile($file_id, $local_file_path = false)
  {
    if (!$this->bot->isUpdate()) return;

      $parameters = [
          'file_id' => $file_id,
      ];

      if ($local_file_path) {
          $result = $this->bot->api('getFile', $parameters);
          if (!$result['ok'])
              return false;
          return $this->bot->saveFile($result['result']['file_path'], $local_file_path);
      }

      return $this->bot->api('getFile', $parameters);
  }

  public function saveFile($file_path, $local_file_path = false)
  {
    if (!$this->bot->isUpdate()) return;

      if (!$local_file_path)
          return false;

      $extension = stripos(basename($file_path), '.') !== false ? end(explode('.', basename($file_path))) : '';
      $local_file_path = str_ireplace(['{ext}', '{extension}', '{file_ext}'], $extension, $local_file_path);
      $local_file_path = str_ireplace(['{base}', '{basename}', '{base_name}', '{name}'], basename($file_path), $local_file_path);
      $local_file_path = str_ireplace(['{time}'], time(), $local_file_path);
      $local_file_path = str_ireplace(['{md5}'], md5(time().mt_rand()), $local_file_path);
      $local_file_path = str_ireplace(['{rand}','{random}','{rand_name}','{random_name}'], md5(time().mt_rand()) . ".$extension", $local_file_path);
      $local_file_path = str_ireplace(['{base}', '{basename}', '{base_name}', '{name}'], basename($file_path), $local_file_path);

      file_put_contents($local_file_path, file_get_contents("https://api.telegram.org/file/bot{$this->bot->token}/{$file_path}"));

      return basename($local_file_path);
  }
}
