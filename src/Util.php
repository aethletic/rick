<?php

namespace Botify4;

class Util
{
    public function shuffle($message)
    {
        preg_match_all('/{{(.+?)}}/mi', $message, $sentences);

        if (sizeof($sentences[1]) == 0) {
            return $message;
        }

        foreach ($sentences[1] as $words) {
            $words_array = explode('|', $words);
            $select = $words_array[array_rand($words_array)];
            $message = str_ireplace('{{' . $words . '}}', $select, $message);
        }

        return $message;
    }

    public function isRegEx($string)
    {
        return @preg_match($string, '') !== false;
    }

    public function isJson($string)
    {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    // $n = 42, $forms = ['арбуз', 'арбуза', 'арбузов']
    public function plural($n, $forms)
    {
        return is_float($n)?$forms[1]:($n%10==1&&$n%100!=11?$forms[0]:($n%10>=2&&$n%10<=4&&($n%100<10||$n%100>=20)?$forms[1]:$forms[2]));
    }

    public function pluralEng($value, $phrase)
    {
        $plural='';
        if ($value>1) {
            for ($i=0;$i<strlen($phrase);$i++) {
                if ($i==strlen($phrase)-1) {
                    $plural.=($phrase[$i]=='y')? 'ies':(($phrase[$i]=='s'|| $phrase[$i]=='x' || $phrase[$i]=='z' || $phrase[$i]=='ch' || $phrase[$i]=='sh')? $phrase[$i].'es' :$phrase[$i].'s');
                } else {
                    $plural.=$phrase[$i];
                }
            }
            return $plural;
        }
        return $phrase;
    }

    public function random($arr)
    {
        shuffle($arr);
        return $arr[array_rand($arr)];
    }

    // like 2020-02-02 00:00:00
    public function midnight($timestamp = false)
    {
      $timestamp = $timestamp ? $timestamp : time();
      return strtotime(date('Y-m-d', $timestamp) . ' midnight');
    }

}
