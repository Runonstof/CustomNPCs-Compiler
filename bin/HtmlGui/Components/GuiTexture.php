<?php

namespace App\HtmlGui\Components;

class GuiTexture extends GuiComponent
{
    public $type = 'texture';

    public function renderJs()
    {
        //addTexturedRect​(int id, java.lang.String texture, int x, int y, int width, int height, int textureX, int textureY)

        $hasTextureOffset = $this->attributes->has('texture-x') || $this->attributes->has('texture-y');
        $textureX = $this->attributes->get('texture-x', 0, true);
        $textureY = $this->attributes->get('texture-y', 0, true);
        $js = 'var _component = gui.addTexturedRect(id(' . $this->getRenderId() . '), ' .
            $this->attributes->get('texture') . ', ' .
            $this->getRenderX() . ', ' .
            $this->getRenderY() . ', ' .
            $this->attributes->get('width', 16, true) . ', ' .
            $this->attributes->get('height', 16, true) .
            ($hasTextureOffset ? "$textureX, $textureY" : '')
            . ')';

        if ($this->attributes->has('scale')) {
            $js .= "\n_component.setScale({$this->attributes->get('scale', 1, true)})";
        }

        $js .= "\nreturn _component;";
        return $js;
    }
}
