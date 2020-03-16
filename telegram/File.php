<?php

namespace Aethletic\Telegram;

class File
{
    public static function upload($file_path)
    {
        return new \CURLFile($file_path);
    }
}
