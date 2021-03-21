<?php

namespace App\HtmlGui\Components;

class GuiTextField extends GuiComponent
{
    public $type = 'text-field';

    public function renderJs()
    {
        $js = $this->getRenderVar('var ', ' = ') . 'gui.addTextField(id(' . $this->getRenderId() . '), ' .
            $this->getRenderX() . ', ' .
            $this->getRenderY() . ', ' .
            $this->attributes->get('width', 90, true) . ', ' .
            $this->attributes->get('height', 20, true) .
            ');';
        if ($this->text) {

            $js .= "\n" .
                $this->getRenderVar() . '.setText(' . $this->text . ');';
        }

        return $js;
    }
}
