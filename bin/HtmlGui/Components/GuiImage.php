<?php

namespace App\HtmlGui\Components;

class GuiImage extends GuiComponent
{
    public function renderJs()
    {
        $texture = $this->attributes->get('texture');
        $texture = str_replace('\/', '/', $texture);
        $js = 'var _component = gui.addTexturedRect(id(' . $this->getRenderId() . '), ' .
            $texture . ', ' . //texture
            $this->getRenderX() . ', ' . //x
            $this->getRenderY() . ', ' . //y
            $this->attributes->get('width', 0, true) . ', ' . //width
            $this->attributes->get('height', 0, true) //height
        ;

        $textureX = $this->attributes->get('texture-x', 0, true);
        $textureY = $this->attributes->get('texture-y', 0, true);

        if ($this->attributes->has('texture-x') || $this->attributes->has('texture-y')) {
            $js .= ', ' . $textureX //texture offset x
                . ', ' . $textureY; //texture offset y
        }

        $js .= ');';

        if ($this->attributes->has('scale')) {
            $js .= "\n_component.setScale({$this->attributes->get('scale', 1, true)})";
        }

        // $js .= "\nlet lastVal = str => (str.match(/\w+\.\w+$/)||[])[0]||'';let now = new Date();\$data.player.message(''+now.getMinutes() + now.getSeconds() + 'render' + \$key + ': ' + lastVal($texture));";
        // $js .= "\n\$data.player.message('render: ' + $texture);";

        $js .= "\nreturn _component;";

        return $js;
    }
}
