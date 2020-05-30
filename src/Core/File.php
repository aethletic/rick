<?php

namespace Botify\Core;

class File
{
    public static function upload($file_path)
    {
        return new \CURLFile($file_path);
    }
}
