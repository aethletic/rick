<?php

namespace Botify\Extension;

class Keyboard extends AbstractExtension
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


    public function contact($text = 'Contact', $resize = true, $one_time = false)
    {
        $keyboard = [
            [
                'text' => $text,
                'request_contact' => true,
            ]
        ];

        $markup = [
            'keyboard' => [$keyboard],
            'resize_keyboard' => $resize,
            'one_time_keyboard' => $one_time,
        ];

        return json_encode($markup);
    }

    public function location($text = 'Location', $resize = true, $one_time = false)
    {
        $keyboard = [
            [
                'text' => $text,
                'request_location' => true,
            ]
        ];

        $markup = [
            'keyboard' => [$keyboard],
            'resize_keyboard' => $resize,
            'one_time_keyboard' => $one_time,
        ];

        return json_encode($markup);
    }

    public function add($keyboards = [])
    {
        self::$keyboards = $keyboards;
    }
}
