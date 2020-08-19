<?php

namespace Botify\Extension;

use Botify\Bot;

abstract class AbstractExtension
{
  protected $bot;

  public function __construct()
  {
    $this->bot = Bot::self();
  }
}
