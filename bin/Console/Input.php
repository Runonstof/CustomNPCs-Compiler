<?php

namespace App\Console;

use Tightenco\Collect\Support\Collection;

class Input
{
    public $options;

    public function __construct()
    {
        $this->options = new Collection;
    }

    public function getArguments()
    {
        return array_slice($argv, 1);
    }

    public function mergeOptions($options)
    {
        $this->options = $this->options->merge($options);
        return $this;
    }
    

    public function has($key)
    {
        return in_array($key, $this->optionNames());
    }

    public function optionNames()
    {
        return $this->options->pluck('name')->toArray();
    }

    public function initOption($name, $key=null, $default=null)
    {
        $val = $this->options->where('name', $name)->first();

        if ($val) {
            $type = $val['type'];
            $def = false;
            switch ($type) {
                case 'array':
                    $def = [];
                break;
                
            }

            return $key && isset($val[$key]) ? $val[$key] : $def;
        }

        return $default;
    }

    public function getOptionInput()
    {
        $opts = [];

        foreach ($this->options as $option) {
            $opts[] = $option['option'];
        }

        $options = getopt('', $opts);

        foreach ($options as $name=>&$option) {
            $toption = $this->options->where('name', $name)->first();
            $type = $toption['type']??'auto';

            switch ($type) {
                case "object":
                    $option = to_object(explode(',', $option));
                    break;
                case "array":
                    $option = explode(',', $option);
                    break;
                case "collection":
                    $option = new Collection(explode(',', $option));
                    break;
                case "float":
                    $option = floatval($option);
                    break;
                case "int":
                case "integer":
                    $option = intval($option);
                    break;
                case "auto":
                    if (is_json($option)) {
                        $option = json_decode($option);
                    } elseif ($option == 'true' || $option == 'false') {
                        $option = ($option == 'true');
                    } elseif (is_numeric($option)) {
                        $option = intval($option);
                    }
                    break;
                case "bool":
                case "boolean":
                    $option = $this->has($name);
                    break;
                case "json":
                    if (!is_json($option)) {
                        echo (new Output)->parse("#f#@4ERROR: Option '--$name' is not valid json.");
                        exit;
                    }

                    $option = json_decode($option);
                    break;
                case "string":
                default:
                    //
                    break;
            }
        }

        $options = to_object($options, false);

        foreach ($this->optionNames() as $optionName) {
            if (!property_exists($options, $optionName)) {
                $options->{$optionName} = $this->initOption($optionName, 'default', false);
            }
        }


        //replace kebab-case to camelCase
        foreach ($options as $key=>$value) {
            $newKey = kebabToCamel($key);
            if ($newKey != $key) {
                $options->{$newKey} = $options->{$key};
            }
        }

        

        return $options;
    }
}
