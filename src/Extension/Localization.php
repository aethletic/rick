<?php

namespace Botify\Extension;

class Localization extends AbstractExtension
{
    private $lang;
    private $default_lang = 'en';
    private $localizations = [];

    public function __construct($lang = 'en', $default_lang = 'en')
    {
      $this->setLang($lang);
      $this->setDefaultLang($default_lang);
    }

    public function setLang($lang = 'en')
    {
      if (trim($lang) == '') {
        $lang = 'en';
      }
      $this->lang = $lang;

      return $this;
    }

    public function setDefaultLang($lang = 'en')
    {
      if (trim($lang) == '') {
        $lang = 'en';
      }
      $this->default_lang = $lang;

      return $this;
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

        return $this;
    }

    public function exists($lang = false)
    {
      if (!$lang) return false;
      return array_key_exists($lang, $this->localizations);
    }

    public function getAvailableLangs()
    {
      return array_keys($this->localizations);
    }
}
