<?php

namespace Aethletic\Telegram\Core;

class Keyboard
{
    protected static $keyboards = [];

    public function show($keyboard, $resize = true, $one_time = false)
    {
        if (!is_array($keyboard)) {
            $keyboard = self::$keyboards[$keyboard];
        }

        $markup = [
            'keyboard' => $keyboard,
            'resize_keyboard' => $resize,
            'one_time_keyboard' => $one_time,
        ];

        return json_encode($markup);
    }

    public function hide()
    {
        $markup = [
            'hide_keyboard' => true,
            'selective' => true,
        ];

        return json_encode($markup);
    }

    public function inline($keyboard)
    {
        if (!is_array($keyboard)) {
            $keyboard = self::$keyboards[$keyboard];
        }

        return json_encode(['inline_keyboard' => $keyboard]);
    }

    // request_contact, request_location
    public function request($type = 'contact', $text = null, $resize = true, $one_time = false)
    {
        $keyboard = [
            [
                'text' => $text,
                'request_' . trim($type) => true,
            ]
        ];

        $markup = [
            'keyboard' => [$keyboard],
            'resize_keyboard' => $resize,
            'one_time_keyboard' => $one_time,
        ];

        return json_encode($markup);
    }

    public function register($keyboards = [])
    {
        self::$keyboards = $keyboards;
    }
}
