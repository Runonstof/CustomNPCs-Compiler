<?php

namespace App\HtmlGui\Components;

class GuiImage extends GuiComponent
{
    /**
    addTexturedRect​(
        int id,
        java.lang.String texture,
        int x,
        int y,
        int width,
        int height
    ) 	

    addTexturedRect​(
        int id,
        java.lang.String texture,
        int x,
        int y,
        int width,
        int height,
        int textureX,
        int textureY
    )
 
     */
    public function renderJs()
    {
        $texture = $this->attributes->get('texture');
        $js = $this->getRenderVar('var ', ' = ') . 'gui.addTexturedRect(id(' . $this->getRenderId() . '), ' .
            $texture . ', ' . //texture
            $this->getRenderX() . ', ' . //x
            $this->getRenderY() . ', ' . //y
            $this->style->get('width', 0) . ', ' . //width
            $this->style->get('height', 0) //height
        ;

        $textureX = $this->attributes->get('texture-x', 0);
        $textureY = $this->attributes->get('texture-y', 0);

        if (!is_null($textureX) || !is_null($textureY)) {
            $js .= ', ' . $textureX //texture offset x
                . ', ' . $textureY; //texture offset y
        }

        $js .= ');';

        return $js;
    }
}
