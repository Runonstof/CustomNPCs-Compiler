<?php

namespace App\HtmlGui\Components;

class GuiButton extends GuiComponent
{
    public $type = 'button';

    public function renderJs()
    {
        $js = $this->getRenderVar('var ', ' = ') . 'gui.addButton(id(' . $this->getRenderId() . '), ' .
            ($this->text ?: '\'\'') . ', ' .
            $this->getRenderX() . ', ' .
            $this->getRenderY() . ', ' .
            $this->style->get('width', 0) . ', ' .
            $this->style->get('height', 0) .
            ');';

        return $js;
    }
}
