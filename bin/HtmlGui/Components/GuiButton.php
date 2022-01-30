<?php

namespace App\HtmlGui\Components;

class GuiButton extends GuiComponent
{
    public $type = 'button';

    public function renderJs()
    {
        $buttonType = '';
        if ($this->attributes->has('texture')) {
            $buttonType = 'Textured';
        }
        $js = '';
        if ($this->attributes->has('onclick')) {
            $onclickFn = 'function() { ' . $this->attributes->get('onclick') . ' }';
            $js .= "\nonHtmlGuiCustomButton(id({$this->getRenderId()}), $onclickFn, [id({$this->getRenderId()})]);";
        }

        $js .= 'return gui.add' . $buttonType . 'Button(id(' . $this->getRenderId() . '), ' .
            ($this->text ?: '\'\'') . ', ' .
            $this->getRenderX() . ', ' .
            $this->getRenderY() . ', ' .
            $this->attributes->get('width', 0, true) . ', ' .
            $this->attributes->get('height', 0, true);

        if ($this->attributes->has('texture')) {
            $textureX = $this->attributes->get('texture-x', 0, true);
            $textureY = $this->attributes->get('texture-y', 0, true);
            $texture = $this->attributes->get('texture');
            $texture = str_replace('\/', '/', $texture);

            $js .= ', ' . $texture //texture path
                . ', ' . $textureX //texture offset x
                . ', ' . $textureY //texture offset y
            ;
        }

        $js .= ');';

        //Render event hooks


        return $js;
    }
}
