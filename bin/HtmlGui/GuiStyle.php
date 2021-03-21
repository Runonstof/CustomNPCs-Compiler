<?php

namespace App\HtmlGui;

class GuiStyle
{
    const COLOR_DARK_RED = 'dark_red';
    const COLOR_RED = 'red';
    const COLOR_GOLD = 'gold';
    const COLOR_YELLOW = 'yellow';
    const COLOR_DARK_GREEN = 'dark_green';
    const COLOR_GREEN = 'green';
    const COLOR_AQUA = 'aqua';
    const COLOR_DARK_AQUA = 'dark_aqua';
    const COLOR_DARK_BLUE = 'dark_blue';
    const COLOR_BLUE = 'blue';
    const COLOR_LIGHT_PURPLE = 'light_purple';
    const COLOR_DARK_PURPLE = 'dark_purple';
    const COLOR_WHITE = 'white';
    const COLOR_GRAY = 'gray';
    const COLOR_DARK_GRAY = 'dark_gray';
    const COLOR_BLACK = 'black';
    const COLOR_NONE = null;

    const BORDER_TYPE_SOLID = 'solid';
    const BORDER_TYPE_DOTTED = 'dotted';
    const BORDER_TYPE_NONE = null;

    const GRID_TYPE_NONE = null;
    const GRID_TYPE_HORIZONTAL = 'horizontal';
    const GRID_TYPE_VERTICAL = 'vertical';
    
    
    private $data = [
        'position' => 'relative',
        'color' => GuiStyle::COLOR_NONE,
        'align' => [
            'hor' => 'left',
            'ver' => 'top',
        ],
        'border' => [
            'type' => GuiStyle::BORDER_TYPE_NONE, //null, 'solid', 'dotted'
            'color' => GuiStyle::COLOR_WHITE,
            'width' => 0,
        ],
        'padding' => [
            'top' => 0,
            'bottom' => 0,
            'right' => 0,
            'left' => 0
        ],
        'margin' => [
            'top' => 0,
            'bottom' => 0,
            'right' => 0,
            'left' => 0
        ],
        'width' => 0,
        'height' => 0,
        'depth' => 0
    ];

    public function __construct($data = [])
    {
        $this->data = array_merge($this->data, $data);

        // collect()->merge();
    }

    public function merge($style)
    {
        $this->data = array_merge_recursive($this->data, $style);
    }

    public function set($path, $value) {
        $separator = '.';
        $arr = &$this->data;
        $keys = explode($separator, $path);
    
        foreach ($keys as $key) {
            $arr = &$arr[$key];
        }
    
        return $arr = $value;
    }
    
    public function get($key, $default = null)
    {
        return collect($this->data)->rget($key) ?? $default;
    }

    public static function parse($string)
    {
        preg_match_all( '/(?ims)([a-z0-9\s\,\.\:#_\-@]+)\{([^\}]*)\}/', $string, $arr);

        $result = array();
        foreach ($arr[0] as $i => $x)
        {
            $selector = trim($arr[1][$i]);
            $rules = explode(';', trim($arr[2][$i]));
            $result[$selector] = array();
            foreach ($rules as $strRule)
            {
                if (!empty($strRule))
                {
                    $rule = explode(":", $strRule);
                    $result[$selector][trim($rule[0])] = trim($rule[1]);
                }
            }
        }

        return $result;
    }
}