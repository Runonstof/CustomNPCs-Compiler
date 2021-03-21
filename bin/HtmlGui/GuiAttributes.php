<?php

namespace App\HtmlGui;

class GuiAttributes
{
    private $rawData = [];
    private $data = [];

    public function __construct($data = [])
    {
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

        return $isJs ? $propData['value'] : json_encode($propData['value']);
    }

    public function keys()
    {
        return array_keys($this->data);
    }

    public function isJs($propName)
    {
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

    public function toPropJs()
    {
        $array = [];
        $js = '{';
        $i = 0;
        foreach ($this->data as $propName => $prop) {
            $value = $this->get($propName, null, $prop['isJs']);
            $js .= "\"$propName\":$value" . ($i < count($this->data) - 1 ? ',' : '');
            $i++;
        }
        $js .= '}';
        return $js;
    }
}
