<?php

namespace App\HtmlGui\Components;

class GuiTextField extends GuiComponent
{
    public $type = 'text-field';

    public function renderJs()
    {
        $js = 'var _component = gui.addTextField(id(' . $this->getRenderId() . '), ' .
            $this->getRenderX() . ', ' .
            $this->getRenderY() . ', ' .
            $this->attributes->get('width', 90, true) . ', ' .
            $this->attributes->get('height', 20, true) .
            ');';
        if ($this->text) {

            $js .= "\n" .
                '_component.setText(' . $this->text . ');';
        }

        $js .= "\nreturn _component;";
        return $js;
    }
}
