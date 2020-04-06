<?php

namespace Aethletic\Telegram\Core;

class File
{
    public static function upload($file_path)
    {
        return new \CURLFile($file_path);
    }
}
