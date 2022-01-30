<?php

namespace App\HtmlGui\Components;

class GuiSlot extends GuiComponent
{
    public $type = 'slot';

    public function renderJs()
    {
        $js = '';
        if ($this->attributes->has('onchange')) {
            $onchangeFn = 'function() { ' . $this->attributes->get('onchange') . ' }';
            $js .= "\nonHtmlGuiCustomSlot(id({$this->getRenderId()}), $onchangeFn, [id({$this->getRenderId()})]);";
        }
        $js .= '$data.player.message("building slot");';
        $js .= 'var _component = gui.addItemSlot(' .
            $this->getRenderX() . ', ' .
            $this->getRenderY() .
            ($this->attributes->has('value') ? ', ' . $this->attributes->get('value') : '') .
            ');';
        // $js .= "gui.removeComponent(_component.getID());";
        $js .= "_component.setID(id({$this->getRenderId()}));";
        $js .= "gui.updateComponent(_component);";
        $js .= "gui.update(\$data.player);";
        $js .= '$data.player.message("slotID: " + _component.getID() + " setToID: " + ' . $this->getRenderId() . ' + "(" + id(' . $this->getRenderId() . ') + ")");';
        $js .= "\nreturn _component;";
        return $js;
    }
}
