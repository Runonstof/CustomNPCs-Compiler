<?php

namespace App\Compiler\Plugin;

use App\Compiler\FileCompiler;
use App\Compiler\Plugin;

class GuiIdReserver extends Plugin
{
    public function prerun(FileCompiler $fileCompiler)
    {
        $guiIdFuncRgx = syntax($this->language)->get('guiIdFunc');
        preg_match_all($guiIdFuncRgx, $fileCompiler->content, $matches);
        $block = new \stdClass;
        // dump($matches);

        $block->content = '';
        $block->name = '_GUI_IDS';
        $block->innerContent = '/* GUI Id Reserver. Auto Generated IDs */' . "\n";
        $block->file = 'Plugin GUI ID Reserver';
        $block->lineNumber = -1;

        $done = [];
        foreach ($matches[1] as $idCode) {
            if (array_search($idCode, $done) !== false) {
                continue;
            }
            $done[] = $idCode;
            $block->innerContent .= $idCode . ";\n";
        }

        $block->innerContent .= '/* End GUI Id Reserver */';

        $fileCompiler->blocks->push($block);

        foreach ($matches[0] as $i => $replaceCode) {
            $fileCompiler->content = str_replace($replaceCode, $matches[1][$i], $fileCompiler->content);
        }
        /*

            $block->content = $match;
            $block->name = $matches[1][$i];
            $block->innerContent = $sourceCommentLine.$matches[2][$i].$endSourceCommentLine;
            $block->file = $this->filePath;
            $block->lineNumber = $lineNumber;

        */
    }
}
