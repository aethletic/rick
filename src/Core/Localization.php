<?php

namespace Botify\Core;

class Localization
{
    private $lang;
    private $localizations = [];
    private $default_lang = 'ru';

    public function setLang($lang = 'ru')
    {
        $this->lang = $lang;
    }

    public function setDefaultLang($lang = 'ru')
    {
        $this->default_lang = $lang;
    }

    public function get($key, $params = null)
    {
        $msg = array_key_exists($this->lang, $this->localizations) ? $this->localizations[$this->lang][$key] : $this->localizations[$this->default_lang][$key];

        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $msg = str_ireplace('{' . $key . '}', $value, $msg);
            }
        }

        return $msg;
    }

    // можно передать путь до файлов например __DIR__ . '/loc/*.json';
    // или массив вида ['ru' => ['hello' => 'привет']];
    public function add($localizations)
    {
        if (!is_array($localizations)) {
            $dir = $localizations;
            $localizations = [];
            foreach (glob($dir) as $key => $file) {
                $lang = str_ireplace('.json', '', basename($file));
                $data = json_decode(file_get_contents($file), true);
                $localizations[$lang] = $data;
            }
        }
        $this->localizations = array_merge($this->localizations, $localizations);
    }
}
