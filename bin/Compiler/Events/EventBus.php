<?php

namespace App\Compiler\Events;

use Tightenco\Collect\Support\Collection;

class EventBus
{
    protected static $events;
    private $domain;
    private $ran;

    public static function init()
    {
        self::$events = self::$events ?? new Collection;
    }

    public function __construct($domain = 'app')
    {
        self::init();
        $this->domain = $domain;
        self::$events[$this->domain] = self::$events[$this->domain] ?? new Collection;
    }

    public function events()
    {
        return self::$events[$this->domain];
    }

    public function register(Event $event)
    {
    }
}
