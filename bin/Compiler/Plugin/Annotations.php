<?php

namespace App\Compiler\Plugin;

use App\Compiler\FileCompiler;
use App\Compiler\Plugin;

class Annotations extends Plugin {
    private static $callbacks = [];

    public function run(FileCompiler $fileCompiler) {
        $annotationRgx = syntax($this->language)->get('annotations');
        
        preg_match_all($annotationRgx, $fileCompiler->content, $matches);
        foreach($matches[0] as $i=>$annotation) {
            $key = $matches[1][$i];
            $value = $matches[2][$i];

            foreach(self::$callbacks as $callback) {
                call_user_func_array($callback, [$fileCompiler, $key, $value, $this]);
            }
        }
    }

    public static function callback(callable $callback) {
        self::$callbacks[] = $callback;
    }
}