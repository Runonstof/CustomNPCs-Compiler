<?php

namespace App\HtmlGui\Components;

class GuiScript extends GuiComponent
{
    public $type = 'script';

    public function renderJs()
    {
        return $this->text;
    }

    public function renderIds()
    {
        return [];
    }
}
