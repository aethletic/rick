<?php

namespace Botify4\Extension;

use Botify4\Bot;

abstract class AbstractExtension
{
  protected $bot;

  public function __construct()
  {
    $this->bot = Bot::self();
  }
}
