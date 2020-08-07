<?php

namespace App\Console;

use Tightenco\Collect\Support\Collection;

class Output
{
    private $foreColors = [];
    private $backColors = [];
    private $colors;
    private static $parseRegex = "/#(@?)([\w])/";

    public function __construct()
    {
        // Set up shell colors
        $this->foreColors['black'] = '0;30';//0
        $this->foreColors['dark_gray'] = '1;30';//8
        $this->foreColors['blue'] = '0;34';//1
        $this->foreColors['light_blue'] = '1;34';//9
        $this->foreColors['green'] = '0;32';//2
        $this->foreColors['light_green'] = '1;32';//a
        $this->foreColors['cyan'] = '0;36';//3
        $this->foreColors['light_cyan'] = '1;36';//b
        $this->foreColors['red'] = '0;31';//4
        $this->foreColors['light_red'] = '1;31';//c
        $this->foreColors['purple'] = '0;35';//5
        $this->foreColors['light_purple'] = '1;35';//d
        $this->foreColors['brown'] = '0;33';
        $this->foreColors['yellow'] = '1;33';//e
        $this->foreColors['light_gray'] = '0;37';//7
        $this->foreColors['white'] = '1;37';//f
        $this->foreColors['reset'] = '0';

        $this->backColors['black'] = '40';
        $this->backColors['red'] = '41';
        $this->backColors['green'] = '42';
        $this->backColors['yellow'] = '43';
        $this->backColors['blue'] = '44';
        $this->backColors['magenta'] = '45';
        $this->backColors['cyan'] = '46';
        $this->backColors['light_gray'] = '47';

        $this->colors = new Collection([
            "black" => "0",
            "dark_gray" => "8",
            "blue" => "1",
            "light_blue" => "9",
            "green" => "2",
            "light_green" => "a",
            "cyan" => "3",
            "light_cyan" => "b",
            "red" => "4",
            "light_red" => "c",
            "purple" => "5",
            "light_purple" => "d",
            "yellow" => "e",
            "light_gray" => "7",
            "white" => "f",
            "brown" => "6",
            "reset" => "r"
        ]);
    }

    // Returns colored string
    public function getColoredString($string, $foreColor = null, $backColor = null)
    {
        $colorString = "";

        // Check if given foreground color found
        if (isset($this->foreColors[$foreColor])) {
            $colorString .= "\033[" . $this->foreColors[$foreColor] . "m";
        }
        // Check if given background color found
        if (isset($this->backColors[$backColor])) {
            $colorString .= "\033[" . $this->backColors[$backColor] . "m";
        }

        // Add string and end coloring
        $colorString .=  $string . "\033[0m";

        return $colorString;
    }

    // Returns all foreground color names
    public function getForegroundColors()
    {
        return array_keys($this->foreColors);
    }

    // Returns all background color names
    public function getBackgroundColors()
    {
        return array_keys($this->backColors);
    }

    // Clears php CLI Output
    public function clear($returns=false)
    {
        $clearText = chr(27).chr(91).'H'.chr(27).chr(91).'J';
        if ($returns) {
            return $clearText;
        } else {
            echo $clearText;
        }
        return null;
    }


    public function parse($string)
    {
        preg_match_all(self::$parseRegex, $string, $m);

        foreach ($m[0] as $i=>$replaceCode) {
            $isBack = !empty($m[1][$i]);
            $colorCode = $m[2][$i];
            $colorName = $this->colors->flip()->get($colorCode);

            $string = str_replace($replaceCode, "\033[".$this->{($isBack ? 'back' : 'fore') . 'Colors'}[$colorName] . 'm', $string);
        }

        return $string."\033[0m";
    }

    public function up()
    {
        echo "\033[2A";
    }

    public function print($string)
    {
        echo $this->parse($string)."\n";
    }
    
    public function pprint($string, $level = 0)
    {
        $levels = [
            '#a[INFO]',
            '#e[NOTICE]',
            '#4[WARN]'
        ];

        $text = is_null($level) ? '' : $levels[$level] ?? '';
        $this->print('#6[Runon-Compiler]' . $text . ' #r' . $string);
    }
}
