<?php

namespace App\Compiler\Events;

class Event
{
    private $name;
    private $action;

    public function __construct(string $name, callable $action)
    {
        $this->name = $name;
        $this->action = $action;
    }

    public function execute($arguments = [])
    {
        return call_user_func_array($this->action, $arguments);
    }

    public function getName()
    {
        return $this->name;
    }
}
