<?php

namespace App\Compiler\Plugin;

use App\Compiler\Plugin;
use App\Compiler\FileCompiler;

class JavaImporter extends Plugin
{
    public function run(FileCompiler $fileCompiler)
    {
        $javaRgx = syntax($this->language)->get('javaImport');

        preg_match_all($javaRgx, $fileCompiler->content, $matches);

        foreach($matches[0] as $i=>$javaImport) {
            $javaPath = $matches[1][$i];
            $javaAlias = $matches[2][$i] ?: array_value_last(explode('.', $javaPath));

            $javaCode = "var $javaAlias = Java.type('$javaPath');\n";

            $fileCompiler->content = str_replace($javaImport, $javaCode, $fileCompiler->content);
        }
    }
}