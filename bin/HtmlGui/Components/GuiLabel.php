<?php

namespace App\HtmlGui\Components;

class GuiLabel extends GuiComponent
{
    public $type = 'label';

    public function renderJs()
    {
        $text = $this->attributes->get('text', '');
        $defaultWidth = strlen($text) * 10;
        $js = 'var _component = gui.addLabel(id(' . $this->getRenderId() . '), ' .
            ($text ?: '\'\'') . ', ' .
            $this->getRenderX() . ', ' .
            $this->getRenderY() . ', ' .
            $this->attributes->get('width', $defaultWidth, true) . ', ' .
            $this->attributes->get('height', 16, true) .
            ');';
        if ($this->attributes->has('hover-text')) {
            $js .= "\n" . '_component.setHoverText(' . $this->attributes->get('hover-text') . ')';
        }
        $js .= "\nreturn _component;";
        return $js;
    }
}
