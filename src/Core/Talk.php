<?php

namespace Botify\Core;

class Talk
{
    public $morphy;
    private $container = [];
    private $debug = false;

    public function __construct() {
        $this->morphy = new \cijic\phpMorphy\Morphy('ru');
    }

    public function setLanguage($lang = 'ru')
    {
        $allowedLangs = ['ru', 'ua', 'en', 'de'];

        if (!in_array(mb_strtolower($lang), $allowedLangs)) {
            $lang = 'ru'; // default lang if incorrect lang pass
        }

        $this->morphy = new \cijic\phpMorphy\Morphy($lang);
    }

    public function add($messages = false, $callback = false)
    {
        if (!$messages || !$callback) {
            return false;
        }

        $this->container[] = [
            'messages' => $messages,
            'callback' => $callback
        ];
    }

    public function get($message)
    {
        if ($this->getCount() == 0) {
            return false;
        }

        $str = mb_strtoupper($message);
        $str = preg_replace( "/[^a-zA-ZА-Яа-яёЁ0-9\s]/u", '', $str);
        $str = str_ireplace('ё', 'е', $str);
        $user_message = explode(' ', $str);

        foreach ($user_message as $key => $value) {
            $root_word = $this->morphy->lemmatize(mb_strtoupper($value));
            if (is_array($root_word)) {
                $root_word = end($root_word);
            } else {
                $root_word = $value;
            }
            $user_message[$key] = $root_word;
        }

        // getPseudoRoot

        $result = [];
        foreach ($user_message as $user_word) {
            foreach ($this->container as $id => $value) {
                $result[$id] = array_key_exists($id, $result) ? $result[$id] : ['id' => $id, 'count' => 0, 'score' => 0, 'matches' => []];
                foreach ($value['messages'] as $message) {
                    $message = preg_replace( "/[^a-zA-ZА-Яа-яёЁ0-9\s]/u", '', $message);
                    $message = str_ireplace('ё', 'е', $message);
                    $message = explode(' ', $message);
                    foreach ($message as $msgKey => $msgText) {
                        $root_word = $this->morphy->lemmatize(mb_strtoupper($msgText));
                        if ($root_word) {
                            $root_word = end($root_word);
                        } else {
                            $root_word = mb_strtoupper($msgText);
                        }
                        $message[$msgKey] = $root_word;
                    }

                    if (in_array($user_word, $message)) {
                        if (!in_array($user_word, $result[$id]['matches'])) {
                            $result[$id]['score'] = $result[$id]['score'] + 1;
                            $result[$id]['count'] = $result[$id]['count'] + 1;
                            $result[$id]['matches'][] = $user_word;
                        } else {
                            $result[$id]['count'] = $result[$id]['count'] + 1;
                        }
                    } else {
                        continue;
                    }
                }
            }
        }

        uasort($result, function ($a, $b) {
            if ($a['score'] == $b['score']) {
                if ($a['count'] < $b['count']) {
                    return -1;
                } else {
                    return 1;
                }
                return 0;
            }
            return ($a['score'] < $b['score']) ? -1 : 1;
        });

        $res = end($result);
        return $res['score'] == 0 ? false : $res;
    }

    public function getCount()
    {
        return sizeof($this->container);
    }

    public function getCallbackByID($id)
    {
        return $this->container[$id]['callback'];
    }

    public function setDebug($bool)
    {
        $this->debug = $bool;
    }

    public function isDebug()
    {
        return $this->debug;
    }
}
