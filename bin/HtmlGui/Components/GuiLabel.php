<?php

namespace App\HtmlGui\Components;

class GuiLabel extends GuiComponent
{
    public $type = 'label';

    public function renderJs()
    {
        $js = $this->getRenderVar('var ', ' = ') . 'gui.addLabel(id(' . $this->getRenderId() . '), ' .
            ($this->text ?: '\'\'') . ', ' .
            $this->getCanvasX() . ', ' .
            $this->getCanvasY() . ', ' .
            $this->style->get('width', 0) . ', ' .
            $this->style->get('height', 0) .
            ');';

        return $js;
    }
}
