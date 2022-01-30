<?php

namespace App\HtmlGui;

use App\HtmlGui\Components\GuiComponent;

class GuiAttributes
{
    const JS_ATTRIBUTES = [
        'width',
        'height',
        'x',
        'y'
    ];

    private $rawData = [];
    private $data = [];

    public function __construct($data = [])
    {
        $data = $data ?? [];
        $this->rawData = $data;
        foreach ($data as $propName => $propValue) {
            $isJs = false;
            // dump('===', $propName, $propValue);
            if (strpos($propName, 'js-') === 0) {
                $propName = preg_replace('/^js\-/', '', $propName);
                $isJs = true;
            }

            $this->add($propName, $propValue, $isJs);
        }
    }

    public function merge($data)
    {
        if (is_array($data)) {
            $attributes = new self($data);
            return $this->merge($attributes);
        }
        return $this->data = array_merge($this->data, $data->getData());
    }

    public function copy(): self
    {
        return new self($this->rawData);
    }

    public function only($keys): self
    {
        $copy = $this->copy();

        foreach ($copy->keys() as $propName) {
            if (!in_array($propName, $keys)) {
                $copy->remove($propName);
            }
        }

        return $copy;
    }

    public function getProp($propName)
    {
        return $this->data[$propName];
    }

    public function remove($propName)
    {
        if (!$this->has($propName)) {
            return false;
        }

        unset($this->data[$propName]);
        return true;
    }

    public function add($propName, $value, $isJs = false)
    {
        $this->data[$propName] = compact('isJs', 'value');

        return $this;
    }

    public function set($propName, $option, $value)
    {
        if ($this->has($propName)) {
            $this->data[$propName][$option] = $value;
        }

        return $this;
    }

    public function has($propName)
    {
        return isset($this->data[$propName]);
    }

    public function get($propName, $default = null, $asJs = null)
    {
        if (!$this->has($propName)) {
            return $asJs ? $default : json_encode($default);
        }
        $propData = $this->data[$propName];

        $isJs = $propData['isJs'];
        if (!is_null($asJs)) {
            $isJs = $asJs;
        }

        $value = $isJs ? $propData['value'] : json_encode($propData['value']);

        $value = str_replace(
            array_values(GuiComponent::$attrReplaces),
            array_keys(GuiComponent::$attrReplaces),
            $value
        );

        return $value;
    }

    public function keys()
    {
        return array_keys($this->data);
    }

    public function getData()
    {
        return $this->data;
    }

    public function isJs($propName)
    {
        if (!$this->has($propName)) {
            return false;
        }

        return $this->data[$propName]['isJs'] ?? false;
    }

    public function toArray()
    {
        $data = [];
        foreach ($this->data as $propName => $propData) {
            $data[$propName] = $this->get($propName);
        }

        return $data;
    }

    public function toPropJs($propsData = [])
    {
        $array = [];
        $js = '{';
        $i = 0;
        foreach ($this->data as $propName => $prop) {
            $value = $this->get($propName, null, $prop['isJs'] || in_array($propName, self::JS_ATTRIBUTES));

            if (isset($propsData[$propName])) {
                switch ($propsData[$propName]['type']) {
                    case 'function':
                        $value = "function(){ $value }";
                        break;
                }
            }

            $js .= "\"$propName\":$value" . ($i < count($this->data) - 1 ? ',' : '');
            $i++;
        }
        $js .= '}';
        return $js;
    }
}
