<?php

namespace Botify\Extension;

class File
{
    public static function upload($file_path = false)
    {
        return $file_path ? new \CURLFile($file_path) : false;
    }
}
