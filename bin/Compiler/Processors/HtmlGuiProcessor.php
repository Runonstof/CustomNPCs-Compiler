<?php

namespace App\Compiler\Processors;

use App\Compiler\Exceptions\CompilerException;
use App\Compiler\FileCompiler;
use App\HtmlGui\Components\GuiComponent;

class HtmlGuiProcessor
{
    public function run($string, $import, FileCompiler $fileCompiler)
    {
        if (empty($import->alias)) {
            throw new CompilerException('HTML Gui import requires an alias!', $fileCompiler->getOriginal(), $import->lineNumber, $fileCompiler->getFilePath());
        }

        if (count($import->filePaths) == 0) {
            throw new CompilerException('File: ' . $import->rawPath . ' not found!', $fileCompiler->getOriginal(), $import->lineNumber, $fileCompiler->getFilePath());
        }

        if (count($import->filePaths) > 1) {
            throw new CompilerException('Found more than 1 file matches for path: ' . $import->rawPath, $fileCompiler->getOriginal(), $import->lineNumber, $fileCompiler->getFilePath());
        }

        $gui = GuiComponent::fromXml($string, $import->filePaths[0]);
        $js = "/*VARRRR*/var $import->alias = {$gui->renderAsFunction($import->alias)}";

        $block = new \stdClass;
        // dump($matches);

        $block->content = '';
        $block->name = '_GUI_IDS';
        $block->innerContent = '/* GUI IDS */' . "\n" . $gui->render('renderIds');
        $block->file = 'Plugin GUI ID Reserver';
        $block->lineNumber = -1;
        $fileCompiler->blocks->push($block);

        // foreach()

        return $js;
    }
}
