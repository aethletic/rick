<?php

namespace Aethletic\Telegram\Utils;

class Bootstrap
{
    public function require($dirs)
    {
        foreach ($dirs as $dir) {
            $files = glob($dir);
            foreach ($files as $key => $file) {
                require_once $file;
            }
        }
    }
}
