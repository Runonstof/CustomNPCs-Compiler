<?php

namespace App\Compiler\Plugin;

use App\Compiler;
use App\Compiler\FileCompiler;
use App\Compiler\Plugin;

class BabelRunner extends Plugin
{
    public function preCompile(Compiler $compiler, &$status)
    {
        if (!file_exists(BASEDIR . '/temp')) {
            mkdir(BASEDIR . '/temp');
        } else {
            rrmdir(BASEDIR . '/temp/');
        }
        $compiler->setBuildFolder('temp' . DIRECTORY_SEPARATOR);
        $compiler->console->output->pprint('#fBabel Runner enabled.');
    }

    public function postCompile(Compiler $compiler, &$status)
    {
        // $a = exec('npm run build', $b, $c);
        
        $compiler->console->output->pprint('#fRunning Babel for ES6 JavaScript features...');
        
        $cmd = 'npm run build';
        flush();
        $proc = popen($cmd, 'r');
        while (!feof($proc)) {
            echo fread($proc, 4096);
            @ flush();
        }
        // dump($proc);
        $status = pclose($proc);

        if ($status !== 0) {
            $compiler->console->output->pprint('#cBabel errored with exit code ' . $status, 2);
            return false;
        }
    }
}
