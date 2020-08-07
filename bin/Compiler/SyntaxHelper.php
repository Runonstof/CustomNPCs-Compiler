<?php

namespace App\Compiler;

use Tightenco\Collect\Support\Collection;

class SyntaxHelper
{
    private $language;
    private static $cache = null;

    private static function init($language = null)
    {
        if (is_null(self::$cache)) {
            self::$cache = new Collection;
        }

        self::$cache[$language] = new Collection(include realpath(__DIR__ . "\..\..\config\syntax\\".$language.".php") ?? []);
    }

    public function __construct($language=null)
    {
        $this->language = $language ?? 'js';
        self::init($this->language);
    }

    public function setLang($lang)
    {
        $this->language = $lang;
    }

    public function all()
    {
        return self::$cache[$this->language];
    }

    public function get($key)
    {
        return $this->all()[$key] ?? null;
    }

    public function set($key, $value)
    {
        self::$cache[$this->language][$key] = $value;
        return $this;
    }
}
