<?php

namespace App\Compiler;

use App\Console\Output;

abstract class Plugin implements IPlugin
{
    public $language;
    public Output $output;

    public function __construct($language='js')
    {
        $this->language = $language;
        $this->output = new Output;
    }

    public static function all($language)
    {
        $configPlugins = config('plugins.'.$language, [], true);
        $plugins = [];
        foreach ($configPlugins as $configPlugin) {
            $plugins[] = new $configPlugin($language);
        }

        return $plugins;
    }

    public function event($event, &$bus = [])
    {
        //Todo initiate Event Bus
    }
}
