<?php

namespace App\Compiler\Plugin;

use App\Compiler\FileCompiler;
use App\Compiler\Plugin;

class FunctionTweak extends Plugin
{
    public function run(FileCompiler $fileCompiler)
    {
        $fnRgx = syntax($this->language)->get('functionTweak');
        $fnArgsRgx = syntax($this->language)->get('functionTweakArgs');

        preg_match_all($fnRgx, $fileCompiler->content, $matches);
        // dump($matches);
        foreach ($matches[0] as $i=>$function) {
            $fnName = $matches[1][$i];
            $fnArgs = $matches[2][$i];
            $newFunctionCode = 'function' . (empty($fnName) ? '' : ' '.$fnName).'(';
            $newArgCode = '';
            preg_match_all($fnArgsRgx, $fnArgs, $argMatches);
            
            foreach ($argMatches[0] as $j=>$argument) {
                $argName = $argMatches[1][$j];
                $argValue = $argMatches[2][$j];

                $newFunctionCode .= $argName . (intval($j) < count($argMatches[0])-1 ? ', ' : '');
                if (!empty($argValue)) {
                    $newArgCode .= "\nif(typeof $argName === typeof undefined) { var $argName = $argValue; }";
                }
            }

            $newFunctionCode .= ') {' . $newArgCode;

            $fileCompiler->content = str_replace($function, $newFunctionCode, $fileCompiler->content);
        }
    }
}
