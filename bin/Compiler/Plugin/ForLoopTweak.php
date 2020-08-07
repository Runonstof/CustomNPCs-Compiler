<?php

namespace App\Compiler\Plugin;

use App\Compiler\FileCompiler;
use App\Compiler\Plugin;

class ForLoopTweak extends Plugin {
    public function run(FileCompiler $fileCompiler) {
        $forLoopRgx = syntax($this->language)->get('forLoopTweak');
        
        preg_match_all($forLoopRgx, $fileCompiler->content, $matches);
        foreach ($matches[0] as $i=>$forLoop) {
            $incrementer = $matches[1][$i];
            $list = $matches[2][$i];
            $alias = $matches[3][$i];

            $newForLoop ='for(var '.$incrementer.' in '.$list.') {'."\n\t".'var '.$alias.' = '.$list.'['.$incrementer.'];';
            $fileCompiler->content = str_replace($forLoop, $newForLoop, $fileCompiler->content);
        }
        
    }
}