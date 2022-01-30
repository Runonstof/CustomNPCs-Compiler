<?php

namespace App\HtmlGui\Components;

class GuiExport extends GuiComponent
{
    public $templateConstruct = true;
    public $type = 'export';

    public function renderJs()
    {
        $js = '';
        //Handle ref exports
        $type = $this->attributes->get('type', 'var', true);
        $var = $this->attributes->get('var', 'null', true);
        $name = $this->attributes->get('name', 'export');
        switch ($type) {
            case 'ref':
                $js .= "if(\$props.ref) { \$refs[\$props.ref] = $var; }\n";
                break;
            case 'var':
                $js .= "\$return[$name] = $var;\n";
                break;
            case 'return':
                $js .= "\$return = $var;\n";
                break;
        }

        return $js;
    }

    public function renderIds()
    {
        return [];
    }
}
