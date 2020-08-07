<?php

namespace App\Compiler\Plugin;

use App\Compiler\FileCompiler;
use App\Compiler\Plugin;

class ImportConverter extends Plugin
{
    public function preLoad($language = null)
    {
    }

    public function preRun(FileCompiler $fileCompiler)
    {
        $importRgx = syntax($this->language)->get('import');


        // preg_match_all($importRgx, $fileCompiler->content, $matches);

        // foreach ($matches[1] as $i=>$importSyntax) {
        //     $importCode = $matches[0][$i];
            
        //     $newImportSyntax = ''.str_replace('.', '/', $importSyntax).'.js';
        //     $newImportCode = str_replace($importSyntax, $newImportSyntax, $importCode);

        //     $fileCompiler->content = str_replace($importCode, $newImportCode, $fileCompiler->content);
        // }
    }

    public function run()
    {
    }
}
